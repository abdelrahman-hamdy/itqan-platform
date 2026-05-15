/**
 * LiveKit Mushaf Module — virtual-participant tile model (mobile parity).
 *
 * The mushaf renders as a tile inside the existing #videoGrid (like a
 * regular participant). Clicking the tile triggers the meeting's focus
 * mode — the same UX as clicking any other participant. Floating page-nav
 * controls appear next to the focused tile for the teacher.
 *
 * Wire protocol (frozen, byte-parity with mobile):
 *   topic 'mushaf', JSON UTF-8, reliable
 *   Source of truth: itqan-mobile/lib/features/mushaf/sync/mushaf_sync_message.dart
 *
 * Page rendering: each tile/focused-tile renders the same DOM (HTML text,
 * Amiri-font Arabic). Unlike the whiteboard the focus-mode `cloneNode(true)`
 * works perfectly here because there's no canvas to copy — the cloned
 * subtree paints identically to the original via CSS.
 *
 * Content fallbacks: ideal rendering is the KFGQPC QPC v4 page font with
 * per-page glyph strings. If that data isn't bundled (see
 * resources/js/mushaf/quran-data.js + storage/app/public/mushaf/fonts/),
 * we fall back to: server-side `/mushaf/page/{n}.json` if available, then
 * to a `surahs[]` lookup that at least shows "Page N — Surah X (verses
 * Y..Z)". State is fully ephemeral.
 */
