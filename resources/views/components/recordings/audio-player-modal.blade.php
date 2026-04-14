{{-- Audio Player Modal — native <audio> + Alpine.js (render once per page) --}}
@once
<div
    x-data="{
        open: false,
        playing: false,
        loading: false,
        error: false,
        currentTime: 0,
        duration: 0,
        audioUrl: '',
        downloadUrl: '',
        recordingDate: '',
        recordingDuration: '',
        recordingSize: '',
        recordingTitle: '',
        playlist: [],
        currentIndex: -1,

        get hasPrev() { return this.currentIndex > 0; },
        get hasNext() { return this.currentIndex < this.playlist.length - 1; },
        get progress() { return this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0; },
        get currentTimeStr() { return this.fmt(this.currentTime); },
        get durationStr() { return this.fmt(this.duration); },

        init() {
            const a = this.$refs.audio;
            a.addEventListener('timeupdate', () => { this.currentTime = a.currentTime; });
            a.addEventListener('loadedmetadata', () => { this.duration = a.duration; this.loading = false; this.error = false; });
            a.addEventListener('play', () => { this.playing = true; });
            a.addEventListener('pause', () => { this.playing = false; });
            a.addEventListener('ended', () => { this.playing = false; if (this.hasNext) this.next(); });
            a.addEventListener('error', () => { this.loading = false; this.error = true; this.playing = false; });
            a.addEventListener('waiting', () => { this.loading = true; });
            a.addEventListener('canplay', () => { this.loading = false; });
        },

        toggle() {
            const a = this.$refs.audio;
            if (this.error) return;
            if (a.paused) { a.play().catch(() => { this.error = true; }); }
            else { a.pause(); }
        },

        seek(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const pct = Math.max(0, Math.min(1, x / rect.width));
            // RTL: progress bar is always LTR, so no flip needed
            this.$refs.audio.currentTime = pct * this.duration;
        },

        skip(sec) {
            const a = this.$refs.audio;
            a.currentTime = Math.max(0, Math.min(a.currentTime + sec, this.duration));
        },

        next() {
            if (!this.hasNext) return;
            this.loadTrack(this.currentIndex + 1);
        },

        prev() {
            if (!this.hasPrev) return;
            this.loadTrack(this.currentIndex - 1);
        },

        loadTrack(index) {
            const track = this.playlist[index];
            if (!track) return;
            this.currentIndex = index;
            this.setTrackData(track);
            this.loadAudio();
        },

        setTrackData(track) {
            this.audioUrl = track.streamUrl;
            this.downloadUrl = track.downloadUrl;
            this.recordingDate = track.date || '';
            this.recordingDuration = track.duration || '';
            this.recordingSize = track.size || '';
            this.recordingTitle = track.title || '';
        },

        loadAudio() {
            const a = this.$refs.audio;
            this.currentTime = 0;
            this.duration = 0;
            this.playing = false;
            this.loading = true;
            this.error = false;
            a.src = this.audioUrl;
            a.load();
        },

        openPlayer(detail) {
            this.playlist = [detail];
            this.currentIndex = 0;
            this.setTrackData(detail);
            this.open = true;
            this.$nextTick(() => this.loadAudio());
        },

        openPlaylist(detail) {
            this.playlist = detail.playlist || [];
            this.currentIndex = detail.startIndex || 0;
            const track = this.playlist[this.currentIndex];
            if (!track) return;
            this.setTrackData(track);
            this.open = true;
            this.$nextTick(() => this.loadAudio());
        },

        closePlayer() {
            this.$refs.audio.pause();
            this.playing = false;
            this.open = false;
        },

        fmt(s) {
            if (!s || isNaN(s)) return '00:00';
            const m = Math.floor(s / 60), ss = Math.floor(s % 60);
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
    {{-- Hidden audio element --}}
    <audio x-ref="audio" preload="metadata" crossorigin="anonymous"></audio>

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/60" x-show="open" x-transition.opacity @click="closePlayer()"></div>

    {{-- Modal --}}
    <div class="fixed inset-0 flex items-center justify-center p-4" x-show="open" x-transition @click="closePlayer()">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" x-text="recordingTitle || '{{ __('recordings.player_title') }}'"></h3>
                    <p x-show="playlist.length > 1" class="text-[10px] text-gray-400 mt-0.5" x-text="(currentIndex + 1) + ' / ' + playlist.length"></p>
                </div>
                <button @click="closePlayer()" class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ms-2">
                    <i class="ri-close-line text-gray-400"></i>
                </button>
            </div>

            {{-- Progress Bar --}}
            <div class="px-5 pt-5 pb-2">
                {{-- Error state --}}
                <template x-if="error">
                    <div class="flex items-center justify-center h-16 bg-red-50 dark:bg-red-900/20 rounded-lg text-red-500 text-xs gap-2">
                        <i class="ri-error-warning-line text-base"></i>
                        {{ __('recordings.format_not_supported') }}
                    </div>
                </template>

                {{-- Loading state --}}
                <template x-if="loading && !error">
                    <div class="flex items-center justify-center h-16 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-400 text-xs gap-2">
                        <i class="ri-loader-4-line animate-spin text-base"></i>
                        {{ __('recordings.loading_player') }}
                    </div>
                </template>

                {{-- Progress bar (clickable to seek) --}}
                <template x-if="!loading && !error">
                    <div>
                        <div class="relative h-10 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer group overflow-hidden" @click="seek($event)" dir="ltr">
                            {{-- Filled progress --}}
                            <div class="absolute inset-y-0 start-0 bg-blue-500/20 dark:bg-blue-500/30 transition-all duration-100 rounded-lg"
                                 :style="'width: ' + progress + '%'"></div>
                            {{-- Progress handle --}}
                            <div class="absolute top-1/2 -translate-y-1/2 w-3 h-3 bg-blue-600 rounded-full shadow transition-all duration-100 opacity-0 group-hover:opacity-100"
                                 :style="'left: calc(' + progress + '% - 6px)'"></div>
                            {{-- Center time display --}}
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xs font-mono text-gray-500 dark:text-gray-400" x-text="currentTimeStr + ' / ' + durationStr"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Controls --}}
            <div class="flex items-center justify-center gap-4 py-3">
                <button @click="prev()" class="p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="hasPrev ? 'text-gray-500' : 'text-gray-200 dark:text-gray-600 cursor-default'" :disabled="!hasPrev">
                    <i class="ri-skip-back-fill text-lg"></i>
                </button>
                <button @click="skip(-10)" class="p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 transition-colors" title="-10s">
                    <i class="ri-replay-10-line text-xl"></i>
                </button>
                <button @click="toggle()" class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center shadow hover:bg-blue-700 transition-colors" :class="error ? 'opacity-50 cursor-not-allowed' : ''">
                    <i x-show="!playing" class="ri-play-fill text-2xl ms-0.5"></i>
                    <i x-show="playing" x-cloak class="ri-pause-fill text-2xl"></i>
                </button>
                <button @click="skip(10)" class="p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 transition-colors" title="+10s">
                    <i class="ri-forward-10-line text-xl"></i>
                </button>
                <button @click="next()" class="p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="hasNext ? 'text-gray-500' : 'text-gray-200 dark:text-gray-600 cursor-default'" :disabled="!hasNext">
                    <i class="ri-skip-forward-fill text-lg"></i>
                </button>
            </div>

            {{-- Metadata --}}
            <div class="mx-5 flex items-center justify-center gap-3 text-[11px] text-gray-400">
                <span x-show="recordingDate" class="flex items-center gap-1">
                    <i class="ri-calendar-line text-xs"></i>
                    <span x-text="recordingDate"></span>
                </span>
                <span x-show="recordingDuration" class="flex items-center gap-1">
                    <i class="ri-time-line text-xs"></i>
                    <span x-text="recordingDuration"></span>
                </span>
                <span x-show="recordingSize" class="flex items-center gap-1">
                    <i class="ri-hard-drive-2-line text-xs"></i>
                    <span x-text="recordingSize"></span>
                </span>
            </div>

            {{-- Download --}}
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
