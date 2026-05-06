{{-- Audio Player Modal — native <audio> for progressive playback + WaveSurfer.js for real waveform --}}
@once
<div
    x-data="{
        open: false,
        playing: false,
        loading: false,
        error: false,
        currentTime: 0,
        duration: 0,
        knownDuration: 0,
        audioUrl: '',
        waveformUrl: '',
        downloadUrl: '',
        recordingDate: '',
        recordingDuration: '',
        recordingSize: '',
        recordingTitle: '',
        recordingId: 0,
        studentName: '',
        teacherName: '',
        sessionType: '',
        sessionDate: '',
        playlist: [],
        currentIndex: -1,
        waveform: null,
        waveformReady: false,
        _WaveSurfer: null,
        touchStartX: 0,
        touchStartY: 0,

        get hasPrev() { return this.currentIndex > 0; },
        get hasNext() { return this.currentIndex < this.playlist.length - 1; },
        get totalDuration() { return this.knownDuration || ((this.duration && isFinite(this.duration)) ? this.duration : 0); },
        get progress() { return this.totalDuration > 0 ? (this.currentTime / this.totalDuration) * 100 : 0; },
        get currentTimeStr() { return this.fmt(this.currentTime); },
        get durationStr() { return this.fmt(this.totalDuration); },

        init() {
            const a = this.$refs.audio;
            a.addEventListener('timeupdate', () => { this.currentTime = a.currentTime; });
            a.addEventListener('loadedmetadata', () => {
                if (a.duration && isFinite(a.duration)) this.duration = a.duration;
            });
            a.addEventListener('durationchange', () => {
                if (a.duration && isFinite(a.duration)) this.duration = a.duration;
            });
            a.addEventListener('play', () => { this.playing = true; this.loading = false; });
            a.addEventListener('pause', () => { this.playing = false; });
            a.addEventListener('ended', () => { this.playing = false; if (this.hasNext) this.next(); });
            a.addEventListener('waiting', () => { this.loading = true; });
            a.addEventListener('canplay', () => { this.loading = false; });
            a.addEventListener('playing', () => { this.loading = false; });
            a.addEventListener('error', () => {
                this.loading = false;
                if (a.error && a.error.code === MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED) {
                    this.error = true;
                }
            });
        },

        async ensureWavesurfer() {
            if (this._WaveSurfer) return this._WaveSurfer;
            // window.loadWaveSurfer is defined in resources/js/app.js — the bare module
            // specifier resolves at build time, and Vite code-splits wavesurfer into its
            // own chunk that's fetched only on first modal open.
            if (typeof window.loadWaveSurfer !== 'function') return null;
            try {
                this._WaveSurfer = await window.loadWaveSurfer();
            } catch (e) {
                this._WaveSurfer = null;
            }
            return this._WaveSurfer;
        },

        destroyWaveform() {
            if (this.waveform) {
                try { this.waveform.destroy(); } catch (e) {}
                this.waveform = null;
            }
            this.waveformReady = false;
        },

        async createWaveform() {
            this.destroyWaveform();
            const url = this.waveformUrl || this.audioUrl;
            const container = this.$refs.waveformContainer;
            if (!url || !container) return;
            const WaveSurfer = await this.ensureWavesurfer();
            if (!WaveSurfer) return;
            // If a newer track was loaded while we were awaiting the import, bail.
            if (url !== (this.waveformUrl || this.audioUrl)) return;

            // Decouple playback URL from waveform URL: <audio> already plays from the direct
            // (cross-origin) LiveKit URL with progressive Range support. We fetch the proxy
            // URL here only to decode peaks for the waveform — passing them via the `peaks`
            // option means WaveSurfer renders without touching media.src.
            let peaks = null;
            let decodedDuration = 0;
            try {
                const response = await fetch(url, { credentials: 'same-origin' });
                if (!response.ok) throw new Error('waveform fetch failed: ' + response.status);
                if (url !== (this.waveformUrl || this.audioUrl)) return;
                const buf = await response.arrayBuffer();
                if (url !== (this.waveformUrl || this.audioUrl)) return;
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) throw new Error('Web Audio API unavailable');
                const ctx = new AudioCtx();
                const decoded = await new Promise((resolve, reject) => {
                    ctx.decodeAudioData(buf.slice(0), resolve, reject);
                });
                if (url !== (this.waveformUrl || this.audioUrl)) {
                    try { ctx.close(); } catch (e) {}
                    return;
                }
                decodedDuration = decoded.duration || 0;
                peaks = [this.computePeaks(decoded, 1000)];
                try { ctx.close(); } catch (e) {}
            } catch (e) {
                // Decode failed (CORS, network, unsupported codec). Leave placeholder bars visible.
                return;
            }

            try {
                // Pass `url` AND `peaks` AND `duration`: WaveSurfer skips fetching/decoding
                // (peaks already provided), and `url` matches our pre-set media.src so the
                // existing audio element keeps streaming from the direct LiveKit URL.
                const ws = WaveSurfer.create({
                    container: container,
                    media: this.$refs.audio,
                    url: this.audioUrl,
                    peaks: peaks,
                    duration: decodedDuration || undefined,
                    waveColor: '#d1d5db',
                    progressColor: '#3b82f6',
                    cursorColor: 'transparent',
                    barWidth: 2,
                    barGap: 1,
                    barRadius: 1,
                    height: 64,
                    normalize: true,
                    interact: true,
                });
                this.waveform = ws;
                this.waveformReady = true;
            } catch (e) {
                this.waveform = null;
                this.waveformReady = false;
            }
        },

        computePeaks(audioBuffer, samples) {
            const channelData = audioBuffer.getChannelData(0);
            const blockSize = Math.max(1, Math.floor(channelData.length / samples));
            const peaks = new Array(samples);
            for (let i = 0; i < samples; i++) {
                let blockMax = 0;
                const start = i * blockSize;
                const end = Math.min(start + blockSize, channelData.length);
                for (let j = start; j < end; j++) {
                    const v = channelData[j];
                    const abs = v < 0 ? -v : v;
                    if (abs > blockMax) blockMax = abs;
                }
                peaks[i] = blockMax;
            }
            return peaks;
        },

        toggle() {
            const a = this.$refs.audio;
            if (this.error) return;
            if (!a.paused) { a.pause(); return; }
            this.loading = true;
            a.play().catch((e) => {
                this.loading = false;
                if (e && e.name === 'NotSupportedError') {
                    this.error = true;
                }
                this.playing = false;
            });
        },

        seek(event) {
            const a = this.$refs.audio;
            if (!this.totalDuration) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const x = event.clientX ?? event.touches?.[0]?.clientX ?? event.changedTouches?.[0]?.clientX ?? 0;
            const pct = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
            a.currentTime = pct * this.totalDuration;
        },

        skip(sec) {
            const a = this.$refs.audio;
            a.currentTime = Math.max(0, Math.min(a.currentTime + sec, this.totalDuration));
        },

        next() { if (this.hasNext) this.loadTrack(this.currentIndex + 1); },
        prev() { if (this.hasPrev) this.loadTrack(this.currentIndex - 1); },

        loadTrack(index) {
            const track = this.playlist[index];
            if (!track) return;
            this.currentIndex = index;
            this.setTrackData(track);
            this.resetState();
            const a = this.$refs.audio;
            a.src = this.audioUrl;
            a.play().catch(() => {});
            this.createWaveform();
        },

        setTrackData(track) {
            this.audioUrl = track.streamUrl;
            this.waveformUrl = track.waveformUrl || track.streamUrl;
            this.downloadUrl = track.downloadUrl;
            this.recordingDate = track.date || '';
            this.recordingDuration = track.duration || '';
            this.recordingSize = track.size || '';
            this.recordingTitle = track.title || '';
            this.recordingId = track.id || 0;
            this.studentName = track.studentName || '';
            this.teacherName = track.teacherName || '';
            this.sessionType = track.sessionType || '';
            this.sessionDate = track.sessionDate || '';
            this.knownDuration = track.durationSeconds || 0;
        },

        resetState() {
            const a = this.$refs.audio;
            a.pause();
            this.currentTime = 0;
            this.duration = 0;
            this.playing = false;
            this.loading = false;
            this.error = false;
        },

        openPlayer(detail) {
            this.playlist = [detail];
            this.currentIndex = 0;
            this.setTrackData(detail);
            this.resetState();
            this.$refs.audio.src = this.audioUrl;
            this.open = true;
            this.$refs.audio.play().catch(() => {});
            this.createWaveform();
        },

        openPlaylist(detail) {
            this.playlist = detail.playlist || [];
            this.currentIndex = detail.startIndex || 0;
            const track = this.playlist[this.currentIndex];
            if (!track) return;
            this.setTrackData(track);
            this.resetState();
            this.$refs.audio.src = this.audioUrl;
            this.open = true;
            this.$refs.audio.play().catch(() => {});
            this.createWaveform();
        },

        closePlayer() {
            this.$refs.audio.pause();
            this.playing = false;
            this.open = false;
            this.destroyWaveform();
        },

        onTouchStart(e) {
            this.touchStartX = e.touches[0].clientX;
            this.touchStartY = e.touches[0].clientY;
        },
        onTouchEnd(e) {
            const dx = e.changedTouches[0].clientX - this.touchStartX;
            const dy = e.changedTouches[0].clientY - this.touchStartY;
            if (Math.abs(dx) < 50 || Math.abs(dy) > Math.abs(dx)) return;
            const isRtl = document.documentElement.dir === 'rtl';
            if (dx < 0) { isRtl ? this.prev() : this.next(); }
            else { isRtl ? this.next() : this.prev(); }
        },

        fmt(s) {
            if (!s || isNaN(s)) return '00:00';
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const ss = Math.floor(s % 60);
            if (h > 0) return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
            return String(m).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
        }
    }"
    x-on:open-audio-player.window="openPlayer($event.detail)"
    x-on:open-audio-player-playlist.window="openPlaylist($event.detail)"
    x-on:keydown.escape.window="if (open) closePlayer()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[9999]"
