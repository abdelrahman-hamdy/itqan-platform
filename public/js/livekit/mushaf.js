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

            if (!this._canShare) this._scheduleSnapReq();
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
            const p = clampPage(page);
            if (p === this._page) return;
            this._page = p;
            this._highlight = null;
            this._renderTile();
            if (this._canShare && this._isShared) {
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
            // Re-render the focused clone (so the font scales to the larger area).
            this._renderPageInto(focusedTile.querySelector('.' + TILE_CONTENT_CLASS));
            this._showToolbar(focusedTile);
        }

        _onFocusExit() {
            this._hideToolbar();
        }

        // ───── Toolbar ─────

        _showToolbar(focusedTile) {
            this._hideToolbar();
            const bar = document.createElement('div');
            bar.id = 'mushaf-toolbar';
            bar.className = 'absolute z-[70] left-1/2 -translate-x-1/2 bottom-3 bg-white rounded-full px-3 py-2 shadow-2xl flex items-center gap-2 border border-gray-200';
            bar.style.pointerEvents = 'auto';
            bar.addEventListener('click', (e) => e.stopPropagation());
            const sharing = this._canShare && this._isShared;
            bar.innerHTML = `
                <button data-mushaf-act="prev" aria-label="${tt('mushaf.prev_page', 'Previous')}" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 flex items-center justify-center transition"><i class="ri-arrow-right-s-line text-lg"></i></button>
                <div class="px-2 text-sm font-mono text-gray-700">
                    <span data-mushaf-page-num>${this._page}</span> / ${MAX_PAGE}
                </div>
                <button data-mushaf-act="next" aria-label="${tt('mushaf.next_page', 'Next')}" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 flex items-center justify-center transition"><i class="ri-arrow-left-s-line text-lg"></i></button>
                ${this._canShare ? `
                <span class="w-px h-6 bg-gray-300"></span>
                <button data-mushaf-act="share" aria-label="${tt('mushaf.share', 'Share')}" class="px-3 h-9 rounded-full ${sharing ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'} flex items-center gap-1.5 text-sm font-medium transition">
                    <i class="ri-share-line"></i>
                    <span>${sharing ? tt('mushaf.unshare', 'Stop sharing') : tt('mushaf.share', 'Share')}</span>
                </button>
                ` : ''}
            `;
            focusedTile.appendChild(bar);

            bar.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                if (!btn) return;
                const act = btn.dataset.mushafAct;
                // prev = earlier in mushaf (smaller page number, page-1)
                // next = later  (larger page number, page+1)
                // The RTL chevron icons match: ri-arrow-right (visually
                // right, "back" in RTL) → prev; ri-arrow-left → next.
                if (act === 'prev') this.goToPage(this._page - 1);
                else if (act === 'next') this.goToPage(this._page + 1);
                else if (act === 'share') {
                    if (this._isShared) this._stopSharing();
                    else this._startSharing();
                    this._showToolbar(focusedTile); // re-render share-state styling
                }
            });
        }

        _hideToolbar() {
            const bar = document.getElementById('mushaf-toolbar');
            if (bar && bar.parentNode) bar.parentNode.removeChild(bar);
        }

        // ───── Render ─────

        _renderTile() {
            // Update the label pill page number first
            document.querySelectorAll('[data-mushaf-page-label]').forEach(el => { el.textContent = this._page; });
            document.querySelectorAll('[data-mushaf-page-num]').forEach(el => { el.textContent = this._page; });

            // Paint into all .mushaf-page elements (tile + focused clone).
            document.querySelectorAll('.' + TILE_CONTENT_CLASS).forEach((el) => {
                this._renderPageInto(el);
            });
            this._renderHighlight();
        }

        async _renderPageInto(host) {
            if (!host) return;
            // Kick off font + data loads in parallel. Font loads are awaited
            // only loosely — the page may render glyphs against the loading
            // font (browser shows fallback briefly until QPC swaps in).
            const dataP = this._fetchPageData(this._page);
            this._loadPageFont(this._page).catch(() => null);
            this._loadCompanionFonts().catch(() => null);
            const data = await dataP.catch(() => null);

            host.innerHTML = '';
            host.style.direction = 'rtl';

            if (data && Array.isArray(data.lines)) {
                this._renderQpcPage(host, data);
                return;
            }

            // Final fallback: surah catalog metadata only (no payload bundled).
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
                // Reserve a small inner margin (~3% each side).
                const availW = rect.width * 0.94;
                const availH = rect.height * 0.94;
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
                    for (const seg of (line.segments || [])) {
                        const words = String(seg.text || '').split(/\s+/).filter(w => w.length > 0);
                        for (const w of words) {
                            const word = document.createElement('span');
                            word.className = 'mushaf-word';
                            word.dataset.surah = String(seg.surah);
                            word.dataset.ayah = String(seg.ayah);
                            word.style.display = 'inline-block';
                            word.style.color = '#0b1220';
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
            const surah = new FontFace('mushaf-surah', `url(/mushaf/fonts/surah-name.woff2) format('woff2')`);
            const bism = new FontFace('mushaf-bismillah', `url(/mushaf/fonts/bismillah.woff2) format('woff2')`);
            this._companionFontsP = Promise.all([
                surah.load().then(() => document.fonts.add(surah)),
                bism.load().then(() => document.fonts.add(bism)),
            ]);
            this._companionFontsP.catch(() => {}); // swallow — fallback renders without them
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
            const ff = new FontFace(`QPC-${page}`, `url(${url}) format('woff2')`);
            const promise = ff.load().then(() => {
                document.fonts.add(ff);
                this._touchLru(page);
            });
            // Don't bubble missing-font errors — fallbacks above handle it.
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
