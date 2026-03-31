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
                waveColor: '#cbd5e1',
                progressColor: '#3b82f6',
                cursorColor: '#1e40af',
                barWidth: 3,
                barGap: 2,
                barRadius: 3,
                height: 80,
                normalize: true,
                backend: 'WebAudio',
            });

            this.wavesurfer.on('ready', () => {
                this.totalTime = this.fmt(this.wavesurfer.getDuration());
            });
            this.wavesurfer.on('audioprocess', () => {
                this.currentTime = this.fmt(this.wavesurfer.getCurrentTime());
            });
            this.wavesurfer.on('seeking', () => {
                this.currentTime = this.fmt(this.wavesurfer.getCurrentTime());
            });
            this.wavesurfer.on('finish', () => { this.playing = false; });
            this.wavesurfer.on('play', () => { this.playing = true; });
            this.wavesurfer.on('pause', () => { this.playing = false; });

            this.wavesurfer.load(this.audioUrl);
        },

        toggle() { this.wavesurfer?.playPause(); },
        skip(sec) { this.wavesurfer?.skip(sec); },

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
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" x-show="open" x-transition.opacity @click="open = false"></div>

    {{-- Modal --}}
    <div class="fixed inset-0 flex items-center justify-center p-4" x-show="open" x-transition>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-bold text-gray-900">{{ __('recordings.player_title') }}</h3>
                <button @click="open = false" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Waveform --}}
            <div class="px-5 pt-5 pb-2">
                <div x-ref="waveform" class="w-full rounded-lg overflow-hidden" style="min-height: 80px">
                    <template x-if="!loaded">
                        <div class="flex items-center justify-center h-20 text-gray-400 text-sm">
                            <svg class="animate-spin w-5 h-5 me-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('recordings.loading_player') }}
                        </div>
                    </template>
                </div>

                {{-- Time --}}
                <div class="flex justify-between text-xs text-gray-400 mt-1 font-mono" dir="ltr">
                    <span x-text="currentTime"></span>
                    <span x-text="totalTime"></span>
                </div>
            </div>

            {{-- Controls --}}
            <div class="flex items-center justify-center gap-4 py-3">
                <button @click="skip(-10)" class="p-2 rounded-full hover:bg-gray-100 text-gray-500 transition-colors" title="-10s">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.066 2v1.982a8 8 0 110 16.035 8 8 0 01-7.651-5.636l1.888-.644A6 6 0 1012.066 5.98V8l-4-3 4-3zm-1.6 7v6l5-3-5-3z"/></svg>
                </button>
                <button @click="toggle()" class="w-14 h-14 rounded-full bg-primary text-white flex items-center justify-center shadow-lg hover:shadow-xl transition-all hover:scale-105">
                    <svg x-show="!playing" class="w-7 h-7 ms-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg x-show="playing" class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </button>
                <button @click="skip(10)" class="p-2 rounded-full hover:bg-gray-100 text-gray-500 transition-colors" title="+10s">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.934 2v1.982a8 8 0 100 16.035 8 8 0 007.651-5.636l-1.888-.644A6 6 0 1111.934 5.98V8l4-3-4-3zm1.6 7v6l-5-3 5-3z"/></svg>
                </button>
            </div>

            {{-- Metadata --}}
            <div class="mx-5 mb-3 flex items-center justify-center gap-4 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                <span x-show="recordingDate" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span x-text="recordingDate"></span>
                </span>
                <span x-show="recordingDuration" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="recordingDuration"></span>
                </span>
                <span x-show="recordingSize" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"/></svg>
                    <span x-text="recordingSize"></span>
                </span>
            </div>

            {{-- Download + Retention --}}
            <div class="px-5 pb-5 space-y-2">
                <a :href="downloadUrl" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ __('recordings.download_recording') }}
                </a>
                <p class="text-[10px] text-gray-400 text-center">{{ __('recordings.retention_notice') }}</p>
            </div>
        </div>
    </div>
</div>
@endonce