>
    <audio x-ref="audio" preload="auto" style="display:none"></audio>

    <div class="fixed inset-0 bg-black/60" x-show="open" x-transition.opacity @click="closePlayer()"></div>

    <div class="fixed inset-0 flex items-center justify-center p-4" x-show="open" x-transition @click="closePlayer()">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden"
             @click.stop @touchstart="onTouchStart($event)" @touchend="onTouchEnd($event)">

            <div class="flex items-start justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 gap-2">
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate"
                        x-text="studentName || recordingTitle || '{{ __('recordings.player_title') }}'"></h3>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        <span x-show="teacherName" class="flex items-center gap-1 min-w-0">
                            <i class="ri-user-2-line shrink-0"></i>
                            <span class="truncate" x-text="teacherName"></span>
                        </span>
                        <span x-show="sessionType" class="flex items-center gap-1 min-w-0">
                            <i class="ri-bookmark-line shrink-0"></i>
                            <span class="truncate" x-text="sessionType"></span>
                        </span>
                        <span x-show="sessionDate" class="flex items-center gap-1 min-w-0">
                            <i class="ri-calendar-line shrink-0"></i>
                            <span class="truncate" x-text="sessionDate"></span>
                        </span>
                    </div>
                    <p x-show="playlist.length > 1" class="text-[10px] text-gray-400 mt-0.5"
                       x-text="(currentIndex + 1) + ' / ' + playlist.length"></p>
                </div>
                <button @click="closePlayer()" class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ms-2 shrink-0">
                    <i class="ri-close-line text-gray-400"></i>
                </button>
            </div>

            <div class="px-5 pt-5 pb-2">
                <template x-if="error">
                    <div class="flex items-center justify-center h-16 bg-red-50 dark:bg-red-900/20 rounded-lg text-red-500 text-xs gap-2">
                        <i class="ri-error-warning-line text-base"></i>
                        {{ __('recordings.format_not_supported') }}
                    </div>
                </template>

                <template x-if="!error">
                    <div>
                        <div class="relative h-16">
                            {{-- WaveSurfer.js renders into this container. Always laid out so it
                                 has a measurable width on init, even before peaks are ready. --}}
                            <div x-ref="waveformContainer"
                                 class="absolute inset-0 rounded-lg overflow-hidden cursor-pointer"
                                 dir="ltr"></div>
                            {{-- Placeholder: dotted line, replaced by the real waveform once peaks are decoded. --}}
                            <div x-show="!waveformReady"
                                 class="absolute inset-0 cursor-pointer flex items-center"
                                 @click="seek($event)" @touchend.prevent="seek($event)" dir="ltr">
                                <div class="relative w-full h-px">
                                    <div class="absolute inset-0 border-t border-dotted border-gray-300 dark:border-gray-600"></div>
                                    <div class="absolute top-0 left-0 border-t border-dotted border-blue-500 dark:border-blue-400 transition-all"
                                         :style="'width: ' + progress + '%'"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between text-[10px] text-gray-400 mt-1.5 font-mono" dir="ltr">
                            <span x-text="currentTimeStr"></span>
                            <span x-text="durationStr"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-center gap-3 py-3">
                <button @click="next()" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="hasNext ? 'text-gray-500' : 'text-gray-200 dark:text-gray-600 cursor-default'" :disabled="!hasNext">
                    <i class="ri-skip-forward-fill text-lg"></i>
                </button>
                <button @click="skip(10)" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 transition-colors">
                    <i class="ri-forward-10-line text-xl"></i>
                </button>
                <button @click="toggle()" class="relative w-14 h-14 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg hover:bg-blue-700 transition-colors" :class="error ? 'opacity-50 cursor-not-allowed' : ''">
                    <i x-show="!playing && !loading" class="ri-play-fill text-2xl ms-0.5"></i>
                    <i x-show="playing && !loading" x-cloak class="ri-pause-fill text-2xl"></i>
                    <i x-show="loading" x-cloak class="ri-loader-4-line text-2xl animate-spin"></i>
                </button>
                <button @click="skip(-10)" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 transition-colors">
                    <i class="ri-replay-10-line text-xl"></i>
                </button>
                <button @click="prev()" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="hasPrev ? 'text-gray-500' : 'text-gray-200 dark:text-gray-600 cursor-default'" :disabled="!hasPrev">
                    <i class="ri-skip-back-fill text-lg"></i>
                </button>
            </div>

            <div class="mx-5 flex items-center justify-center gap-3 text-[11px] text-gray-400">
                <span x-show="recordingDate" class="flex items-center gap-1"><i class="ri-calendar-line text-xs"></i><span x-text="recordingDate"></span></span>
                <span x-show="recordingDuration" class="flex items-center gap-1"><i class="ri-time-line text-xs"></i><span x-text="recordingDuration"></span></span>
                <span x-show="recordingSize" class="flex items-center gap-1"><i class="ri-hard-drive-2-line text-xs"></i><span x-text="recordingSize"></span></span>
            </div>

            <div class="px-5 pt-3 pb-5 space-y-2">
                <a :href="downloadUrl" class="flex items-center justify-center gap-2 w-full py-2 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 text-xs font-medium transition-colors border border-gray-200 dark:border-gray-600">
                    <i class="ri-download-line"></i>
                    {{ __('recordings.download_recording') }}
                </a>
                <p class="text-[10px] text-gray-300 dark:text-gray-600 text-center">{{ __('recordings.retention_notice') }}</p>
            </div>
        </div>
    </div>
</div>
@endonce
