/**
 * LiveKit Whiteboard Module — virtual-participant tile model (mobile parity).
 *
 * The whiteboard renders as a tile inside the existing #videoGrid (like a
 * regular participant). Clicking the tile triggers the meeting's focus
 * mode — the same UX as clicking any other participant. A small floating
 * toolbar appears next to the focused canvas for the teacher's drawing
 * controls.
 *
 * Wire protocol (frozen, byte-parity with mobile):
 *   topic 'whiteboard', JSON UTF-8, reliable
 *   Source of truth: itqan-mobile/lib/features/session/models/whiteboard_message.dart
 *
 * Coordinates: normalized 0..1 against canvasW × canvasH (default 1080×1440).
 * Each peer letterboxes its canvas onto the same logical rect so points
 * line up across aspect ratios.
 *
 * Render flow: every animation frame paints to ALL `.wb-canvas` elements
 * — currently {the tile's canvas} ∪ {the cloned canvas inside #focusedVideoContainer
 * when focus mode is on}. This is necessary because the meeting's focus
 * mode `cloneNode(true)`s the tile, and canvas cloneNode copies the DOM
 * but not the pixel buffer. By painting to every `.wb-canvas` element on
 * each render tick, both tile and focused clone stay in sync.
 *
 * Pointer events for drawing are bound to ANY `.wb-canvas` element via
 * event delegation (capture-phase listener on document). The tile-sized
 * canvas is too small for accurate input so we only register strokes that
 * originate on the FOCUSED canvas (the large one inside
 * #focusedVideoContainer). This is the same behavior mobile gives users:
 * draw only happens when the whiteboard is the active focused surface.
 *
 * State is fully ephemeral: closing the tile, reconnecting, or leaving
 * the room wipes the canvas. Late joiners re-fetch via wb_snap_req.
 */
