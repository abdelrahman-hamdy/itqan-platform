/**
 * LiveKit Mushaf Module — web parity with mobile MushafReaderCubit +
 * MushafShareController.
 *
 * Wire protocol: topic `mushaf`, JSON UTF-8, reliable. Source of truth is
 * `itqan-mobile/lib/features/mushaf/sync/mushaf_sync_message.dart`. Every
 * packet carries `type`, `v: 1`, `id` (instanceId), `seq`. `m_snap_req`
 * is the only student → teacher packet.
 *
 * Page rendering: each Madinah-mushaf page is rendered with the
 * corresponding KFGQPC QPC v4 page font (`/mushaf/fonts/{page}.woff2`,
 * loaded lazily via FontFace API). Per-ayah glyph strings + line layout
 * come from the pre-baked `quran-data.js`. If that file isn't present
 * yet, the renderer falls back to a textual "page N" placeholder so the
 * sync protocol can still be exercised end-to-end.
 *
 * State is fully ephemeral — closing the sheet wipes everything.
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

    function uuid() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
        return Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
    }

    class Mushaf {
        constructor() {
            this._room = null;
            this._canShare = false;
            this._localIdentity = null;
            this._initialized = false;
            this._destroyed = false;

            // Local viewer state
            this._isOpen = false;
            this._page = 1;
            this._highlight = null; // {s, a} | null

            // Share-controller state (teacher = isWriter)
            this._instanceId = null;
            this._seq = 0;
            this._isShared = false;
            this._isFollowing = false; // student-only — true while teacher is sharing
            this._lastSeqByInstance = new Map();

            // Timers
            this._snapReqTimers = [];
            this._heartbeatTimer = null;
            this._snapReplyTimer = null;
            this._snapshotRequesters = new Set();

            // Font caching (LRU)
            this._fontLru = [];        // page numbers, MRU first
            this._fontPromises = new Map(); // page → Promise<void>

            this._encoder = new TextEncoder();
            this._decoder = new TextDecoder();
        }

        // -------- Public API --------

        init(room, canShare, opts) {
            if (this._initialized) return;
            this._room = room;
            this._canShare = !!canShare;
            this._localIdentity = (opts && opts.localIdentity) || (room && room.localParticipant && room.localParticipant.identity) || null;
            this._initialized = true;

            this._installPanel();
            this._installButtons();

            // Students send snap_req on init so they catch the teacher's
            // active mushaf state (if any). Teachers don't.
            if (!this._canShare) {
                this._scheduleSnapReq();
            }
        }

        destroy() {
            if (this._destroyed) return;
            this._destroyed = true;
            this._clearSnapReqTimers();
            if (this._heartbeatTimer) { clearInterval(this._heartbeatTimer); this._heartbeatTimer = null; }
            if (this._snapReplyTimer) { clearTimeout(this._snapReplyTimer); this._snapReplyTimer = null; }
            this._snapshotRequesters.clear();
        }

        toggle() {
            if (this._isOpen) this.close();
            else this.open();
        }

        open() {
            this._isOpen = true;
            this._showOverlay();
            if (this._canShare) {
                this._startSharing();
            }
            this._renderPage();
        }

        close() {
            this._isOpen = false;
            if (this._canShare && this._isShared) {
                this._stopSharing();
            }
            this._hideOverlay();
        }

        goToPage(page) {
            const p = Math.max(MIN_PAGE, Math.min(MAX_PAGE, Number(page) || 1));
            if (p === this._page) return;
            this._page = p;
            this._highlight = null;
            this._renderPage();
            if (this._canShare && this._isShared) {
                this._publish({
                    type: 'm_nav',
                    v: PROTO_VERSION,
                    id: this._instanceId,
                    seq: this._nextSeq(),
                    p: this._page,
                });
            }
        }

        setHighlight(surah, ayah) {
            if (surah == null || ayah == null) {
                this._highlight = null;
            } else {
                this._highlight = { s: Number(surah), a: Number(ayah) };
            }
            this._renderHighlight();
            if (this._canShare && this._isShared) {
                const packet = {
                    type: 'm_hl',
                    v: PROTO_VERSION,
                    id: this._instanceId,
                    seq: this._nextSeq(),
                };
                if (this._highlight) packet.h = this._highlight;
                this._publish(packet);
            }
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
                case 'm_open':     this._onOpen(data); break;
                case 'm_close':    this._onClose(data); break;
                case 'm_nav':      this._onNav(data); break;
                case 'm_hl':       this._onHl(data); break;
                case 'm_snap_req': this._onSnapReq(senderId); break;
                case 'm_snap':     this._onSnap(data); break;
            }
        }

        _onOpen(data) {
            // Teacher's heartbeat / open. Students adopt instanceId and
            // page state idempotently — heartbeats are no-ops if state is
            // already in sync.
            if (this._canShare) return;
            const isNewInstance = this._instanceId !== data.id;
            this._instanceId = data.id;
            this._isFollowing = true;
            this._clearSnapReqTimers();
            const prevSeq = this._lastSeqByInstance.get(data.id) || 0;
            if (data.seq < prevSeq) return;
            this._lastSeqByInstance.set(data.id, data.seq);

            if (isNewInstance || this._page !== data.p) {
                this._page = data.p;
                if (!this._isOpen) this._isOpen = true;
                this._showOverlay();
                this._renderPage();
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
            this._renderHighlight();
            // Don't force-close the student's viewer — they can keep browsing.
        }

        _onNav(data) {
            if (this._canShare) return;
            if (data.id !== this._instanceId) return;
            if (this._isSeqStale(data)) return;
            this._page = data.p;
            this._highlight = null;
            if (!this._isOpen) { this._isOpen = true; this._showOverlay(); }
            this._renderPage();
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
            if (!this._isOpen) { this._isOpen = true; this._showOverlay(); }
            this._renderPage();
        }

        _isSeqStale(data) {
            const last = this._lastSeqByInstance.get(data.id) || 0;
            if (data.seq < last) return true;
            this._lastSeqByInstance.set(data.id, data.seq);
            return false;
        }

        // -------- Sharing (teacher) --------

        _startSharing() {
            if (this._isShared) return;
            this._instanceId = uuid();
            this._seq = 0;
            this._isShared = true;
            this._publish(this._currentOpenPacket());
            // Heartbeat for late joiners — same packet shape, idempotent.
            if (this._heartbeatTimer) clearInterval(this._heartbeatTimer);
            this._heartbeatTimer = setInterval(() => {
                if (!this._isShared) return;
                this._publish(this._currentOpenPacket());
            }, HEARTBEAT_MS);
        }

        _stopSharing() {
            if (!this._isShared) return;
            this._publish({
                type: 'm_close',
                v: PROTO_VERSION,
                id: this._instanceId,
                seq: this._nextSeq(),
            });
            this._isShared = false;
            if (this._heartbeatTimer) { clearInterval(this._heartbeatTimer); this._heartbeatTimer = null; }
        }

        _currentOpenPacket() {
            const p = {
                type: 'm_open',
                v: PROTO_VERSION,
                id: this._instanceId,
                seq: this._nextSeq(),
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
                type: 'm_snap',
                v: PROTO_VERSION,
                id: this._instanceId,
                seq: this._nextSeq(),
                p: this._page,
            };
            if (this._highlight) packet.h = this._highlight;
            this._publish(packet, { to });
        }

        // -------- Wire I/O --------

        _publish(obj, opts) {
            if (!this._room || !this._room.localParticipant) return;
            const bytes = this._encoder.encode(JSON.stringify(obj));
            const options = { reliable: true, topic: 'mushaf' };
            if (opts && opts.to && opts.to.length) options.destinationIdentities = opts.to;
            try {
                this._room.localParticipant.publishData(bytes, options);
            } catch (e) {
                if (window.MT && window.MT.warn) window.MT.warn('mushaf', 'publish_failed', { err: String(e) });
            }
        }

        _nextSeq() { return ++this._seq; }

        _scheduleSnapReq() {
            this._clearSnapReqTimers();
            for (const delay of SNAP_RETRY_DELAYS_MS) {
                const t = setTimeout(() => {
                    if (this._destroyed) return;
                    if (this._isFollowing) return;
                    this._publish({ type: 'm_snap_req', v: PROTO_VERSION });
                }, delay);
                this._snapReqTimers.push(t);
            }
        }

        _clearSnapReqTimers() {
            for (const t of this._snapReqTimers) clearTimeout(t);
            this._snapReqTimers.length = 0;
        }

        // -------- DOM --------

        _installPanel() {
            this._overlay = document.getElementById('mushafOverlay');
            this._pageContainer = document.getElementById('mushafPage');
            this._pageLabel = document.getElementById('mushafPageLabel');
            this._highlightLayer = document.getElementById('mushafHighlightLayer');
        }

        _installButtons() {
            const bind = (id, fn) => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('click', fn);
            };
            bind('mushafClose', () => this.close());
            bind('mushafPrev', () => this.goToPage(this._page + 1)); // Mushaf reading order: next page = +1
            bind('mushafNext', () => this.goToPage(this._page - 1)); // RTL swipe semantics
            if (this._canShare) {
                bind('mushafShareToggle', () => {
                    if (this._isShared) this._stopSharing();
                    else this._startSharing();
                    this._refreshShareButton();
                });
            } else {
                const shareBtn = document.getElementById('mushafShareToggle');
                if (shareBtn) shareBtn.style.display = 'none';
            }
            this._refreshShareButton();
        }

        _refreshShareButton() {
            const shareBtn = document.getElementById('mushafShareToggle');
            if (!shareBtn) return;
            shareBtn.classList.toggle('bg-green-600', this._isShared);
            shareBtn.classList.toggle('bg-gray-700', !this._isShared);
        }

        _showOverlay() {
            if (this._overlay) this._overlay.classList.remove('hidden');
        }

        _hideOverlay() {
            if (this._overlay) this._overlay.classList.add('hidden');
        }

        async _renderPage() {
            if (!this._pageContainer) return;
            const page = this._page;
            if (this._pageLabel) this._pageLabel.textContent = String(page);

            await this._loadPageFont(page).catch(() => null);

            // Use the data file's per-ayah glyph string if available; otherwise
            // a textual placeholder so sync still works in dev/test envs.
            const data = (window.QuranData && window.QuranData.pageGlyphs) ? window.QuranData.pageGlyphs(page) : null;
            this._pageContainer.style.fontFamily = `'QPC-${page}', 'Amiri', serif`;
            this._pageContainer.style.direction = 'rtl';
            this._pageContainer.innerHTML = '';
            if (data && data.lines && data.lines.length) {
                for (const line of data.lines) {
                    const div = document.createElement('div');
                    div.className = 'mushaf-line';
                    div.dataset.surah = String(line.surah || '');
                    div.dataset.ayah = String(line.ayah || '');
                    div.textContent = line.text;
                    this._pageContainer.appendChild(div);
                }
            } else {
                const fallback = document.createElement('div');
                fallback.className = 'text-center text-gray-500 py-12';
                fallback.textContent = (typeof t === 'function')
                    ? t('mushaf.page_label') + ' ' + page
                    : 'صفحة ' + page;
                this._pageContainer.appendChild(fallback);
            }
            this._renderHighlight();
        }

        _renderHighlight() {
            if (!this._highlightLayer) return;
            this._highlightLayer.innerHTML = '';
            if (!this._highlight || !this._pageContainer) return;
            const { s, a } = this._highlight;
            const lines = this._pageContainer.querySelectorAll('.mushaf-line');
            const containerRect = this._pageContainer.getBoundingClientRect();
            lines.forEach((line) => {
                if (Number(line.dataset.surah) !== s || Number(line.dataset.ayah) !== a) return;
                const r = line.getBoundingClientRect();
                const overlay = document.createElement('div');
                overlay.className = 'absolute bg-amber-200/40 rounded';
                overlay.style.left = (r.left - containerRect.left) + 'px';
                overlay.style.top = (r.top - containerRect.top) + 'px';
                overlay.style.width = r.width + 'px';
                overlay.style.height = r.height + 'px';
                this._highlightLayer.appendChild(overlay);
            });
        }

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
                // Note: we don't actively unload the FontFace from document.fonts
                // — once `document.fonts.add(ff)` is called the browser owns the
                // lifecycle. Dropping our Promise entry is enough to let GC
                // reclaim our reference.
            }
        }
    }

    window.mushaf = new Mushaf();
})();
