@auth
    @unless(auth()->user()->hasVerifiedEmail())
        <div class="bg-amber-50 border-b border-amber-200" role="alert">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2 text-amber-800">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.94 6.412A2 2 0 002 8.108V16a2 2 0 002 2h12a2 2 0 002-2V8.108a2 2 0 00-.94-1.696l-6-3.75a2 2 0 00-2.12 0l-6 3.75zm6.56 6.088a1 1 0 00-1 0l-3 1.875A1 1 0 006 16h8a1 1 0 00.5-1.625l-3-1.875a1 1 0 00-1 0l-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium">
                            {{ __('auth.verification.banner_message') }}
                        </span>
                    </div>
                    <form action="{{ route('verification.resend', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-amber-700 hover:text-amber-900 underline transition-colors">
                            {{ __('auth.verification.resend_link') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endunless
@endauth