(function () {
    'use strict';

    const PROTO_VERSION = 1;
    const CANVAS_W = 1080;
    const CANVAS_H = 1440;
    const MAX_CHUNK_POINTS = 500;
    const SNAP_RETRY_DELAYS_MS = [1000, 3000, 7000, 15000];
    const SNAPSHOT_COALESCE_MS = 250;
    const ASSEMBLER_TIMEOUT_MS = 5000;
    const SNAP_REPLY_BATCH = 50;
    const SNAP_REPLY_INTERVAL_MS = 100;
    const TILE_ID = 'participant-whiteboard';
    const CANVAS_CLASS = 'wb-canvas';
    const FOCUS_CONTAINER_ID = 'focusedVideoContainer';

    // Index → hex. **Append-only**, must match WhiteboardColor enum in
    // itqan-mobile/lib/features/session/models/whiteboard_stroke.dart.
    const COLOR_PALETTE = [
        '#111827', '#DC2626', '#2563EB', '#059669',
        '#EAB308', '#EA580C', '#9333EA',
    ];
    const WIDTH_PALETTE = [2, 4, 8, 16, 28]; // Index → logical px

    const TOOL_PEN = 0;
    const TOOL_ERASER = 1;

    function uuid() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
        return Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
    }
    function nowMs() { return Date.now(); }
    function flatten(points) {
        const out = new Array(points.length * 2);
        for (let i = 0; i < points.length; i++) {
            out[i * 2] = points[i].x;
            out[i * 2 + 1] = points[i].y;
        }
        return out;
    }
    function unflatten(flat) {
        const out = [];
        for (let i = 0; i + 1 < flat.length; i += 2) {
            const x = Number(flat[i]); const y = Number(flat[i + 1]);
            if (!isFinite(x) || !isFinite(y)) continue;
            out.push({ x, y });
        }
        return out;
    }
    function tt(key, fallback) {
        if (typeof window.t === 'function') {
            const v = window.t(key);
            if (v && v !== key) return v;
        }
        return fallback;
    }

    function chunkStroke(stroke, boardId, seqStart) {
        const flat = flatten(stroke.points);
        const n = stroke.points.length;
        const chunks = Math.max(1, Math.ceil(n / MAX_CHUNK_POINTS));
        const packets = [];
        for (let k = 0; k < chunks; k++) {
            const start = k * MAX_CHUNK_POINTS * 2;
            const end = Math.min(start + MAX_CHUNK_POINTS * 2, flat.length);
            packets.push({
                type: 'wb_stroke', v: PROTO_VERSION,
                boardId: boardId, seq: seqStart + k,
                sid: stroke.strokeId, t: stroke.tool, c: stroke.color, w: stroke.width,
                k: k, ks: chunks, d: k === chunks - 1,
                p: flat.slice(start, end),
            });
        }
        return packets;
    }

    class StrokeAssembler {
        constructor() { this._pending = new Map(); }
        feed(packet) {
            const key = packet.boardId + '|' + packet.sid;
            this._evictStale();
            let entry = this._pending.get(key);
            if (!entry) {
                entry = {
                    tool: packet.t, color: packet.c, width: packet.w,
                    chunks: new Array(packet.ks).fill(null),
                    received: 0, total: packet.ks, ts: nowMs(),
                };
                this._pending.set(key, entry);
            }
            if (packet.k < 0 || packet.k >= entry.total) return null;
            if (entry.chunks[packet.k] !== null) return null;
            entry.chunks[packet.k] = packet.p;
            entry.received++;
            entry.ts = nowMs();
            if (entry.received < entry.total || !packet.d) return null;
            this._pending.delete(key);
            let flat = [];
            for (const c of entry.chunks) { if (!c) return null; flat = flat.concat(c); }
            return {
                strokeId: packet.sid, tool: entry.tool, color: entry.color, width: entry.width,
                points: unflatten(flat),
            };
        }
        _evictStale() {
            const cutoff = nowMs() - ASSEMBLER_TIMEOUT_MS;
            for (const [k, e] of this._pending) if (e.ts < cutoff) this._pending.delete(k);
        }
    }

    class Whiteboard {
        constructor() {
            this._room = null;
            this._canWrite = false;
            this._localIdentity = null;
            this._initialized = false;
            this._destroyed = false;
            this._isActive = false;
            this._boardId = null;
            this._isWaitingForSnapshot = false;
            this._strokes = new Map();
            this._undoStack = [];
            this._redoStack = [];
            this._draftStroke = null;
            this._seq = 0;
            this._strokeCounter = 0;
            this._lastSeqByBoard = new Map();
            this._activeTool = TOOL_PEN;
            this._color = 0;
            this._width = 1;
            this._assembler = new StrokeAssembler();
            this._pendingSnapshot = null;
            this._snapReqTimers = [];
            this._snapReplyTimer = null;
            this._snapshotRequesters = new Set();
            this._renderRaf = 0;
            // Gesture state (focus-mode canvas only)
            this._pointers = new Map(); // pointerId → {x, y, canvas}
            this._gestureMode = 'idle';  // 'idle' | 'draw' | 'pan' | 'pinch'
            this._panStart = null;       // {tx, ty, x, y}
            this._pinchStart = null;     // {dist, cx, cy, scale, tx, ty}
            this._drawingPointerId = null;
            // Pan/zoom transform for the focused canvas. Never broadcast —
            // local presentation only (mobile parity: view matrix is per
            // peer). Teacher can zoom out and draw off the [0..1] home rect;
            // students need their own pan/zoom to see those strokes.
            this._focusView = { tx: 0, ty: 0, scale: 1 };
            this._encoder = new TextEncoder();
            this._decoder = new TextDecoder();
            this._focusObserver = null;
        }

        // ───── Public API ─────

        init(room, canWrite, opts) {
            if (this._initialized) return;
            this._room = room;
            this._canWrite = !!canWrite;
            this._localIdentity = (opts && opts.localIdentity) || (room && room.localParticipant && room.localParticipant.identity) || null;
            this._initialized = true;

            this._installDelegatedPointer();
            this._observeFocusMode();

            // Students send snap_req on init so they catch any in-flight board.
            if (!this._canWrite) this._scheduleSnapReq();
        }

        destroy() {
            if (this._destroyed) return;
            this._destroyed = true;
            this._clearSnapReqTimers();
            if (this._snapReplyTimer) { clearTimeout(this._snapReplyTimer); this._snapReplyTimer = null; }
            if (this._renderRaf) { cancelAnimationFrame(this._renderRaf); this._renderRaf = 0; }
            if (this._focusObserver) { this._focusObserver.disconnect(); this._focusObserver = null; }
            this._removeTile();
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
        }

        toggle() {
            if (!this._canWrite) return;
            if (this._isActive) this.close();
            else this.open();
        }

        open() {
            if (!this._canWrite || this._isActive) return;
            this._boardId = uuid();
            this._seq = 0;
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._isActive = true;
            this._publish({
                type: 'wb_open', v: PROTO_VERSION,
                boardId: this._boardId, seq: this._nextSeq(),
                cw: CANVAS_W, ch: CANVAS_H,
            });
            this._ensureTile();
            this._requestRender();
        }

        close() {
            if (!this._canWrite) return;
            if (this._isActive) {
                this._publish({
                    type: 'wb_close', v: PROTO_VERSION,
                    boardId: this._boardId, seq: this._nextSeq(),
                });
            }
            this._isActive = false;
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._draftStroke = null;
            this._removeTile();
        }

        clear() {
            if (!this._canWrite || !this._isActive) return;
            this._publish({
                type: 'wb_clear', v: PROTO_VERSION,
                boardId: this._boardId, seq: this._nextSeq(),
            });
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._requestRender();
        }

        undo() {
            if (!this._canWrite || !this._isActive) return;
            if (!this._undoStack.length) return;
            const sid = this._undoStack.pop();
            const s = this._strokes.get(sid);
            if (!s) return;
            this._strokes.delete(sid);
            this._redoStack.push(s);
            this._publish({
                type: 'wb_undo', v: PROTO_VERSION,
                boardId: this._boardId, seq: this._nextSeq(),
                sid: sid,
            });
            this._requestRender();
        }

        redo() {
            if (!this._canWrite || !this._isActive) return;
            if (!this._redoStack.length) return;
            const s = this._redoStack.pop();
            this._strokes.set(s.strokeId, s);
            this._undoStack.push(s.strokeId);
            this._broadcastStroke(s);
            this._requestRender();
        }

        setTool(t) { this._activeTool = (t === TOOL_ERASER) ? TOOL_ERASER : TOOL_PEN; this._refreshToolbar(); }
        setColor(i) { if (i >= 0 && i < COLOR_PALETTE.length) { this._color = i; this._refreshToolbar(); } }
        setWidth(i) { if (i >= 0 && i < WIDTH_PALETTE.length) { this._width = i; this._refreshToolbar(); } }

        // ───── Inbound packet routing ─────

        onPacket(payload, participant) {
            if (!payload || !payload.length) return;
            let data; try { data = JSON.parse(this._decoder.decode(payload)); } catch (_) { return; }
            if (!data || data.v !== PROTO_VERSION) return;
            const senderId = participant && participant.identity;
            switch (data.type) {
                case 'wb_open':         this._onOpen(data); break;
                case 'wb_close':        this._onClose(data); break;
                case 'wb_stroke':       this._onStroke(data); break;
                case 'wb_undo':         this._onUndo(data); break;
                case 'wb_clear':        this._onClear(data); break;
                case 'wb_snap_req':     this._onSnapReq(senderId); break;
                case 'wb_snap_begin':   this._onSnapBegin(data); break;
                case 'wb_snap_stroke':  this._onSnapStroke(data); break;
                case 'wb_snap_end':     this._onSnapEnd(data); break;
            }
        }

        _onOpen(data) {
            if (this._canWrite) return; // teachers never adopt foreign opens
            this._boardId = data.boardId;
            this._isActive = true;
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._isWaitingForSnapshot = false;
            this._clearSnapReqTimers();
            this._lastSeqByBoard.set(data.boardId, data.seq);
            this._ensureTile();
            this._requestRender();
        }

        _onClose(data) {
            if (this._canWrite) return;
            if (data.boardId !== this._boardId) return;
            this._isActive = false;
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._draftStroke = null;
            this._removeTile();
        }

        _onStroke(data) {
            if (this._canWrite) return;
            if (data.boardId !== this._boardId) return;
            if (this._isSeqStale(data)) return;
            const s = this._assembler.feed(data);
            if (!s) return;
            this._strokes.set(s.strokeId, s);
            this._undoStack.push(s.strokeId);
            this._requestRender();
        }

        _onUndo(data) {
            if (this._canWrite) return;
            if (data.boardId !== this._boardId) return;
            if (this._isSeqStale(data)) return;
            this._strokes.delete(data.sid);
            const idx = this._undoStack.indexOf(data.sid);
            if (idx >= 0) this._undoStack.splice(idx, 1);
            this._requestRender();
        }

        _onClear(data) {
            if (this._canWrite) return;
            if (data.boardId !== this._boardId) return;
            if (this._isSeqStale(data)) return;
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._requestRender();
        }

        _onSnapReq(senderId) {
            if (!this._canWrite || !this._isActive || !senderId) return;
            this._snapshotRequesters.add(senderId);
            if (this._snapReplyTimer) return;
            this._snapReplyTimer = setTimeout(() => {
                this._snapReplyTimer = null;
                this._sendSnapshot();
            }, SNAPSHOT_COALESCE_MS);
        }

        _onSnapBegin(data) {
            if (this._canWrite) return;
            this._boardId = data.boardId;
            this._isActive = true;
            this._isWaitingForSnapshot = true;
            this._pendingSnapshot = { boardId: data.boardId, strokes: [] };
            this._clearSnapReqTimers();
            this._ensureTile();
            this._requestRender();
        }

        _onSnapStroke(data) {
            if (this._canWrite) return;
            if (!this._pendingSnapshot || this._pendingSnapshot.boardId !== data.boardId) return;
            const raw = data.s;
            if (!raw) return;
            this._pendingSnapshot.strokes.push({
                strokeId: raw.id,
                tool: Number(raw.t) || 0,
                color: Number(raw.c) || 0,
                width: Number(raw.w) || 0,
                points: unflatten(raw.p || []),
            });
        }

        _onSnapEnd(data) {
            if (this._canWrite) return;
            if (!this._pendingSnapshot || this._pendingSnapshot.boardId !== data.boardId) return;
            this._strokes.clear();
            this._undoStack.length = 0;
            for (const s of this._pendingSnapshot.strokes) {
                this._strokes.set(s.strokeId, s);
                this._undoStack.push(s.strokeId);
            }
            this._pendingSnapshot = null;
            this._isWaitingForSnapshot = false;
            this._lastSeqByBoard.set(data.boardId, data.seq);
            this._requestRender();
        }

        _isSeqStale(data) {
            const last = this._lastSeqByBoard.get(data.boardId) || 0;
            if (data.seq < last) return true;
            this._lastSeqByBoard.set(data.boardId, data.seq);
            return false;
        }

        // ───── Wire I/O ─────

        _publish(obj, opts) {
            if (!this._room || !this._room.localParticipant) return;
            const bytes = this._encoder.encode(JSON.stringify(obj));
            const options = { reliable: true, topic: 'whiteboard' };
            if (opts && opts.to && opts.to.length) options.destinationIdentities = opts.to;
            try {
                this._room.localParticipant.publishData(bytes, options);
            } catch (e) {
                if (window.MT?.warn) window.MT.warn('whiteboard', 'publish_failed', { err: String(e) });
            }
        }

        _nextSeq() { return ++this._seq; }

        _broadcastStroke(stroke) {
            const startSeq = this._seq + 1;
            const packets = chunkStroke(stroke, this._boardId, startSeq);
            this._seq += packets.length;
            for (const p of packets) this._publish(p);
        }

        _scheduleSnapReq() {
            this._clearSnapReqTimers();
            this._isWaitingForSnapshot = true;
            for (const delay of SNAP_RETRY_DELAYS_MS) {
                const tm = setTimeout(() => {
                    if (this._destroyed) return;
                    if (this._isActive && !this._isWaitingForSnapshot) return;
                    if (this._strokes.size > 0) return;
                    this._publish({ type: 'wb_snap_req', v: PROTO_VERSION });
                }, delay);
                this._snapReqTimers.push(tm);
            }
        }
        _clearSnapReqTimers() {
            for (const t of this._snapReqTimers) clearTimeout(t);
            this._snapReqTimers.length = 0;
        }

        _sendSnapshot() {
            const to = Array.from(this._snapshotRequesters);
            this._snapshotRequesters.clear();
            if (!to.length || !this._isActive) return;
            const strokes = Array.from(this._strokes.values());
            this._publish({
                type: 'wb_snap_begin', v: PROTO_VERSION,
                boardId: this._boardId, seq: this._nextSeq(),
                n: strokes.length, cw: CANVAS_W, ch: CANVAS_H,
            }, { to });
            let i = 0;
            const sendBatch = () => {
                if (this._destroyed) return;
                const end = Math.min(i + SNAP_REPLY_BATCH, strokes.length);
                for (; i < end; i++) {
                    const s = strokes[i];
                    this._publish({
                        type: 'wb_snap_stroke', v: PROTO_VERSION,
                        boardId: this._boardId, seq: this._nextSeq(),
                        s: { id: s.strokeId, t: s.tool, c: s.color, w: s.width, p: flatten(s.points) },
                    }, { to });
                }
                if (i < strokes.length) setTimeout(sendBatch, SNAP_REPLY_INTERVAL_MS);
                else this._publish({
                    type: 'wb_snap_end', v: PROTO_VERSION,
                    boardId: this._boardId, seq: this._nextSeq(),
                }, { to });
            };
            sendBatch();
        }

        // ───── Tile lifecycle ─────

        _ensureTile() {
            if (document.getElementById(TILE_ID)) {
                this._requestRender();
                return;
            }
            const grid = document.getElementById('videoGrid');
            if (!grid) return;

            const tile = document.createElement('div');
            tile.id = TILE_ID;
            // Match the visual chrome of a real participant tile so the grid
            // layout treats it identically.
            tile.className = 'participant-video relative bg-white rounded-lg overflow-hidden aspect-video w-full h-full group';
            tile.dataset.participantId = 'whiteboard';
            tile.dataset.isVirtual = 'true';

            const canvas = document.createElement('canvas');
            canvas.className = CANVAS_CLASS + ' absolute inset-0 w-full h-full block';
            canvas.style.touchAction = 'none';
            canvas.style.cursor = this._canWrite ? 'crosshair' : 'default';
            tile.appendChild(canvas);

            // Bottom-left label pill (matches other tiles' name overlay style)
            const label = document.createElement('div');
            label.className = 'absolute bottom-2 left-2 z-20 pointer-events-none';
            label.innerHTML = `
                <div class="flex items-center gap-2 bg-black bg-opacity-60 rounded-lg px-3 py-1.5 text-white text-sm shadow">
                    <i class="ri-quill-pen-line"></i>
                    <span>${tt('whiteboard.whiteboard', 'Whiteboard')}</span>
                </div>
            `;
            tile.appendChild(label);

            // Click → toggle focus mode. We bypass the participants module
            // (which only knows about real LiveKit participants) and call
            // layout directly.
            tile.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this._handleTileClick(tile);
            });

            grid.appendChild(tile);

            // Tell the layout to re-grid (it counts children of #videoGrid).
            try {
                window.meeting?.layout?.applyGrid(grid.children.length);
            } catch (_) {}

            this._requestRender();
        }

        _removeTile() {
            const tile = document.getElementById(TILE_ID);
            const layout = window.meeting?.layout;
            // Exit focus first if we're the focused element.
            try {
                if (layout?.isFocusModeActive && layout.focusedParticipant === 'whiteboard') {
                    layout.exitFocusMode();
                }
            } catch (_) {}
            if (tile && tile.parentNode) tile.parentNode.removeChild(tile);
            this._hideToolbar();
            const grid = document.getElementById('videoGrid');
            try {
                if (grid) window.meeting?.layout?.applyGrid(grid.children.length);
            } catch (_) {}
        }

        _handleTileClick(tile) {
            const layout = window.meeting?.layout;
            if (!layout) return;
            const state = layout.getLayoutState?.();
            if (state?.isFocusModeActive && state?.focusedParticipant === 'whiteboard') {
                layout.exitFocusMode();
            } else {
                layout.applyFocusMode('whiteboard', tile);
            }
        }

        // Watch for focus mode entering/leaving our tile so we can wire the
        // floating toolbar at the right moment. We can't rely on layout
        // callbacks (no IDs exposed), so MutationObserver on the focus
        // container is the cleanest hook.
        _observeFocusMode() {
            const container = document.getElementById(FOCUS_CONTAINER_ID);
            if (!container) {
                // Container is rendered server-side, but in case timing is
                // odd, retry once after a tick.
                setTimeout(() => this._observeFocusMode(), 250);
                return;
            }
            this._focusObserver = new MutationObserver(() => {
                const focused = container.querySelector('#focused-whiteboard');
                if (focused) {
                    this._onFocusEnter(focused);
                } else {
                    this._onFocusExit();
                }
            });
            this._focusObserver.observe(container, { childList: true, subtree: false });
        }

        _onFocusEnter(focusedTile) {
            // Reset pan/zoom so each focus entry starts at the home view.
            this._resetView();
            // Cursor: teacher draws with a crosshair, students get a grab
            // cursor since their gesture is pan.
            const canvas = focusedTile.querySelector('canvas.' + CANVAS_CLASS);
            if (canvas) {
                canvas.style.cursor = this._canWrite ? 'crosshair' : 'grab';
                canvas.style.touchAction = 'none'; // prevent browser pinch-zoom hijack
            }
            if (this._canWrite) this._showToolbar(focusedTile);
            else this._showStudentHint(focusedTile);
            this._requestRender();
        }

        _onFocusExit() {
            this._hideToolbar();
            this._hideStudentHint();
            // Clean up any in-flight gesture state when leaving focus mode.
            this._pointers.clear();
            this._gestureMode = 'idle';
            this._drawingPointerId = null;
            this._panStart = null;
            this._pinchStart = null;
            this._draftStroke = null;
            this._resetView();
        }

        /**
         * Small auto-fading hint that teaches students about pan/zoom on
         * first focus. Disappears after ~3 s and doesn't re-appear within
         * the same session.
         */
        _showStudentHint(focusedTile) {
            if (this._studentHintShown) return;
            this._studentHintShown = true;
            const hint = document.createElement('div');
            hint.id = 'wb-student-hint';
            hint.className = 'absolute z-[70] left-1/2 -translate-x-1/2 top-3 bg-black bg-opacity-70 text-white text-xs px-3 py-2 rounded-lg shadow pointer-events-none';
            hint.textContent = tt('whiteboard.student_pan_zoom_hint', 'Pinch or scroll to zoom · drag to pan · double-tap to reset');
            focusedTile.appendChild(hint);
            setTimeout(() => {
                if (hint.parentNode) {
                    hint.style.transition = 'opacity 0.5s';
                    hint.style.opacity = '0';
                    setTimeout(() => hint.remove(), 600);
                }
            }, 3500);
        }

        _hideStudentHint() {
            const h = document.getElementById('wb-student-hint');
            if (h && h.parentNode) h.parentNode.removeChild(h);
        }

        // ───── Toolbar (only in focus mode, teacher only) ─────

        _showToolbar(focusedTile) {
            this._hideToolbar();
            const bar = document.createElement('div');
            bar.id = 'wb-toolbar';
            bar.className = 'absolute z-[70] left-1/2 -translate-x-1/2 bottom-3 bg-gray-800 rounded-full px-2 py-1.5 shadow-2xl flex items-center gap-1';
            bar.style.pointerEvents = 'auto';
            bar.addEventListener('click', (e) => e.stopPropagation()); // don't bubble to tile click
            bar.innerHTML = `
                <button data-wb-act="pen" aria-label="${tt('whiteboard.pen', 'Pen')}" class="wb-tb w-9 h-9 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition"><i class="ri-quill-pen-line"></i></button>
                <button data-wb-act="eraser" aria-label="${tt('whiteboard.eraser', 'Eraser')}" class="wb-tb w-9 h-9 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition"><i class="ri-eraser-line"></i></button>
                <span class="w-px h-6 bg-gray-600"></span>
                ${COLOR_PALETTE.map((hex, i) =>
                    `<button data-wb-color="${i}" aria-label="${tt('whiteboard.color_' + ['black','red','blue','green','yellow','orange','purple'][i], 'Color')}" class="wb-tb-color w-6 h-6 rounded-full border-2 border-white/30 hover:border-white transition" style="background-color: ${hex};"></button>`
                ).join('')}
                <span class="w-px h-6 bg-gray-600"></span>
                ${WIDTH_PALETTE.map((px, i) => {
                    const dot = Math.min(18, Math.max(3, px / 1.5));
                    return `<button data-wb-width="${i}" aria-label="${tt('whiteboard.width_label', 'Width')} ${px}" class="wb-tb-width w-9 h-9 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition"><span class="rounded-full bg-white block" style="width: ${dot}px; height: ${dot}px;"></span></button>`;
                }).join('')}
                <span class="w-px h-6 bg-gray-600"></span>
                <button data-wb-act="undo" aria-label="${tt('whiteboard.undo', 'Undo')}" class="wb-tb w-9 h-9 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition"><i class="ri-arrow-go-back-line"></i></button>
                <button data-wb-act="redo" aria-label="${tt('whiteboard.redo', 'Redo')}" class="wb-tb w-9 h-9 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition"><i class="ri-arrow-go-forward-line"></i></button>
                <button data-wb-act="fit" aria-label="${tt('whiteboard.fit', 'Fit view')}" class="wb-tb w-9 h-9 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition"><i class="ri-focus-3-line"></i></button>
                <button data-wb-act="clear" aria-label="${tt('whiteboard.clear', 'Clear')}" class="wb-tb w-9 h-9 rounded-full bg-red-700 hover:bg-red-600 text-white flex items-center justify-center transition"><i class="ri-delete-bin-line"></i></button>
            `;
            focusedTile.appendChild(bar);

            bar.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                if (!btn) return;
                if (btn.dataset.wbAct === 'pen') this.setTool(TOOL_PEN);
                else if (btn.dataset.wbAct === 'eraser') this.setTool(TOOL_ERASER);
                else if (btn.dataset.wbAct === 'undo') this.undo();
                else if (btn.dataset.wbAct === 'redo') this.redo();
                else if (btn.dataset.wbAct === 'fit') this._resetView();
                else if (btn.dataset.wbAct === 'clear') {
                    const msg = tt('whiteboard.clear_confirm', 'Clear the whiteboard for everyone?');
                    if (window.confirm(msg)) this.clear();
                } else if (btn.dataset.wbColor != null) this.setColor(parseInt(btn.dataset.wbColor, 10));
                else if (btn.dataset.wbWidth != null) this.setWidth(parseInt(btn.dataset.wbWidth, 10));
            });
            this._refreshToolbar();
        }

        _hideToolbar() {
            const bar = document.getElementById('wb-toolbar');
            if (bar && bar.parentNode) bar.parentNode.removeChild(bar);
        }

        _refreshToolbar() {
            const bar = document.getElementById('wb-toolbar');
            if (!bar) return;
            const setRing = (sel, on) => bar.querySelectorAll(sel).forEach(el => {
                el.classList.toggle('ring-2', on); el.classList.toggle('ring-blue-400', on);
            });
            setRing('[data-wb-act="pen"]', this._activeTool === TOOL_PEN);
            setRing('[data-wb-act="eraser"]', this._activeTool === TOOL_ERASER);
            bar.querySelectorAll('[data-wb-color]').forEach(el => {
                const on = parseInt(el.dataset.wbColor, 10) === this._color;
                el.classList.toggle('ring-2', on); el.classList.toggle('ring-white', on);
            });
            bar.querySelectorAll('[data-wb-width]').forEach(el => {
                const on = parseInt(el.dataset.wbWidth, 10) === this._width;
                el.classList.toggle('ring-2', on); el.classList.toggle('ring-blue-400', on);
            });
        }

        // ───── Pointer events (delegated, only on focused canvas) ─────

        _installDelegatedPointer() {
            // Listen at document level so we catch pointer events against
            // canvases that come and go (cloned focus tile, etc.).
            const wantsCanvas = (target) => {
                if (!target || !target.classList) return null;
                const canvas = target.classList.contains(CANVAS_CLASS) ? target : null;
                if (!canvas) return null;
                const inFocused = !!canvas.closest('#' + FOCUS_CONTAINER_ID);
                if (!inFocused) return null;
                return canvas;
            };

            // Gesture model (mobile parity):
            //   teacher + 1 pointer  → draw
            //   student + 1 pointer  → pan
            //   any peer + 2 pointers → pinch-zoom + pan around centroid
            //   wheel (desktop)      → zoom around cursor
            //
            // When a second pointer lands while drawing, we abort the
            // draft stroke and switch to pinch — same UX the mobile
            // CustomPainter version exposes.
            document.addEventListener('pointerdown', (e) => {
                if (!this._isActive) return;
                const canvas = wantsCanvas(e.target);
                if (!canvas) return;
                e.preventDefault();
                try { canvas.setPointerCapture && canvas.setPointerCapture(e.pointerId); } catch (_) {}
                this._pointers.set(e.pointerId, { x: e.clientX, y: e.clientY, canvas });

                if (this._pointers.size >= 2) {
                    // Switch to pinch — abort any in-progress draft.
                    this._abortDraftStroke();
                    this._beginPinch(canvas);
                    return;
                }

                // First pointer: teacher draws, student pans.
                if (this._canWrite) {
                    this._gestureMode = 'draw';
                    this._drawingPointerId = e.pointerId;
                    const p = this._toNormalized(canvas, e.clientX, e.clientY);
                    this._draftStroke = {
                        strokeId: (this._localIdentity || 'local') + '-' + (++this._strokeCounter),
                        tool: this._activeTool, color: this._color, width: this._width,
                        points: [p],
                    };
                } else {
                    this._gestureMode = 'pan';
                    this._panStart = {
                        tx: this._focusView.tx,
                        ty: this._focusView.ty,
                        x: e.clientX,
                        y: e.clientY,
                    };
                }
                this._requestRender();
            }, true);

            document.addEventListener('pointermove', (e) => {
                const ent = this._pointers.get(e.pointerId);
                if (!ent) return;
                ent.x = e.clientX;
                ent.y = e.clientY;

                if (this._gestureMode === 'draw' && this._drawingPointerId === e.pointerId && this._draftStroke) {
                    const p = this._toNormalized(ent.canvas, e.clientX, e.clientY);
                    const pts = this._draftStroke.points;
                    const last = pts[pts.length - 1];
                    if (!last || Math.hypot(p.x - last.x, p.y - last.y) > 0.0008) {
                        pts.push(p);
                        this._requestRender();
                    }
                } else if (this._gestureMode === 'pan' && this._panStart) {
                    this._focusView.tx = this._panStart.tx + (e.clientX - this._panStart.x);
                    this._focusView.ty = this._panStart.ty + (e.clientY - this._panStart.y);
                    this._requestRender();
                } else if (this._gestureMode === 'pinch' && this._pinchStart && this._pointers.size >= 2) {
                    this._updatePinch();
                }
            }, true);

            const endPointer = (e) => {
                this._pointers.delete(e.pointerId);
                if (this._gestureMode === 'draw' && this._drawingPointerId === e.pointerId) {
                    this._commitDraftStroke();
                    this._gestureMode = 'idle';
                    this._drawingPointerId = null;
                } else if (this._gestureMode === 'pinch' && this._pointers.size < 2) {
                    // Drop back to pan with the remaining finger, if any.
                    this._pinchStart = null;
                    if (this._pointers.size === 1) {
                        const remaining = this._pointers.values().next().value;
                        this._gestureMode = 'pan';
                        this._panStart = {
                            tx: this._focusView.tx,
                            ty: this._focusView.ty,
                            x: remaining.x,
                            y: remaining.y,
                        };
                    } else {
                        this._gestureMode = 'idle';
                    }
                } else if (this._gestureMode === 'pan' && this._pointers.size === 0) {
                    this._gestureMode = 'idle';
                    this._panStart = null;
                }
                this._requestRender();
            };
            document.addEventListener('pointerup', endPointer, true);
            document.addEventListener('pointercancel', endPointer, true);

            // Wheel = zoom around cursor (desktop). Trackpad pinch also
            // arrives as wheel events with ctrlKey set, but we treat all
            // wheel as zoom — the canvas doesn't scroll, so plain wheel
            // is unambiguous.
            document.addEventListener('wheel', (e) => {
                if (!this._isActive) return;
                const canvas = wantsCanvas(e.target);
                if (!canvas) return;
                e.preventDefault();
                const rect = canvas.getBoundingClientRect();
                const cx = e.clientX - rect.left;
                const cy = e.clientY - rect.top;
                // Negative deltaY = scroll up = zoom in.
                const factor = Math.exp(-e.deltaY * 0.0015);
                this._zoomAroundPoint(cx, cy, factor);
            }, { passive: false, capture: true });

            // Double-click on canvas resets the view (handy "fit to home").
            document.addEventListener('dblclick', (e) => {
                if (!this._isActive) return;
                const canvas = wantsCanvas(e.target);
                if (!canvas) return;
                e.preventDefault();
                this._resetView();
            }, true);
        }

        _abortDraftStroke() {
            this._draftStroke = null;
            this._drawingPointerId = null;
        }

        _commitDraftStroke() {
            const s = this._draftStroke;
            this._draftStroke = null;
            if (s && s.points.length >= 1) {
                this._strokes.set(s.strokeId, s);
                this._undoStack.push(s.strokeId);
                this._redoStack.length = 0;
                this._broadcastStroke(s);
            }
        }

        _beginPinch(canvas) {
            const arr = Array.from(this._pointers.values());
            if (arr.length < 2) return;
            const rect = canvas.getBoundingClientRect();
            const cx = ((arr[0].x + arr[1].x) / 2) - rect.left;
            const cy = ((arr[0].y + arr[1].y) / 2) - rect.top;
            const dist = Math.hypot(arr[0].x - arr[1].x, arr[0].y - arr[1].y) || 1;
            this._gestureMode = 'pinch';
            this._pinchStart = {
                dist, cx, cy,
                scale: this._focusView.scale,
                tx: this._focusView.tx,
                ty: this._focusView.ty,
            };
        }

        _updatePinch() {
            const arr = Array.from(this._pointers.values());
            if (arr.length < 2 || !this._pinchStart) return;
            const canvas = arr[0].canvas;
            const rect = canvas.getBoundingClientRect();
            const newCx = ((arr[0].x + arr[1].x) / 2) - rect.left;
            const newCy = ((arr[0].y + arr[1].y) / 2) - rect.top;
            const newDist = Math.hypot(arr[0].x - arr[1].x, arr[0].y - arr[1].y) || 1;
            // Pan component: centroid drift translates the view.
            const dx = newCx - this._pinchStart.cx;
            const dy = newCy - this._pinchStart.cy;
            // Zoom component: dist ratio scales around the ORIGINAL pinch
            // centroid (mobile feel — locked to your first two finger
            // positions instead of chasing the moving centroid).
            const rawScale = this._pinchStart.scale * (newDist / this._pinchStart.dist);
            const scale = Math.max(0.25, Math.min(5, rawScale));
            // Hold the original logical point under the start centroid.
            const logicalX = (this._pinchStart.cx - this._pinchStart.tx) / this._pinchStart.scale;
            const logicalY = (this._pinchStart.cy - this._pinchStart.ty) / this._pinchStart.scale;
            this._focusView.scale = scale;
            this._focusView.tx = this._pinchStart.cx - logicalX * scale + dx;
            this._focusView.ty = this._pinchStart.cy - logicalY * scale + dy;
            this._requestRender();
        }

        _zoomAroundPoint(cx, cy, factor) {
            const old = this._focusView;
            const rawScale = old.scale * factor;
            const scale = Math.max(0.25, Math.min(5, rawScale));
            if (scale === old.scale) return;
            // Logical coord under cursor at old scale.
            const lx = (cx - old.tx) / old.scale;
            const ly = (cy - old.ty) / old.scale;
            this._focusView.scale = scale;
            this._focusView.tx = cx - lx * scale;
            this._focusView.ty = cy - ly * scale;
            this._requestRender();
        }

        _resetView() {
            this._focusView = { tx: 0, ty: 0, scale: 1 };
            this._requestRender();
        }

        // Canvas-pixel → normalized 0..1 against canvas's home rect,
        // inverting the focus-view transform if this canvas is in focus.
        _toNormalized(canvas, clientX, clientY) {
            const rect = canvas.getBoundingClientRect();
            const px = clientX - rect.left;
            const py = clientY - rect.top;
            // If focused canvas, undo view transform first.
            const inFocused = !!canvas.closest('#' + FOCUS_CONTAINER_ID);
            let ux = px, uy = py;
            if (inFocused) {
                const v = this._focusView;
                ux = (px - v.tx) / v.scale;
                uy = (py - v.ty) / v.scale;
            }
            const home = this._homeRectFor(rect);
            return {
                x: (ux - home.x) / home.w,
                y: (uy - home.y) / home.h,
            };
        }

        _homeRectFor(rect) {
            const aspect = CANVAS_W / CANVAS_H;
            const containerAspect = rect.width / rect.height;
            let w, h, x, y;
            if (containerAspect > aspect) {
                h = rect.height; w = h * aspect;
                x = (rect.width - w) / 2; y = 0;
            } else {
                w = rect.width; h = w / aspect;
                x = 0; y = (rect.height - h) / 2;
            }
            return { x, y, w, h };
        }

        // ───── Render ─────

        _requestRender() {
            if (this._renderRaf) return;
            this._renderRaf = requestAnimationFrame(() => {
                this._renderRaf = 0;
                this._render();
            });
        }

        _render() {
            const canvases = document.querySelectorAll('canvas.' + CANVAS_CLASS);
            canvases.forEach((canvas) => this._renderToCanvas(canvas));
        }

        _renderToCanvas(canvas) {
            // Resize if needed (devicePixelRatio-aware)
            const cssRect = canvas.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            const wantW = Math.max(1, Math.floor(cssRect.width * dpr));
            const wantH = Math.max(1, Math.floor(cssRect.height * dpr));
            if (canvas.width !== wantW) canvas.width = wantW;
            if (canvas.height !== wantH) canvas.height = wantH;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const rect = { width: cssRect.width, height: cssRect.height };
            const home = this._homeRectFor(rect);
            const inFocused = !!canvas.closest('#' + FOCUS_CONTAINER_ID);
            const view = inFocused ? this._focusView : { tx: 0, ty: 0, scale: 1 };

            // CSS-pixel space + focus pan/zoom transform. Composed matrix:
            //   final = DPR · TRANSLATE(view.tx, view.ty) · SCALE(view.scale)
            // — so logical CSS coords transform straight into device pixels.
            ctx.setTransform(
                dpr * view.scale, 0,
                0, dpr * view.scale,
                dpr * view.tx, dpr * view.ty
            );

            // Paper background
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(home.x, home.y, home.w, home.h);
            ctx.strokeStyle = '#E5E7EB';
            ctx.lineWidth = 1 / view.scale;
            ctx.strokeRect(home.x, home.y, home.w, home.h);

            const sx = home.w, sy = home.h;
            const drawStroke = (s) => {
                const pts = s.points;
                if (!pts || !pts.length) return;
                const widthPx = WIDTH_PALETTE[s.width] || 4;
                const isEraser = s.tool === TOOL_ERASER;
                ctx.lineCap = 'round'; ctx.lineJoin = 'round';
                // Stroke width is in logical px (relative to home rect).
                // Divide by view.scale so visual stroke thickness stays
                // constant as the user zooms — mobile parity.
                const scale = home.h / CANVAS_H;
                ctx.lineWidth = ((isEraser ? widthPx * 2 : widthPx) * scale) / view.scale;
                ctx.strokeStyle = COLOR_PALETTE[s.color] || '#000';
                ctx.globalCompositeOperation = isEraser ? 'destination-out' : 'source-over';
                ctx.beginPath();
                ctx.moveTo(home.x + pts[0].x * sx, home.y + pts[0].y * sy);
                for (let i = 1; i < pts.length; i++) {
                    ctx.lineTo(home.x + pts[i].x * sx, home.y + pts[i].y * sy);
                }
                if (pts.length === 1) {
                    ctx.arc(home.x + pts[0].x * sx, home.y + pts[0].y * sy, ctx.lineWidth / 2, 0, Math.PI * 2);
                    if (!isEraser) ctx.fillStyle = ctx.strokeStyle;
                    ctx.fill();
                } else {
                    ctx.stroke();
                }
            };

            for (const s of this._strokes.values()) drawStroke(s);
            if (this._draftStroke) drawStroke(this._draftStroke);
            ctx.globalCompositeOperation = 'source-over';

            // Show waiting hint on student tiles when board empty. Reset
            // to identity transform first so the text size stays constant
            // regardless of zoom level.
            if (!this._canWrite && this._isWaitingForSnapshot && this._strokes.size === 0) {
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                ctx.fillStyle = 'rgba(107, 114, 128, 0.85)';
                ctx.font = `${Math.max(11, home.h * 0.04)}px system-ui, sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(
                    tt('whiteboard.waiting_for_teacher', 'Waiting for the teacher…'),
                    home.x + home.w / 2,
                    home.y + home.h / 2
                );
            }
        }
    }

    window.whiteboard = new Whiteboard();
})();