(function () {
    'use strict';

    const PROTO_VERSION = 1;
    const SNAP_RETRY_DELAYS_MS = [1000, 3000, 7000, 15000];
    const SNAPSHOT_COALESCE_MS = 250;
    const HEARTBEAT_MS = 4000;
    const FONT_LRU_SIZE = 10;
    const MIN_PAGE = 1;
    const MAX_PAGE = 604;
    const TILE_ID = 'participant-mushaf';
    const TILE_CONTENT_CLASS = 'mushaf-page';
    const FOCUS_CONTAINER_ID = 'focusedVideoContainer';

    function uuid() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
        return Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
    }
    function tt(key, fallback) {
        if (typeof window.t === 'function') {
            const v = window.t(key);
            if (v && v !== key) return v;
        }
        return fallback;
    }
    function clampPage(p) {
        return Math.max(MIN_PAGE, Math.min(MAX_PAGE, Number(p) || 1));
    }

    class Mushaf {
        constructor() {
            this._room = null;
            this._canShare = false;
            this._localIdentity = null;
            this._initialized = false;
            this._destroyed = false;

            this._isOpen = false;
            this._page = 1;
            this._highlight = null;

            this._instanceId = null;
            this._seq = 0;
            this._isShared = false;
            this._isFollowing = false;
            this._lastSeqByInstance = new Map();

            this._snapReqTimers = [];
            this._heartbeatTimer = null;
            this._snapReplyTimer = null;
            this._snapshotRequesters = new Set();

            this._fontLru = [];
            this._fontPromises = new Map();
            this._pageDataCache = new Map(); // page → { surah, ayah_from, ayah_to, ... }
            this._renderGen = 0; // bumped on every nav; stale renders bail

            this._encoder = new TextEncoder();
            this._decoder = new TextDecoder();
            this._focusObserver = null;
        }

        // ───── Public API ─────

        init(room, canShare, opts) {
            if (this._initialized) return;
            this._room = room;
            this._canShare = !!canShare;
            this._localIdentity = (opts && opts.localIdentity) || (room && room.localParticipant && room.localParticipant.identity) || null;
            this._initialized = true;

            this._observeFocusMode();
            this._installHighlightHandler();

            if (!this._canShare) this._scheduleSnapReq();
        }

        /**
         * Teacher-only: tap on any ayah word in the FOCUSED mushaf view
         * to toggle a verse highlight. Same UX role as mobile's
         * onAyahLongPress → MushafReaderCubit.setHighlight. We use single
         * click here because mobile uses long-press only to disambiguate
         * from scroll — on web the page doesn't scroll under the finger.
         *
         * Clicking an already-highlighted ayah CLEARS the highlight
         * (mobile would do this via a separate gesture, but toggle is the
         * standard web pattern). Inbound m_hl from any other path is
         * unaffected.
         *
         * Delegated at document level so it works against the cloned
         * focus-mode tile without per-word listeners — same trick the
         * whiteboard uses for its pointer events.
         */
        _installHighlightHandler() {
            if (!this._canShare) return;
            document.addEventListener('click', (e) => {
                if (!this._canShare || !this._isOpen) return;
                const target = e.target;
                if (!target || !target.closest) return;
                const word = target.closest('.mushaf-word');
                if (!word) return;
                // Only the focused-mode clone registers highlight taps.
                // The thumbnail tile is too small for precise targeting
                // and we want highlight UX to live in the same view that
                // shows the toolbar.
                if (!word.closest('#' + FOCUS_CONTAINER_ID)) return;
                e.preventDefault();
                e.stopPropagation();
                const s = Number(word.dataset.surah);
                const a = Number(word.dataset.ayah);
                if (!s || !a) return;
                // Toggle: re-tap clears.
                if (this._highlight && this._highlight.s === s && this._highlight.a === a) {
                    this.setHighlight(null, null);
                } else {
                    this.setHighlight(s, a);
                }
            }, true);
        }

        destroy() {
            if (this._destroyed) return;
            this._destroyed = true;
            this._clearSnapReqTimers();
            if (this._heartbeatTimer) { clearInterval(this._heartbeatTimer); this._heartbeatTimer = null; }
            if (this._snapReplyTimer) { clearTimeout(this._snapReplyTimer); this._snapReplyTimer = null; }
            if (this._focusObserver) { this._focusObserver.disconnect(); this._focusObserver = null; }
            this._removeTile();
            this._snapshotRequesters.clear();
        }

        toggle() {
            if (this._isOpen) this.close();
            else this.open();
        }

        open() {
            this._isOpen = true;
            this._ensureTile();
            if (this._canShare) this._startSharing();
            this._renderTile();
        }

        close() {
            this._isOpen = false;
            if (this._canShare && this._isShared) this._stopSharing();
            this._removeTile();
        }

        goToPage(page) {
            // Students cannot navigate locally — the mushaf is read-only
            // for them. They only follow the teacher's published pages.
            if (!this._canShare) return;
            const p = clampPage(page);
            if (p === this._page) return;
            this._page = p;
            this._highlight = null;
            this._renderTile();
            if (this._isShared) {
                this._publish({
                    type: 'm_nav', v: PROTO_VERSION,
                    id: this._instanceId, seq: this._nextSeq(),
                    p: this._page,
                });
            }
        }

        setHighlight(surah, ayah) {
            if (surah == null || ayah == null) this._highlight = null;
            else this._highlight = { s: Number(surah), a: Number(ayah) };
            this._renderHighlight();
            if (this._canShare && this._isShared) {
                const packet = {
                    type: 'm_hl', v: PROTO_VERSION,
                    id: this._instanceId, seq: this._nextSeq(),
                };
                if (this._highlight) packet.h = this._highlight;
                this._publish(packet);
            }
        }

        // ───── Inbound packet routing ─────

        onPacket(payload, participant) {
            if (!payload || !payload.length) return;
            let data; try { data = JSON.parse(this._decoder.decode(payload)); } catch (_) { return; }
            if (!data || data.v !== PROTO_VERSION) return;
            const senderId = participant && participant.identity;
            switch (data.type) {
                case 'm_open':     this._onOpen(data); break;
                case 'm_close':    this._onClose(data); break;
                case 'm_nav':      this._onNav(data); break;
                case 'm_hl':       this._onHl(data); break;
                case 'm_snap_req': this._onSnapReq(senderId); break;
                case 'm_snap':     this._onSnap(data); break;
            }
        }

        _onOpen(data) {
            if (this._canShare) return;
            const isNewInstance = this._instanceId !== data.id;
            this._instanceId = data.id;
            this._isFollowing = true;
            this._clearSnapReqTimers();
            const prevSeq = this._lastSeqByInstance.get(data.id) || 0;
            if (data.seq < prevSeq) return;
            this._lastSeqByInstance.set(data.id, data.seq);
            this._isOpen = true;
            this._ensureTile();
            if (isNewInstance || this._page !== data.p) {
                this._page = data.p;
                this._renderTile();
            }
            this._highlight = data.h || null;
            this._renderHighlight();
        }

        _onClose(data) {
            if (this._canShare) return;
            if (data.id !== this._instanceId) return;
            this._isFollowing = false;
            this._instanceId = null;
            this._highlight = null;
            this._isOpen = false;
            this._removeTile();
        }

        _onNav(data) {
            if (this._canShare) return;
            if (data.id !== this._instanceId) return;
            if (this._isSeqStale(data)) return;
            this._page = data.p;
            this._highlight = null;
            this._ensureTile();
            this._renderTile();
        }

        _onHl(data) {
            if (this._canShare) return;
            if (data.id !== this._instanceId) return;
            if (this._isSeqStale(data)) return;
            this._highlight = data.h || null;
            this._renderHighlight();
        }

        _onSnapReq(senderId) {
            if (!this._canShare || !this._isShared || !senderId) return;
            this._snapshotRequesters.add(senderId);
            if (this._snapReplyTimer) return;
            this._snapReplyTimer = setTimeout(() => {
                this._snapReplyTimer = null;
                this._sendSnapshot();
            }, SNAPSHOT_COALESCE_MS);
        }

        _onSnap(data) {
            if (this._canShare) return;
            this._instanceId = data.id;
            this._isFollowing = true;
            this._clearSnapReqTimers();
            this._lastSeqByInstance.set(data.id, data.seq);
            this._page = data.p;
            this._highlight = data.h || null;
            this._isOpen = true;
            this._ensureTile();
            this._renderTile();
        }

        _isSeqStale(data) {
            const last = this._lastSeqByInstance.get(data.id) || 0;
            if (data.seq < last) return true;
            this._lastSeqByInstance.set(data.id, data.seq);
            return false;
        }

        // ───── Sharing (teacher) ─────

        _startSharing() {
            if (this._isShared) return;
            this._instanceId = uuid();
            this._seq = 0;
            this._isShared = true;
            this._publish(this._currentOpenPacket());
            if (this._heartbeatTimer) clearInterval(this._heartbeatTimer);
            this._heartbeatTimer = setInterval(() => {
                if (!this._isShared) return;
                this._publish(this._currentOpenPacket());
            }, HEARTBEAT_MS);
        }

        _stopSharing() {
            if (!this._isShared) return;
            this._publish({
                type: 'm_close', v: PROTO_VERSION,
                id: this._instanceId, seq: this._nextSeq(),
            });
            this._isShared = false;
            if (this._heartbeatTimer) { clearInterval(this._heartbeatTimer); this._heartbeatTimer = null; }
        }

        _currentOpenPacket() {
            const p = {
                type: 'm_open', v: PROTO_VERSION,
                id: this._instanceId, seq: this._nextSeq(),
                p: this._page,
            };
            if (this._highlight) p.h = this._highlight;
            return p;
        }

        _sendSnapshot() {
            const to = Array.from(this._snapshotRequesters);
            this._snapshotRequesters.clear();
            if (!to.length || !this._isShared) return;
            const packet = {
                type: 'm_snap', v: PROTO_VERSION,
                id: this._instanceId, seq: this._nextSeq(),
                p: this._page,
            };
            if (this._highlight) packet.h = this._highlight;
            this._publish(packet, { to });
        }

        // ───── Wire I/O ─────

        _publish(obj, opts) {
            if (!this._room || !this._room.localParticipant) return;
            const bytes = this._encoder.encode(JSON.stringify(obj));
            const options = { reliable: true, topic: 'mushaf' };
            if (opts && opts.to && opts.to.length) options.destinationIdentities = opts.to;
            try {
                this._room.localParticipant.publishData(bytes, options);
            } catch (e) {
                if (window.MT?.warn) window.MT.warn('mushaf', 'publish_failed', { err: String(e) });
            }
        }

        _nextSeq() { return ++this._seq; }

        _scheduleSnapReq() {
            this._clearSnapReqTimers();
            for (const delay of SNAP_RETRY_DELAYS_MS) {
                const tm = setTimeout(() => {
                    if (this._destroyed) return;
                    if (this._isFollowing) return;
                    this._publish({ type: 'm_snap_req', v: PROTO_VERSION });
                }, delay);
                this._snapReqTimers.push(tm);
            }
        }
        _clearSnapReqTimers() {
            for (const t of this._snapReqTimers) clearTimeout(t);
            this._snapReqTimers.length = 0;
        }

        // ───── Tile lifecycle ─────

        _ensureTile() {
            if (document.getElementById(TILE_ID)) return;
            const grid = document.getElementById('videoGrid');
            if (!grid) return;

            const tile = document.createElement('div');
            tile.id = TILE_ID;
            tile.className = 'participant-video relative bg-stone-50 rounded-lg overflow-hidden aspect-video w-full h-full group';
            tile.dataset.participantId = 'mushaf';
            tile.dataset.isVirtual = 'true';
            tile.style.direction = 'rtl';

            // Page content host. Cloned into focus mode unchanged.
            const page = document.createElement('div');
            page.className = TILE_CONTENT_CLASS + ' absolute inset-0 flex items-center justify-center text-center px-4 py-2 overflow-hidden';
            page.style.fontFamily = "'Amiri', serif";
            tile.appendChild(page);

            // Bottom-left label pill
            const label = document.createElement('div');
            label.className = 'absolute bottom-2 left-2 z-20 pointer-events-none';
            label.innerHTML = `
                <div class="flex items-center gap-2 bg-black bg-opacity-60 rounded-lg px-3 py-1.5 text-white text-sm shadow">
                    <i class="ri-book-open-line"></i>
                    <span>${tt('mushaf.mushaf', 'Mushaf')}</span>
                    <span class="opacity-75 mushaf-page-label" data-mushaf-page-label>${this._page}</span>
                </div>
            `;
            tile.appendChild(label);

            // Click → toggle focus mode
            tile.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this._handleTileClick(tile);
            });

            grid.appendChild(tile);
            try { window.meeting?.layout?.applyGrid(grid.children.length); } catch (_) {}
        }

        _removeTile() {
            const tile = document.getElementById(TILE_ID);
            const layout = window.meeting?.layout;
            try {
                if (layout?.isFocusModeActive && layout.focusedParticipant === 'mushaf') {
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
            if (state?.isFocusModeActive && state?.focusedParticipant === 'mushaf') {
                layout.exitFocusMode();
            } else {
                layout.applyFocusMode('mushaf', tile);
            }
        }

        _observeFocusMode() {
            const container = document.getElementById(FOCUS_CONTAINER_ID);
            if (!container) {
                setTimeout(() => this._observeFocusMode(), 250);
                return;
            }
            this._focusObserver = new MutationObserver(() => {
                const focused = container.querySelector('#focused-mushaf');
                if (focused) this._onFocusEnter(focused);
                else this._onFocusExit();
            });
            this._focusObserver.observe(container, { childList: true, subtree: false });
        }

        _onFocusEnter(focusedTile) {
            // Re-paint the focused clone using already-cached page data so
            // the font scales to the larger area. Toolbar is rendered
            // after so its height can be reserved by _reflowAllPages().
            const host = focusedTile.querySelector('.' + TILE_CONTENT_CLASS);
            const cached = this._pageDataCache.get(this._page) || null;
            this._renderPageIntoSync(host, cached);
            this._renderHighlight();
            this._showToolbar(focusedTile);
            // Kick a full async re-render to fill in anything missing
            // (font, data) — _renderTile is idempotent against gen.
            this._renderTile();
        }

        _onFocusExit() {
            this._hideToolbar();
        }

        // ───── Toolbar ─────

        _showToolbar(focusedTile) {
            this._hideToolbar();
            const bar = document.createElement('div');
            bar.id = 'mushaf-toolbar';
            bar.className = 'absolute z-[70] left-1/2 -translate-x-1/2 bottom-3 flex items-center gap-2';
            bar.style.pointerEvents = 'auto';
            bar.addEventListener('click', (e) => e.stopPropagation());

            if (this._canShare) {
                // Teacher controls — full nav + surah picker + share toggle.
                const sharing = this._isShared;
                const meta = this._surahMetaForPage(this._page);
                const surahLabel = meta.surahNameAr || tt('mushaf.surah_picker', 'Surah');
                bar.innerHTML = `
                    <div class="bg-white rounded-full px-2 py-1.5 shadow-2xl flex items-center gap-1 border border-gray-200">
                        <button data-mushaf-act="prev" aria-label="${tt('mushaf.prev_page', 'Previous')}" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 flex items-center justify-center transition" title="${tt('mushaf.prev_page', 'Previous')}"><i class="ri-arrow-right-s-line text-lg"></i></button>
                        <button data-mushaf-act="page-picker" class="px-2 h-9 rounded-md hover:bg-gray-100 text-gray-800 text-sm font-mono flex items-center gap-1" title="${tt('mushaf.page_label', 'Page')}">
                            <span data-mushaf-page-num>${this._page}</span><span class="text-gray-400">/${MAX_PAGE}</span>
                        </button>
                        <button data-mushaf-act="next" aria-label="${tt('mushaf.next_page', 'Next')}" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 flex items-center justify-center transition" title="${tt('mushaf.next_page', 'Next')}"><i class="ri-arrow-left-s-line text-lg"></i></button>
                        <span class="w-px h-6 bg-gray-300 mx-1"></span>
                        <button data-mushaf-act="surah-picker" class="px-3 h-9 rounded-md hover:bg-gray-100 text-gray-800 text-sm flex items-center gap-1.5" title="${tt('mushaf.surah_picker', 'Pick a Surah')}">
                            <i class="ri-book-2-line"></i>
                            <span style="font-family: 'Cairo','Tajawal',sans-serif;">${surahLabel}</span>
                        </button>
                        <span class="w-px h-6 bg-gray-300 mx-1"></span>
                        <button data-mushaf-act="share" aria-label="${tt('mushaf.share', 'Share')}" class="px-3 h-9 rounded-full ${sharing ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-blue-600 text-white hover:bg-blue-700'} flex items-center gap-1.5 text-sm font-medium transition">
                            <i class="ri-${sharing ? 'stop-circle-line' : 'share-line'}"></i>
                            <span>${sharing ? tt('mushaf.unshare', 'Stop sharing') : tt('mushaf.share', 'Share')}</span>
                        </button>
                    </div>
                `;
            } else {
                // Student view — page indicator only, no nav controls.
                // Students follow whatever page the teacher publishes.
                bar.innerHTML = `
                    <div class="bg-white/90 backdrop-blur rounded-full px-3 py-1.5 shadow flex items-center gap-1.5 text-sm text-gray-700 border border-gray-200">
                        <i class="ri-book-open-line text-gray-500"></i>
                        <span>${tt('mushaf.page_label', 'Page')}</span>
                        <span data-mushaf-page-num class="font-mono">${this._page}</span>
                        <span class="text-gray-400">/${MAX_PAGE}</span>
                    </div>
                `;
            }
            focusedTile.appendChild(bar);

            bar.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-mushaf-act]');
                if (!btn) return;
                if (!this._canShare) return; // read-only for students
                const act = btn.dataset.mushafAct;
                if (act === 'prev') this.goToPage(this._page - 1);
                else if (act === 'next') this.goToPage(this._page + 1);
                else if (act === 'page-picker') this._togglePagePicker(focusedTile);
                else if (act === 'surah-picker') this._toggleSurahPicker(focusedTile);
                else if (act === 'share') {
                    if (this._isShared) this._stopSharing();
                    else this._startSharing();
                    this._showToolbar(focusedTile); // re-render share-state styling
                    this._reflowAllPages();
                }
            });

            // Reserve space for the toolbar in the page layout so it
            // doesn't overlap the verses (issue 1). The layoutPage
            // closure on each .mushaf-page reads `_mushafToolbarH` off the
            // focused tile when reflowing.
            focusedTile._mushafToolbarH = bar.getBoundingClientRect().height + 18;
            this._reflowAllPages();
        }

        _hideToolbar() {
            const bar = document.getElementById('mushaf-toolbar');
            if (bar && bar.parentNode) bar.parentNode.removeChild(bar);
            const pp = document.getElementById('mushaf-page-picker');
            if (pp && pp.parentNode) pp.parentNode.removeChild(pp);
            const sp = document.getElementById('mushaf-surah-picker');
            if (sp && sp.parentNode) sp.parentNode.removeChild(sp);
        }

        _reflowAllPages() {
            document.querySelectorAll('.' + TILE_CONTENT_CLASS).forEach((el) => {
                if (typeof el._mushafRelayout === 'function') el._mushafRelayout();
            });
        }

        // Teacher-only: "Go to page N" inline input.
        _togglePagePicker(focusedTile) {
            const existing = focusedTile.querySelector('#mushaf-page-picker');
            if (existing) { existing.remove(); return; }
            // Close surah picker if open
            const sp = focusedTile.querySelector('#mushaf-surah-picker');
            if (sp) sp.remove();
            const panel = document.createElement('div');
            panel.id = 'mushaf-page-picker';
            panel.className = 'absolute z-[71] left-1/2 -translate-x-1/2 bg-white rounded-xl shadow-2xl border border-gray-200 p-3';
            panel.style.bottom = ((focusedTile._mushafToolbarH || 70) + 10) + 'px';
            panel.style.pointerEvents = 'auto';
            panel.addEventListener('click', (e) => e.stopPropagation());
            panel.innerHTML = `
                <label class="block text-xs text-gray-600 mb-2">${tt('mushaf.page_label', 'Page')} (1 - ${MAX_PAGE})</label>
                <div class="flex items-center gap-2">
                    <input type="number" min="1" max="${MAX_PAGE}" value="${this._page}" class="w-24 px-2 py-1.5 border border-gray-300 rounded text-sm" id="mushaf-page-input" autocomplete="off" />
                    <button id="mushaf-page-go" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-medium">${tt('mushaf.go', 'Go')}</button>
                </div>
            `;
            focusedTile.appendChild(panel);
            const input = panel.querySelector('#mushaf-page-input');
            const go = panel.querySelector('#mushaf-page-go');
            const submit = () => {
                const v = parseInt(input.value, 10);
                if (v >= 1 && v <= MAX_PAGE) {
                    this.goToPage(v);
                    panel.remove();
                }
            };
            go.addEventListener('click', submit);
            input.addEventListener('keydown', (e) => { if (e.key === 'Enter') submit(); });
            input.focus(); input.select();
        }

        // Teacher-only: scrollable 114-surah picker.
        _toggleSurahPicker(focusedTile) {
            const existing = focusedTile.querySelector('#mushaf-surah-picker');
            if (existing) { existing.remove(); return; }
            const pp = focusedTile.querySelector('#mushaf-page-picker');
            if (pp) pp.remove();
            const surahs = (window.QuranData && window.QuranData.SURAHS) || [];
            const startPagesMap = (window.QuranData && window.QuranData.PAGE_TO_START_SURAH) || [];
            // Build surahNumber → first page where it starts.
            const startPageBySurah = {};
            for (let p = 1; p < startPagesMap.length; p++) {
                const s = startPagesMap[p];
                if (s && !startPageBySurah[s]) startPageBySurah[s] = p;
            }
            const currentSurah = this._surahMetaForPage(this._page).surahNumber || 0;

            const panel = document.createElement('div');
            panel.id = 'mushaf-surah-picker';
            panel.className = 'absolute z-[71] left-1/2 -translate-x-1/2 bg-white rounded-xl shadow-2xl border border-gray-200 w-72 max-h-80 overflow-hidden flex flex-col';
            panel.style.bottom = ((focusedTile._mushafToolbarH || 70) + 10) + 'px';
            panel.style.pointerEvents = 'auto';
            panel.addEventListener('click', (e) => e.stopPropagation());
            panel.style.fontFamily = "'Cairo','Tajawal',sans-serif";
            panel.dir = 'rtl';
            let html = `
                <div class="shrink-0 px-3 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-sm font-bold text-gray-700">${tt('mushaf.surah_picker', 'Pick a Surah')}</span>
                    <button id="mushaf-surah-picker-close" class="w-7 h-7 rounded-full hover:bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Close"><i class="ri-close-line"></i></button>
                </div>
                <div class="overflow-y-auto flex-1">`;
            for (let i = 0; i < surahs.length; i++) {
                const s = i + 1;
                const name = surahs[i][0];
                const page = startPageBySurah[s] || 1;
                const isCurrent = s === currentSurah;
                html += `<button data-surah-num="${s}" data-surah-page="${page}" class="w-full grid grid-cols-[2rem_1fr_3rem] items-center gap-2 px-3 py-2 text-right ${isCurrent ? 'bg-blue-50 hover:bg-blue-100' : 'hover:bg-gray-50'} transition border-b border-gray-100 last:border-b-0">
                    <span class="text-xs ${isCurrent ? 'text-blue-700 font-bold' : 'text-gray-400'} font-mono justify-self-start">${s}</span>
                    <span class="text-sm ${isCurrent ? 'text-blue-900 font-semibold' : 'text-gray-800'} text-right">${name}</span>
                    <span class="text-xs text-gray-400 font-mono justify-self-end">${page}</span>
                </button>`;
            }
            html += '</div>';
            panel.innerHTML = html;
            focusedTile.appendChild(panel);

            panel.querySelector('#mushaf-surah-picker-close')?.addEventListener('click', () => panel.remove());
            panel.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-surah-num]');
                if (!btn) return;
                const page = parseInt(btn.dataset.surahPage, 10);
                if (page > 0) {
                    this.goToPage(page);
                    panel.remove();
                }
            });
            // Scroll current surah into view.
            const cur = panel.querySelector(`button[data-surah-num="${currentSurah}"]`);
            if (cur) setTimeout(() => cur.scrollIntoView({ block: 'center' }), 0);
        }

        // ───── Render ─────

        async _renderTile() {
            // Bump the requested-page generation so any in-flight render
            // for an earlier page knows it's been superseded.
            const myGen = ++this._renderGen;

            // Update the label pill page number first (synchronous).
            document.querySelectorAll('[data-mushaf-page-label]').forEach(el => { el.textContent = this._page; });
            document.querySelectorAll('[data-mushaf-page-num]').forEach(el => { el.textContent = this._page; });

            // Wait for the page font AND the per-page JSON before mutating
            // any glyph DOM. This kills the "render → tofu flash → swap"
            // UX (issue 3): the old page content stays visible until the
            // new resources are ready, then we swap atomically.
            const dataP = this._fetchPageData(this._page);
            const fontP = this._loadPageFont(this._page);
            this._loadCompanionFonts().catch(() => null);
            let data = null;
            try {
                const [d] = await Promise.all([dataP, fontP.catch(() => null)]);
                data = d;
            } catch (_) {}

            // If another navigation happened while we were waiting, bail.
            if (myGen !== this._renderGen) return;

            document.querySelectorAll('.' + TILE_CONTENT_CLASS).forEach((el) => {
                this._renderPageIntoSync(el, data);
            });
            this._renderHighlight();
            this._reflowAllPages();

            // Background prefetch — keeps prev/next nav snappy. We also
            // prefetch the per-page JSON so the next page's await resolves
            // immediately from cache.
            const prefetch = (p) => {
                if (p < MIN_PAGE || p > MAX_PAGE) return;
                this._loadPageFont(p).catch(() => null);
                this._fetchPageData(p).catch(() => null);
            };
            prefetch(this._page - 1);
            prefetch(this._page + 1);
        }

        _renderPageIntoSync(host, data) {
            if (!host) return;
            host.innerHTML = '';
            host.style.direction = 'rtl';

            if (data && Array.isArray(data.lines)) {
                this._renderQpcPage(host, data);
                return;
            }

            // Fallback when the per-page JSON isn't available — surah
            // catalog metadata only.
            const meta = this._surahMetaForPage(this._page);
            host.style.fontFamily = "'Cairo', 'Tajawal', sans-serif";
            const fallback = document.createElement('div');
            fallback.className = 'flex flex-col items-center gap-2 text-gray-700';
            fallback.innerHTML = `
                <div class="text-[clamp(14px,2vw,28px)] font-bold">${meta.surahNameAr || ''}</div>
                <div class="text-[clamp(11px,1.4vw,18px)] opacity-75">${tt('mushaf.page_label', 'Page')} <span class="font-mono">${this._page}</span> / ${MAX_PAGE}</div>
                ${meta.juz ? `<div class="text-[clamp(10px,1.2vw,15px)] opacity-60">${tt('mushaf.juz', 'Juz')} ${meta.juz}</div>` : ''}
            `;
            host.appendChild(fallback);
        }

        /**
         * Render a QPC v4 page from the pre-baked JSON schema. The schema
         * has three line types: `surah_name`, `basmallah`, `ayah`. Each
         * uses its own font:
         *   surah_name  → 'mushaf-surah' (surah_name_naskh.ttf)
         *   basmallah   → 'mushaf-bismillah' (vertopal.com_QCF_Bismillah-Regular.ttf)
         *   ayah        → 'QPC-<page>' (per-page color font; this page's font)
         *
         * Ayah lines are rendered with `centered` line alignment iff
         * `line.centered === 1`, mirroring the Dart QpcV4AyahLineBlock.
         * `segments` inside each ayah line are grouped by (surah, ayah)
         * so that highlighting a specific verse only paints the matching
         * span — not the whole line.
         */
        _renderQpcPage(host, data) {
            // A real Madinah-mushaf page is roughly 0.57 wide:tall (392 × 693
            // at the source font size of 23.1 with line-height 2.0 over 15
            // lines). We letterbox an inner "page" of that aspect inside the
            // tile so the page looks like a single cohesive mushaf page —
            // not a stretched 16:9 strip.
            //
            // Font size is then derived from the page WIDTH (not height) so
            // the QPC v4 per-page font hits its design width and the lines
            // justify naturally — same approach the mobile package uses in
            // PageFontSizeHelper (fontSize ≈ width × 23.1 / 392).
            host.style.fontFamily = '';
            host.style.background = ''; // backdrop transparent; the page surface paints itself
            host.style.padding = '0';
            host.style.overflow = 'hidden';
            host.style.display = 'flex';
            host.style.alignItems = 'center';
            host.style.justifyContent = 'center';

            // Build the inner page container.
            const page = document.createElement('div');
            page.className = 'mushaf-page-inner';
            page.style.background = '#fefdf8';
            page.style.boxShadow = '0 6px 18px rgba(0,0,0,0.12)';
            page.style.borderRadius = '8px';
            page.style.color = '#0b1220';
            page.style.position = 'relative';
            page.style.direction = 'rtl';
            page.style.display = 'flex';
            page.style.flexDirection = 'column';
            page.style.justifyContent = 'space-between';
            page.style.alignItems = 'stretch';
            page.style.overflow = 'hidden';
            page.style.boxSizing = 'border-box';

            // Sizing is computed dynamically against the host tile. We re-run
            // on every ResizeObserver tick so focus-mode resize updates the
            // page without re-rendering content.
            const lineCount = Math.max(1, data.lines.length);
            const layoutPage = () => {
                const rect = host.getBoundingClientRect();
                if (rect.width <= 0 || rect.height <= 0) return;
                const MUSHAF_ASPECT = 0.57; // width / height
                // Subtract toolbar height (set by _showToolbar) so the
                // floating bar at the bottom doesn't overlap the page.
                // The toolbar height is parked on the focused tile element
                // (the ancestor with id starting with `focused-`); for the
                // non-focused tile it's 0.
                let toolbarReserve = 0;
                const focused = host.closest('[id^="focused-"]');
                if (focused && focused._mushafToolbarH) {
                    toolbarReserve = focused._mushafToolbarH;
                }
                const availW = rect.width * 0.94;
                const availH = (rect.height * 0.94) - toolbarReserve;
                if (availH <= 0) return;
                let pageW, pageH;
                if (availW / availH > MUSHAF_ASPECT) {
                    pageH = availH;
                    pageW = pageH * MUSHAF_ASPECT;
                } else {
                    pageW = availW;
                    pageH = pageW / MUSHAF_ASPECT;
                }
                page.style.width = pageW + 'px';
                page.style.height = pageH + 'px';
                // Top-align the page inside the host when the toolbar
                // is reserved at the bottom, so the page doesn't drift
                // down into the toolbar zone as text wraps.
                page.style.marginTop = toolbarReserve > 0 ? '0' : 'auto';
                page.style.marginBottom = toolbarReserve > 0 ? (toolbarReserve + 'px') : 'auto';
                // QPC v4 design: fontSize 23.1 at 392 design width.
                const fontSize = Math.max(8, Math.min(64, pageW * (23.1 / 392)));
                page.style.fontSize = fontSize.toFixed(2) + 'px';
                // Inner padding mirrors mobile's vertical margin.
                const padY = pageH * 0.02, padX = pageW * 0.03;
                page.style.padding = padY + 'px ' + padX + 'px';
                // Apportion equal vertical space to every line; each row's
                // own `min-height` is set as a CSS var so they don't have
                // to be re-rendered.
                const rowH = (pageH - padY * 2) / lineCount;
                page.style.setProperty('--mushaf-row-h', rowH.toFixed(2) + 'px');
            };
            // Expose the layout closure on the host so _reflowAllPages can
            // re-run it when the toolbar appears or disappears.
            host._mushafRelayout = layoutPage;
            // Initial sizing on next frame after host is in the DOM.
            requestAnimationFrame(layoutPage);
            // Re-flow on host resize (focus mode enters/exits change the
            // container size sharply).
            if (window.ResizeObserver) {
                try {
                    const ro = new ResizeObserver(layoutPage);
                    ro.observe(host);
                    // Park the observer on the page element so it gets GC'd
                    // when the host is removed from the DOM.
                    page._mushafResizeObserver = ro;
                } catch (_) {}
            }

            const inner = page; // alias to keep the rest of this fn readable

            for (const line of data.lines) {
                const row = document.createElement('div');
                // Each row occupies a fixed share of the page height
                // (set via `--mushaf-row-h` on the parent). Content is
                // vertically centered inside that row so surah-name and
                // basmallah banners don't push the page out of alignment.
                row.className = 'mushaf-row';
                row.style.display = 'flex';
                row.style.alignItems = 'center';
                row.style.width = '100%';
                row.style.height = 'var(--mushaf-row-h)';
                row.style.lineHeight = '1';
                row.style.overflow = 'hidden';
                row.dataset.lineN = String(line.n || '');
                row.dataset.lineType = line.type;
                if (line.type === 'surah_name') {
                    row.style.justifyContent = 'center';
                    const name = document.createElement('span');
                    name.className = 'mushaf-surah-banner';
                    // surahName font ligatures convert the surah number text
                    // into a decorative banner. Same trick the mobile package
                    // uses (see surah_header_widget.dart line 51).
                    name.style.fontFamily = "'mushaf-surah', serif";
                    name.style.fontSize = '1em';
                    name.style.lineHeight = '1';
                    name.style.color = '#111827';
                    name.style.display = 'inline-block';
                    name.textContent = String(line.surah || '');
                    row.appendChild(name);
                } else if (line.type === 'basmallah') {
                    row.style.justifyContent = 'center';
                    const span = document.createElement('span');
                    span.className = 'mushaf-basmallah';
                    span.style.fontFamily = "'mushaf-bismillah', serif";
                    span.style.fontSize = '0.95em';
                    span.style.lineHeight = '1';
                    span.style.color = '#111827';
                    span.style.display = 'inline-block';
                    // Surah-specific glyph variant (mirrors bsmallah_widget.dart).
                    const surah = Number(line.surah) || 0;
                    let glyph = 'ﲪﲫﲮﲴ';
                    if (surah === 2)                       glyph = 'ﲚﲛﲞﲤ';
                    else if (surah === 95 || surah === 97) glyph = 'ﭗﲫﲮﲴ';
                    span.textContent = glyph;
                    row.appendChild(span);
                } else {
                    // Ayah line. We render every WORD as its own flex
                    // child so the row can spread words evenly across the
                    // page width — CSS `text-align: justify` won't work
                    // because the wire format uses U+202F (narrow no-break
                    // space) which the justify algorithm doesn't expand.
                    //
                    // Words are tagged with `data-surah` / `data-ayah` for
                    // highlight, and grouped visually by being adjacent.
                    // `line.centered === 1` in the source means the QPC
                    // line was hand-balanced to fall short of full width
                    // (e.g. end of a surah) — we mirror that by clustering
                    // the words at the center instead of spreading them.
                    row.style.fontFamily = `'QPC-${this._page}', 'Amiri', serif`;
                    row.style.direction = 'rtl';
                    row.style.justifyContent = line.centered ? 'center' : 'space-between';
                    row.style.gap = line.centered ? '0.18em' : '0';
                    // Split on any whitespace (the wire format uses U+202F
                    // NARROW NO-BREAK SPACE between words within an ayah).
                    // For teachers, words inside the focused-mode tile get
                    // `cursor: pointer` so it's discoverable they can be
                    // tapped to highlight (see _installHighlightHandler).
                    const inFocused = !!host.closest('#' + FOCUS_CONTAINER_ID);
                    const interactive = this._canShare && inFocused;
                    for (const seg of (line.segments || [])) {
                        const words = String(seg.text || '').split(/\s+/).filter(w => w.length > 0);
                        for (const w of words) {
                            const word = document.createElement('span');
                            word.className = 'mushaf-word';
                            word.dataset.surah = String(seg.surah);
                            word.dataset.ayah = String(seg.ayah);
                            word.style.display = 'inline-block';
                            word.style.color = '#0b1220';
                            if (interactive) {
                                word.style.cursor = 'pointer';
                                word.style.transition = 'background-color 0.12s';
                            }
                            word.textContent = w;
                            row.appendChild(word);
                        }
                    }
                }
                inner.appendChild(row);
            }

            host.appendChild(inner);
        }

        /** Lazy-load the surahName + bismillah companion fonts (once per session). */
        _loadCompanionFonts() {
            if (this._companionFontsP) return this._companionFontsP;
            const surah = new FontFace('mushaf-surah', `url(/mushaf/fonts/surah-name.woff2) format('woff2')`, { display: 'block' });
            const bism = new FontFace('mushaf-bismillah', `url(/mushaf/fonts/bismillah.woff2) format('woff2')`, { display: 'block' });
            this._companionFontsP = Promise.all([
                surah.load().then(() => document.fonts.add(surah)),
                bism.load().then(() => document.fonts.add(bism)),
            ]);
            this._companionFontsP.catch(() => {});
            return this._companionFontsP;
        }

        _renderHighlight() {
            // Highlight every word span tagged with the active (surah, ayah)
            // across all rendered tiles (tile + focused clone).
            document.querySelectorAll('.' + TILE_CONTENT_CLASS + ' .mushaf-word').forEach((el) => {
                const s = Number(el.dataset.surah);
                const a = Number(el.dataset.ayah);
                const on = this._highlight && this._highlight.s === s && this._highlight.a === a;
                el.style.backgroundColor = on ? 'rgba(251, 191, 36, 0.45)' : '';
                el.style.borderRadius = on ? '3px' : '';
            });
        }

        // ───── Data sources ─────

        async _fetchPageData(page) {
            if (this._pageDataCache.has(page)) return this._pageDataCache.get(page);
            // Bundled per-page payload served statically from public/mushaf/pages/.
            // We cache the null too so a missing page doesn't refetch every render.
            try {
                const res = await fetch(`/mushaf/pages/${page}.json`, { credentials: 'same-origin' });
                if (!res.ok) throw new Error('no data');
                const json = await res.json();
                this._pageDataCache.set(page, json);
                return json;
            } catch (_) {
                this._pageDataCache.set(page, null);
                return null;
            }
        }

        _surahMetaForPage(page) {
            if (window.QuranData && typeof window.QuranData.surahForPage === 'function') {
                return window.QuranData.surahForPage(page) || {};
            }
            return {};
        }

        // ───── Per-page QPC font (best-effort) ─────

        _loadPageFont(page) {
            if (this._fontPromises.has(page)) {
                this._touchLru(page);
                return this._fontPromises.get(page);
            }
            const url = `/mushaf/fonts/${page}.woff2`;
            // `display: 'block'` makes the browser wait (up to 3s) for the
            // font before rendering any text in this family — otherwise
            // the QPC private-use codepoints get drawn with a fallback
            // font and look like Tofu boxes. We also await the promise
            // before _renderTile mutates the DOM, so this is belt-and-
            // suspenders against any code path that bypasses the await.
            const ff = new FontFace(`QPC-${page}`, `url(${url}) format('woff2')`, { display: 'block' });
            const promise = ff.load().then(() => {
                document.fonts.add(ff);
                this._touchLru(page);
            });
            promise.catch(() => {});
            this._fontPromises.set(page, promise);
            return promise;
        }

        _touchLru(page) {
            const idx = this._fontLru.indexOf(page);
            if (idx >= 0) this._fontLru.splice(idx, 1);
            this._fontLru.unshift(page);
            while (this._fontLru.length > FONT_LRU_SIZE) {
                const evicted = this._fontLru.pop();
                this._fontPromises.delete(evicted);
            }
        }
    }

    window.mushaf = new Mushaf();
})();
