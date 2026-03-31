{{-- Audio Player Modal with WaveSurfer.js waveform (render once per page) --}}
@once
<div
    x-data="{
        open: false,
        loaded: false,
        playing: false,
        currentTime: '00:00',
        totalTime: '00:00',
        wavesurfer: null,
        audioUrl: '',
        downloadUrl: '',
        recordingDate: '',
        recordingDuration: '',
        recordingSize: '',

        init() {
            this.$watch('open', (val) => {
                if (val && !this.loaded) this.loadWaveSurfer();
                if (!val && this.wavesurfer) { this.wavesurfer.pause(); this.playing = false; }
            });
        },

        async loadWaveSurfer() {
            if (typeof WaveSurfer === 'undefined') {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = 'https://unpkg.com/wavesurfer.js@7';
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            }
            this.loaded = true;
            this.$nextTick(() => this.createPlayer());
        },

        createPlayer() {
            if (this.wavesurfer) this.wavesurfer.destroy();

            this.wavesurfer = WaveSurfer.create({
                container: this.$refs.waveform,
                waveColor: '#e2e8f0',
                progressColor: '#3b82f6',
                cursorColor: '#1d4ed8',
                barWidth: 3,
                barGap: 2,
                barRadius: 3,
                height: 64,
                normalize: true,
            });

            this.wavesurfer.on('ready', () => {
                this.totalTime = this.fmt(this.wavesurfer.getDuration());
            });
            this.wavesurfer.on('timeupdate', (t) => {
                this.currentTime = this.fmt(t);
            });
            this.wavesurfer.on('finish', () => { this.playing = false; });
            this.wavesurfer.on('play', () => { this.playing = true; });
            this.wavesurfer.on('pause', () => { this.playing = false; });

            this.wavesurfer.load(this.audioUrl);
        },

        toggle() { this.wavesurfer?.playPause(); },

        skip(sec) {
            if (!this.wavesurfer) return;
            const cur = this.wavesurfer.getCurrentTime();
            const dur = this.wavesurfer.getDuration();
            this.wavesurfer.setTime(Math.max(0, Math.min(cur + sec, dur)));
        },

        fmt(s) {
            if (!s || isNaN(s)) return '00:00';
            const m = Math.floor(s / 60), ss = Math.floor(s % 60);
            return String(m).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
        },

        openPlayer(detail) {
            this.audioUrl = detail.streamUrl;
            this.downloadUrl = detail.downloadUrl;
            this.recordingDate = detail.date || '';
            this.recordingDuration = detail.duration || '';
            this.recordingSize = detail.size || '';
            this.currentTime = '00:00';
            this.totalTime = '00:00';
            this.open = true;
            if (this.loaded) this.$nextTick(() => this.createPlayer());
        }
    }"
    x-on:open-audio-player.window="openPlayer($event.detail)"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[9999]"
>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/40" x-show="open" x-transition.opacity @click="open = false"></div>

    {{-- Modal wrapper — click on padding area closes --}}
    <div class="fixed inset-0 flex items-center justify-center p-4" x-show="open" x-transition @click="open = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">{{ __('recordings.player_title') }}</h3>
                <button @click="open = false" class="p-1 rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Waveform --}}
            <div class="px-5 pt-4 pb-1">
                <div x-ref="waveform" class="w-full bg-gray-50 rounded-lg overflow-hidden" style="min-height: 64px">
                    <template x-if="!loaded">
                        <div class="flex items-center justify-center h-16 text-gray-400 text-xs">
                            <svg class="animate-spin w-4 h-4 me-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('recordings.loading_player') }}
                        </div>
                    </template>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-1 font-mono" dir="ltr">
                    <span x-text="currentTime"></span>
                    <span x-text="totalTime"></span>
                </div>
            </div>

            {{-- Controls --}}
            <div class="flex items-center justify-center gap-5 py-3">
                <button @click="skip(-10)" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>
                </button>
                <button @click="toggle()" class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center shadow hover:bg-blue-700 transition-colors">
                    <svg x-show="!playing" class="w-6 h-6 ms-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg x-show="playing" x-cloak class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </button>
                <button @click="skip(10)" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>
                </button>
            </div>

            {{-- Metadata --}}
            <div class="mx-5 flex items-center justify-center gap-3 text-[11px] text-gray-400">
                <span x-show="recordingDate" class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span x-text="recordingDate"></span>
                </span>
                <span x-show="recordingDuration" class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="recordingDuration"></span>
                </span>
                <span x-show="recordingSize" class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span x-text="recordingSize"></span>
                </span>
            </div>

            {{-- Download + Retention --}}
            <div class="px-5 pt-3 pb-6 space-y-2">
                <a :href="downloadUrl" class="flex items-center justify-center gap-2 w-full py-2 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs font-medium transition-colors border border-gray-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ __('recordings.download_recording') }}
                </a>
                <p class="text-[10px] text-gray-300 text-center">{{ __('recordings.retention_notice') }}</p>
            </div>
        </div>
    </div>
</div>
@endonce
