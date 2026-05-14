/**
 * LiveKit Whiteboard Module — web parity with mobile WhiteboardCubit +
 * WhiteboardOverlay.
 *
 * Wire protocol: topic `whiteboard`, JSON UTF-8, reliable. Source of truth
 * is `itqan-mobile/lib/features/session/models/whiteboard_message.dart`.
 * Every packet carries `type`, `v: 1`, `boardId`, and writer-monotonic
 * `seq` so receivers drop stale packets.
 *
 * Coordinates are normalized 0..1 against the canvas logical size sent in
 * `wb_open` (default 1080 × 1440). Each peer letterboxes its canvas onto
 * the same logical rect so points line up across aspect ratios.
 *
 * The teacher (`canWrite=true`) owns the board: mints `boardId`, increments
 * `seq`, broadcasts `wb_stroke` / `wb_undo` / `wb_clear`, and replies to
 * `wb_snap_req` with a paced `wb_snap_begin` + N `wb_snap_stroke` +
 * `wb_snap_end` sequence. Students render-only.
 *
 * State is fully ephemeral: closing the board, reconnecting, or leaving
 * the room wipes the canvas — late joiners re-fetch via snap_req.
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

    // Index → hex. Mirrors WhiteboardColor enum in
    // itqan-mobile/lib/features/session/models/whiteboard_stroke.dart.
    // **Append-only.** Don't reorder — old peers drop unknown indices.
    const COLOR_PALETTE = [
        '#111827', // black
        '#DC2626', // red
        '#2563EB', // blue
        '#059669', // green
        '#EAB308', // yellow
        '#EA580C', // orange
        '#9333EA', // purple
    ];

    // Index → logical width in px. Mirrors WhiteboardWidth enum.
    const WIDTH_PALETTE = [2, 4, 8, 16, 28];

    const TOOL_PEN = 0;
    const TOOL_ERASER = 1;

    // Generate a short random ID — UUID-like but inline, no crypto.randomUUID
    // dependency. Used for boardId and strokeId.
    function makeId() {
        return Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
    }

    function uuid() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return makeId() + '-' + makeId();
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
            const x = Number(flat[i]);
            const y = Number(flat[i + 1]);
            if (!isFinite(x) || !isFinite(y)) continue;
            out.push({ x, y });
        }
        return out;
    }

    /**
     * Chunker — splits a stroke's points into wb_stroke packets of at most
     * MAX_CHUNK_POINTS each. A short stroke fits in one packet with
     * `done: true`. Mirrors mobile's chunker exactly.
     */
    function chunkStroke(stroke, boardId, seqStart) {
        const flat = flatten(stroke.points);
        const n = stroke.points.length;
        const chunks = Math.max(1, Math.ceil(n / MAX_CHUNK_POINTS));
        const packets = [];
        for (let k = 0; k < chunks; k++) {
            const start = k * MAX_CHUNK_POINTS * 2;
            const end = Math.min(start + MAX_CHUNK_POINTS * 2, flat.length);
            packets.push({
                type: 'wb_stroke',
                v: PROTO_VERSION,
                boardId: boardId,
                seq: seqStart + k,
                sid: stroke.strokeId,
                t: stroke.tool,
                c: stroke.color,
                w: stroke.width,
                k: k,
                ks: chunks,
                d: k === chunks - 1,
                p: flat.slice(start, end),
            });
        }
        return packets;
    }

    /**
     * Stroke assembler — buffers `wb_stroke` chunks per (boardId, sid)
     * until `done:true` arrives, then surfaces the completed stroke.
     * Entries older than 5 s are evicted on each feed.
     */
    class StrokeAssembler {
        constructor() {
            this._pending = new Map(); // key = boardId+'|'+sid → { tool, color, width, chunks: [], received, total, ts }
        }

        feed(packet) {
            const key = packet.boardId + '|' + packet.sid;
            this._evictStale();
            let entry = this._pending.get(key);
            if (!entry) {
                entry = {
                    tool: packet.t,
                    color: packet.c,
                    width: packet.w,
                    chunks: new Array(packet.ks).fill(null),
                    received: 0,
                    total: packet.ks,
                    ts: nowMs(),
                };
                this._pending.set(key, entry);
            }
            if (packet.k < 0 || packet.k >= entry.total) return null;
            if (entry.chunks[packet.k] !== null) return null; // duplicate
            entry.chunks[packet.k] = packet.p;
            entry.received++;
            entry.ts = nowMs();
            if (entry.received < entry.total || !packet.d) return null;

            this._pending.delete(key);
            let flat = [];
            for (const chunk of entry.chunks) {
                if (!chunk) return null;
                flat = flat.concat(chunk);
            }
            return {
                strokeId: packet.sid,
                tool: entry.tool,
                color: entry.color,
                width: entry.width,
                points: unflatten(flat),
            };
        }

        _evictStale() {
            const cutoff = nowMs() - ASSEMBLER_TIMEOUT_MS;
            for (const [key, entry] of this._pending) {
                if (entry.ts < cutoff) this._pending.delete(key);
            }
        }
    }

    class Whiteboard {
        constructor() {
            this._room = null;
            this._canWrite = false;
            this._localIdentity = null;
            this._opts = {};
            this._initialized = false;
            this._destroyed = false;

            // Board state
            this._isActive = false;
            this._boardId = null;
            this._isWaitingForSnapshot = false;
            this._strokes = new Map(); // strokeId → Stroke (ordered insert)
            this._undoStack = [];      // strokeId[]
            this._redoStack = [];      // Stroke[]
            this._draftStroke = null;
            this._seq = 0;
            this._strokeCounter = 0;
            this._lastSeqByBoard = new Map(); // boardId → highest seq seen

            // Tools
            this._activeTool = TOOL_PEN;
            this._color = 0; // black
            this._width = 1; // medium

            // Viewport (local only — never broadcast)
            this._view = { tx: 0, ty: 0, scale: 1 };

            // Wire helpers
            this._assembler = new StrokeAssembler();
            this._pendingSnapshot = null; // { boardId, strokes: [] }

            // Timers
            this._snapReqTimers = [];
            this._snapReplyTimer = null;
            this._snapshotRequesters = new Set();
            this._renderRaf = 0;

            // Pointer gesture state
            this._pointers = new Map(); // pointerId → { x, y }
            this._gesture = null; // null | 'draw' | 'pan' | 'pinch'
            this._panStart = null;
            this._pinchStart = null;
            this._encoder = new TextEncoder();
            this._decoder = new TextDecoder();
        }

        // -------- Public API --------

        init(room, canWrite, opts) {
            if (this._initialized) return;
            this._room = room;
            this._canWrite = !!canWrite;
            this._opts = opts || {};
            this._localIdentity = (opts && opts.localIdentity) || (room && room.localParticipant && room.localParticipant.identity) || null;
            this._initialized = true;

            this._installCanvas();
            this._installToolbar();
            this._installButtons();

            // Students that join late ask the teacher for a snapshot.
            // Teachers don't snap_req themselves.
            if (!this._canWrite) {
                this._scheduleSnapReq();
            }
        }

        destroy() {
            if (this._destroyed) return;
            this._destroyed = true;
            this._clearSnapReqTimers();
            if (this._snapReplyTimer) { clearTimeout(this._snapReplyTimer); this._snapReplyTimer = null; }
            if (this._renderRaf) { cancelAnimationFrame(this._renderRaf); this._renderRaf = 0; }
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._snapshotRequesters.clear();
        }

        toggle() {
            if (!this._canWrite) return;
            if (this._isActive) this.close();
            else this.open();
        }

        open() {
            if (!this._canWrite) return;
            if (this._isActive) {
                this._showOverlay();
                return;
            }
            this._boardId = uuid();
            this._seq = 0;
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._isActive = true;
            this._publish({
                type: 'wb_open',
                v: PROTO_VERSION,
                boardId: this._boardId,
                seq: this._nextSeq(),
                cw: CANVAS_W,
                ch: CANVAS_H,
            });
            this._showOverlay();
            this._requestRender();
        }

        close() {
            if (!this._canWrite) return;
            if (!this._isActive) {
                this._hideOverlay();
                return;
            }
            this._publish({
                type: 'wb_close',
                v: PROTO_VERSION,
                boardId: this._boardId,
                seq: this._nextSeq(),
            });
            this._isActive = false;
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._draftStroke = null;
            this._hideOverlay();
            this._requestRender();
        }

        clear() {
            if (!this._canWrite || !this._isActive) return;
            this._publish({
                type: 'wb_clear',
                v: PROTO_VERSION,
                boardId: this._boardId,
                seq: this._nextSeq(),
            });
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._requestRender();
        }

        undo() {
            if (!this._canWrite || !this._isActive) return;
            if (this._undoStack.length === 0) return;
            const sid = this._undoStack.pop();
            const stroke = this._strokes.get(sid);
            if (!stroke) return;
            this._strokes.delete(sid);
            this._redoStack.push(stroke);
            this._publish({
                type: 'wb_undo',
                v: PROTO_VERSION,
                boardId: this._boardId,
                seq: this._nextSeq(),
                sid: sid,
            });
            this._requestRender();
        }

        redo() {
            if (!this._canWrite || !this._isActive) return;
            if (this._redoStack.length === 0) return;
            const stroke = this._redoStack.pop();
            this._strokes.set(stroke.strokeId, stroke);
            this._undoStack.push(stroke.strokeId);
            this._broadcastStroke(stroke);
            this._requestRender();
        }

        setTool(tool) {
            this._activeTool = (tool === TOOL_ERASER) ? TOOL_ERASER : TOOL_PEN;
            this._refreshToolbarActive();
        }
        setColor(idx) {
            if (idx < 0 || idx >= COLOR_PALETTE.length) return;
            this._color = idx;
            this._refreshToolbarActive();
        }
        setWidth(idx) {
            if (idx < 0 || idx >= WIDTH_PALETTE.length) return;
            this._width = idx;
            this._refreshToolbarActive();
        }
        fitView() {
            this._view = { tx: 0, ty: 0, scale: 1 };
            this._requestRender();
        }

        // -------- Inbound packet routing --------

        onPacket(payload, participant) {
            if (!payload || !payload.length) return;
            let data;
            try {
                data = JSON.parse(this._decoder.decode(payload));
            } catch (_) { return; }
            if (!data || data.v !== PROTO_VERSION) return;

            const senderId = participant && participant.identity;
            switch (data.type) {
                case 'wb_open':     this._onOpen(data); break;
                case 'wb_close':    this._onClose(data); break;
                case 'wb_stroke':   this._onStroke(data); break;
                case 'wb_undo':     this._onUndo(data); break;
                case 'wb_clear':    this._onClear(data); break;
                case 'wb_snap_req': this._onSnapReq(senderId); break;
                case 'wb_snap_begin':  this._onSnapBegin(data); break;
                case 'wb_snap_stroke': this._onSnapStroke(data); break;
                case 'wb_snap_end':    this._onSnapEnd(data); break;
            }
        }

        _onOpen(data) {
            // Teacher's open packet — students adopt this boardId and clear
            // any in-flight snap_req timers. Teachers ignore foreign opens
            // (only one teacher in the room).
            if (this._canWrite) return;
            this._boardId = data.boardId;
            this._isActive = true;
            this._strokes.clear();
            this._undoStack.length = 0;
            this._redoStack.length = 0;
            this._isWaitingForSnapshot = false;
            this._clearSnapReqTimers();
            this._lastSeqByBoard.set(data.boardId, data.seq);
            this._showOverlay();
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
            this._hideOverlay();
            this._requestRender();
        }

        _onStroke(data) {
            if (this._canWrite) return;
            if (data.boardId !== this._boardId) return;
            if (this._isSeqStale(data)) return;
            const stroke = this._assembler.feed(data);
            if (!stroke) return;
            this._strokes.set(stroke.strokeId, stroke);
            this._undoStack.push(stroke.strokeId);
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
            // Only the teacher answers snap_reqs.
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
            this._showOverlay();
            this._requestRender();
        }

        _onSnapStroke(data) {
            if (this._canWrite) return;
            if (!this._pendingSnapshot || this._pendingSnapshot.boardId !== data.boardId) return;
            const raw = data.s;
            if (!raw) return;
            const stroke = {
                strokeId: raw.id,
                tool: Number(raw.t) || 0,
                color: Number(raw.c) || 0,
                width: Number(raw.w) || 0,
                points: unflatten(raw.p || []),
            };
            this._pendingSnapshot.strokes.push(stroke);
        }

        _onSnapEnd(data) {
            if (this._canWrite) return;
            if (!this._pendingSnapshot || this._pendingSnapshot.boardId !== data.boardId) return;
            // Atomic swap
            this._strokes.clear();
            this._undoStack.length = 0;
            for (const s of this._pendingSnapshot.strokes) {
                this._strokes.set(s.strokeId, s);
                this._undoStack.push(s.strokeId);
            }
            this._pendingSnapshot = null;
            this._isWaitingForSnapshot = false;
            this._lastSeqByBoard.set(data.boardId, data.seq);
            this._refreshWaitOverlay();
            this._requestRender();
        }

        _isSeqStale(data) {
            const last = this._lastSeqByBoard.get(data.boardId) || 0;
            if (data.seq < last) return true;
            this._lastSeqByBoard.set(data.boardId, data.seq);
            return false;
        }

        // -------- Wire I/O --------

        _publish(obj, opts) {
            if (!this._room || !this._room.localParticipant) return;
            const bytes = this._encoder.encode(JSON.stringify(obj));
            const options = { reliable: true, topic: 'whiteboard' };
            if (opts && opts.to && opts.to.length) options.destinationIdentities = opts.to;
            try {
                // LiveKit JS SDK accepts an options bag with `topic` and
                // `destinationIdentities`. The room callback echoes the
                // same topic on the receiving side.
                this._room.localParticipant.publishData(bytes, options);
            } catch (e) {
                if (window.MT && window.MT.warn) window.MT.warn('whiteboard', 'publish_failed', { err: String(e) });
            }
        }

        _nextSeq() {
            return ++this._seq;
        }

        _broadcastStroke(stroke) {
            const startSeq = this._seq + 1;
            const packets = chunkStroke(stroke, this._boardId, startSeq);
            this._seq += packets.length;
            for (const p of packets) this._publish(p);
        }

        _scheduleSnapReq() {
            this._clearSnapReqTimers();
            this._isWaitingForSnapshot = true;
            this._refreshWaitOverlay();
            for (const delay of SNAP_RETRY_DELAYS_MS) {
                const t = setTimeout(() => {
                    if (this._destroyed) return;
                    if (this._isActive && !this._isWaitingForSnapshot) return;
                    if (this._strokes.size > 0) return;
                    this._publish({ type: 'wb_snap_req', v: PROTO_VERSION });
                }, delay);
                this._snapReqTimers.push(t);
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
                type: 'wb_snap_begin',
                v: PROTO_VERSION,
                boardId: this._boardId,
                seq: this._nextSeq(),
                n: strokes.length,
                cw: CANVAS_W,
                ch: CANVAS_H,
            }, { to });

            // Pace the stroke packets: SNAP_REPLY_BATCH per interval to stay
            // under LiveKit's 15 KiB/packet and avoid bursting the channel.
            let i = 0;
            const sendBatch = () => {
                if (this._destroyed) return;
                const end = Math.min(i + SNAP_REPLY_BATCH, strokes.length);
                for (; i < end; i++) {
                    const s = strokes[i];
                    this._publish({
                        type: 'wb_snap_stroke',
                        v: PROTO_VERSION,
                        boardId: this._boardId,
                        seq: this._nextSeq(),
                        s: {
                            id: s.strokeId,
                            t: s.tool,
                            c: s.color,
                            w: s.width,
                            p: flatten(s.points),
                        },
                    }, { to });
                }
                if (i < strokes.length) {
                    setTimeout(sendBatch, SNAP_REPLY_INTERVAL_MS);
                } else {
                    this._publish({
                        type: 'wb_snap_end',
                        v: PROTO_VERSION,
                        boardId: this._boardId,
                        seq: this._nextSeq(),
                    }, { to });
                }
            };
            sendBatch();
        }

        // -------- DOM / Canvas --------

        _installCanvas() {
            this._overlay = document.getElementById('whiteboardOverlay');
            this._canvas = document.getElementById('whiteboardCanvas');
            this._waitBanner = document.getElementById('whiteboardWaitBanner');
            if (!this._overlay || !this._canvas) return;
            this._ctx = this._canvas.getContext('2d');
            this._resizeObserver = new ResizeObserver(() => this._resizeCanvas());
            this._resizeObserver.observe(this._canvas);
            this._resizeCanvas();

            // Pointer handlers — mirror mobile's raw-listener style so the
            // browser doesn't slop to scroll/zoom mid-stroke. touch-action:none
            // is also set in CSS on the canvas.
            const c = this._canvas;
            c.addEventListener('pointerdown', (e) => this._onPointerDown(e));
            c.addEventListener('pointermove', (e) => this._onPointerMove(e));
            c.addEventListener('pointerup', (e) => this._onPointerUp(e));
            c.addEventListener('pointercancel', (e) => this._onPointerUp(e));
            c.addEventListener('wheel', (e) => this._onWheel(e), { passive: false });
        }

        _resizeCanvas() {
            if (!this._canvas) return;
            const rect = this._canvas.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            this._canvas.width = Math.max(1, Math.floor(rect.width * dpr));
            this._canvas.height = Math.max(1, Math.floor(rect.height * dpr));
            this._dpr = dpr;
            this._requestRender();
        }

        _installToolbar() {
            this._toolbar = document.getElementById('whiteboardToolbar');
            if (!this._toolbar) return;
            // Read-only students: hide write-only buttons. Their canvas is
            // still rendered (snapshot) but no interactive controls.
            if (!this._canWrite) {
                this._toolbar.style.display = 'none';
            }
            this._refreshToolbarActive();
        }

        _installButtons() {
            if (!this._canWrite) return;
            const bindClick = (id, fn) => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('click', fn);
            };
            bindClick('wbTool-pen',     () => this.setTool(TOOL_PEN));
            bindClick('wbTool-eraser',  () => this.setTool(TOOL_ERASER));
            bindClick('wbUndo',         () => this.undo());
            bindClick('wbRedo',         () => this.redo());
            bindClick('wbFit',          () => this.fitView());
            bindClick('wbClose',        () => this.close());
            bindClick('wbClear', () => {
                const msg = (typeof t === 'function') ? t('whiteboard.clear_confirm') : 'Clear the whiteboard?';
                if (window.confirm(msg)) this.clear();
            });
            // Colour swatches: data-wb-color="<idx>"
            this._toolbar?.querySelectorAll('[data-wb-color]').forEach((btn) => {
                btn.addEventListener('click', () => this.setColor(parseInt(btn.dataset.wbColor, 10)));
            });
            // Width buttons: data-wb-width="<idx>"
            this._toolbar?.querySelectorAll('[data-wb-width]').forEach((btn) => {
                btn.addEventListener('click', () => this.setWidth(parseInt(btn.dataset.wbWidth, 10)));
            });
        }

        _refreshToolbarActive() {
            if (!this._toolbar) return;
            const setActive = (sel, isActive) => {
                this._toolbar.querySelectorAll(sel).forEach((el) => {
                    el.classList.toggle('ring-2', isActive);
                    el.classList.toggle('ring-blue-400', isActive);
                });
            };
            setActive(`#wbTool-pen`, this._activeTool === TOOL_PEN);
            setActive(`#wbTool-eraser`, this._activeTool === TOOL_ERASER);
            this._toolbar.querySelectorAll('[data-wb-color]').forEach((el) => {
                const idx = parseInt(el.dataset.wbColor, 10);
                el.classList.toggle('ring-2', idx === this._color);
                el.classList.toggle('ring-white', idx === this._color);
            });
            this._toolbar.querySelectorAll('[data-wb-width]').forEach((el) => {
                const idx = parseInt(el.dataset.wbWidth, 10);
                el.classList.toggle('ring-2', idx === this._width);
                el.classList.toggle('ring-blue-400', idx === this._width);
            });
        }

        _showOverlay() {
            if (!this._overlay) return;
            this._overlay.classList.remove('hidden');
            this._refreshWaitOverlay();
            // Need a paint after the element becomes visible so getBoundingClientRect
            // returns the right size.
            requestAnimationFrame(() => this._resizeCanvas());
        }

        _hideOverlay() {
            if (!this._overlay) return;
            this._overlay.classList.add('hidden');
        }

        _refreshWaitOverlay() {
            if (!this._waitBanner) return;
            this._waitBanner.classList.toggle('hidden', !this._isWaitingForSnapshot || this._strokes.size > 0);
        }

        // -------- Gestures --------

        _onPointerDown(e) {
            if (!this._canvas) return;
            this._canvas.setPointerCapture && this._canvas.setPointerCapture(e.pointerId);
            this._pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            const count = this._pointers.size;
            if (count === 2) {
                // Switch to pinch immediately; abandon any draft stroke.
                this._draftStroke = null;
                this._gesture = 'pinch';
                const arr = Array.from(this._pointers.values());
                const dx = arr[0].x - arr[1].x;
                const dy = arr[0].y - arr[1].y;
                this._pinchStart = {
                    dist: Math.hypot(dx, dy) || 1,
                    cx: (arr[0].x + arr[1].x) / 2,
                    cy: (arr[0].y + arr[1].y) / 2,
                    scale: this._view.scale,
                    tx: this._view.tx,
                    ty: this._view.ty,
                };
                return;
            }
            if (this._canWrite && this._isActive) {
                // Draw
                this._gesture = 'draw';
                const p = this._toCanvas(e.clientX, e.clientY);
                const norm = this._toNormalized(p);
                this._draftStroke = {
                    strokeId: this._localIdentity + '-' + (++this._strokeCounter),
                    tool: this._activeTool,
                    color: this._color,
                    width: this._width,
                    points: [norm],
                };
                this._requestRender();
            } else {
                // Pan
                this._gesture = 'pan';
                this._panStart = { x: e.clientX, y: e.clientY, tx: this._view.tx, ty: this._view.ty };
            }
        }

        _onPointerMove(e) {
            if (!this._pointers.has(e.pointerId)) return;
            this._pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (this._gesture === 'draw' && this._draftStroke) {
                const p = this._toCanvas(e.clientX, e.clientY);
                const norm = this._toNormalized(p);
                const pts = this._draftStroke.points;
                const last = pts[pts.length - 1];
                if (!last || Math.hypot(norm.x - last.x, norm.y - last.y) > 0.0008) {
                    pts.push(norm);
                    this._requestRender();
                }
            } else if (this._gesture === 'pan' && this._panStart) {
                this._view.tx = this._panStart.tx + (e.clientX - this._panStart.x);
                this._view.ty = this._panStart.ty + (e.clientY - this._panStart.y);
                this._requestRender();
            } else if (this._gesture === 'pinch' && this._pinchStart && this._pointers.size === 2) {
                const arr = Array.from(this._pointers.values());
                const dx = arr[0].x - arr[1].x;
                const dy = arr[0].y - arr[1].y;
                const dist = Math.hypot(dx, dy) || 1;
                let scale = this._pinchStart.scale * (dist / this._pinchStart.dist);
                scale = Math.max(0.25, Math.min(5, scale));
                this._view.scale = scale;
                this._requestRender();
            }
        }

        _onPointerUp(e) {
            this._pointers.delete(e.pointerId);
            if (this._gesture === 'draw' && this._draftStroke) {
                const s = this._draftStroke;
                this._draftStroke = null;
                if (s.points.length >= 1) {
                    this._strokes.set(s.strokeId, s);
                    this._undoStack.push(s.strokeId);
                    this._redoStack.length = 0;
                    this._broadcastStroke(s);
                }
                this._requestRender();
            }
            if (this._pointers.size === 0) {
                this._gesture = null;
                this._panStart = null;
                this._pinchStart = null;
            }
        }

        _onWheel(e) {
            e.preventDefault();
            const rect = this._canvas.getBoundingClientRect();
            const cx = e.clientX - rect.left;
            const cy = e.clientY - rect.top;
            const factor = Math.exp(-e.deltaY * 0.001);
            const newScale = Math.max(0.25, Math.min(5, this._view.scale * factor));
            // Keep the world point under the cursor stationary.
            const wx = (cx - this._view.tx) / this._view.scale;
            const wy = (cy - this._view.ty) / this._view.scale;
            this._view.scale = newScale;
            this._view.tx = cx - wx * newScale;
            this._view.ty = cy - wy * newScale;
            this._requestRender();
        }

        _toCanvas(clientX, clientY) {
            const rect = this._canvas.getBoundingClientRect();
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        // Convert a canvas-pixel point to normalized 0..1 by inverting the
        // view transform AND the letterbox transform onto the logical 1080x1440
        // canvas. Off-home points may fall outside [0,1] — that's allowed and
        // mirrors the mobile path.
        _toNormalized(p) {
            const homeRect = this._homeRect();
            // Undo view transform first.
            const wx = (p.x - this._view.tx) / this._view.scale;
            const wy = (p.y - this._view.ty) / this._view.scale;
            return {
                x: (wx - homeRect.x) / homeRect.w,
                y: (wy - homeRect.y) / homeRect.h,
            };
        }

        // The letterboxed "home" rectangle in CSS canvas pixels — where
        // [0,0]→[1,1] normalized maps to.
        _homeRect() {
            const rect = this._canvas.getBoundingClientRect();
            const aspect = CANVAS_W / CANVAS_H;
            const containerAspect = rect.width / rect.height;
            let w, h, x, y;
            if (containerAspect > aspect) {
                h = rect.height;
                w = h * aspect;
                x = (rect.width - w) / 2;
                y = 0;
            } else {
                w = rect.width;
                h = w / aspect;
                x = 0;
                y = (rect.height - h) / 2;
            }
            return { x, y, w, h };
        }

        // -------- Render --------

        _requestRender() {
            if (this._renderRaf) return;
            this._renderRaf = requestAnimationFrame(() => {
                this._renderRaf = 0;
                this._render();
            });
        }

        _render() {
            if (!this._ctx || !this._canvas) return;
            const ctx = this._ctx;
            const dpr = this._dpr || 1;
            const w = this._canvas.width;
            const h = this._canvas.height;
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.clearRect(0, 0, w, h);
            // CSS-pixel space
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

            // Home rectangle background — pale paper to mirror the mobile look.
            const home = this._homeRect();
            ctx.save();
            ctx.translate(this._view.tx, this._view.ty);
            ctx.scale(this._view.scale, this._view.scale);
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(home.x, home.y, home.w, home.h);
            ctx.strokeStyle = '#E5E7EB';
            ctx.lineWidth = 1 / this._view.scale;
            ctx.strokeRect(home.x, home.y, home.w, home.h);

            // Strokes (insertion order) + draft on top.
            const sx = home.w; // CSS px per normalized unit
            const sy = home.h;
            const drawStroke = (s) => {
                const pts = s.points;
                if (!pts || !pts.length) return;
                const widthPx = WIDTH_PALETTE[s.width] || 4;
                const isEraser = s.tool === TOOL_ERASER;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.lineWidth = (isEraser ? widthPx * 2 : widthPx) / this._view.scale;
                ctx.strokeStyle = COLOR_PALETTE[s.color] || '#000';
                ctx.globalCompositeOperation = isEraser ? 'destination-out' : 'source-over';
                ctx.beginPath();
                ctx.moveTo(home.x + pts[0].x * sx, home.y + pts[0].y * sy);
                for (let i = 1; i < pts.length; i++) {
                    ctx.lineTo(home.x + pts[i].x * sx, home.y + pts[i].y * sy);
                }
                if (pts.length === 1) {
                    // Single tap: small filled dot.
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
            ctx.restore();
        }
    }

    window.whiteboard = new Whiteboard();
})();
