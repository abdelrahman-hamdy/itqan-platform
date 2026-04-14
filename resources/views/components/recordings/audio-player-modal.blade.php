{{-- Audio Player Modal — native <audio> with waveform bars, swipe gestures --}}
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
        downloadUrl: '',
        recordingDate: '',
        recordingDuration: '',
        recordingSize: '',
        recordingTitle: '',
        recordingId: 0,
        playlist: [],
        currentIndex: -1,
        bars: [],
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
                this.loading = false;
            });
            a.addEventListener('durationchange', () => {
                if (a.duration && isFinite(a.duration)) this.duration = a.duration;
            });
            a.addEventListener('play', () => { this.playing = true; this.loading = false; });
            a.addEventListener('pause', () => { this.playing = false; });
            a.addEventListener('ended', () => { this.playing = false; if (this.hasNext) this.next(); });
            a.addEventListener('waiting', () => { this.loading = true; });
            a.addEventListener('canplay', () => { this.loading = false; });
        },

        generateBars(id) {
            let seed = id || 1;
            const rand = () => { seed = (seed * 16807 + 0) % 2147483647; return (seed & 0x7fffffff) / 2147483647; };
            this.bars = Array.from({length: 60}, () => 0.2 + rand() * 0.8);
        },

        toggle() {
            const a = this.$refs.audio;
            if (this.error) return;
            if (!a.paused) { a.pause(); return; }
            this.loading = true;
            a.play().catch((e) => {
                this.loading = false;
                // Only show format error for NotSupportedError/unsupported media
                if (e.name === 'NotSupportedError') {
                    this.error = true;
                }
                this.playing = false;
            });
        },

        seek(event) {
            const a = this.$refs.audio;
            if (!this.totalDuration) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const x = event.clientX ?? event.touches?.[0]?.clientX ?? 0;
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
        },

        setTrackData(track) {
            this.audioUrl = track.streamUrl;
            this.downloadUrl = track.downloadUrl;
            this.recordingDate = track.date || '';
            this.recordingDuration = track.duration || '';
            this.recordingSize = track.size || '';
            this.recordingTitle = track.title || '';
            this.recordingId = track.id || 0;
            this.knownDuration = track.durationSeconds || 0;
            this.generateBars(track.id);
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
        },

        closePlayer() {
            this.$refs.audio.pause();
            this.playing = false;
            this.open = false;
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

            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-text="recordingTitle || '{{ __('recordings.player_title') }}'"></h3>
                    <p x-show="playlist.length > 1" class="text-[10px] text-gray-400 mt-0.5" x-text="(currentIndex + 1) + ' / ' + playlist.length"></p>
                </div>
                <button @click="closePlayer()" class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ms-2">
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
                        <div class="relative h-16 rounded-lg cursor-pointer overflow-hidden flex items-end gap-[2px]"
                             @click="seek($event)" @touchend.prevent="seek($event)" dir="ltr">
                            <template x-for="(height, i) in bars" :key="i">
                                <div class="flex-1 rounded-sm transition-colors duration-150"
                                     :style="'height: ' + (height * 100) + '%'"
                                     :class="(i / bars.length * 100) <= progress
                                        ? 'bg-blue-500 dark:bg-blue-400'
                                        : 'bg-gray-200 dark:bg-gray-600'">
                                </div>
                            </template>
                            <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-gray-800/60 rounded-lg">
                                <i class="ri-loader-4-line animate-spin text-xl text-gray-400"></i>
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
                <button @click="toggle()" class="w-14 h-14 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg hover:bg-blue-700 transition-colors" :class="error ? 'opacity-50 cursor-not-allowed' : ''">
                    <i x-show="!playing" class="ri-play-fill text-2xl ms-0.5"></i>
                    <i x-show="playing" x-cloak class="ri-pause-fill text-2xl"></i>
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
