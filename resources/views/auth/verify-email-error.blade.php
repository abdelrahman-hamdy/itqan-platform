<x-auth.layout :academy="$academy" :title="__('auth.verification.error_title')" maxWidth="md">
    <div class="text-center">
        {{-- Error Icon --}}
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-6">
            @if($error === 'expired')
                <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @else
                <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            @endif
        </div>

        {{-- Title --}}
        <h2 class="text-2xl font-bold text-gray-900 mb-4">
            @if($error === 'expired')
                {{ __('auth.verification.link_expired_title') }}
            @else
                {{ __('auth.verification.invalid_link_title') }}
            @endif
        </h2>

        {{-- Message --}}
        <p class="text-gray-600 mb-8">
            {{ $message }}
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            {{-- Login Button --}}
            <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                {{ __('auth.verification.go_to_login') }}
            </a>
        </div>

        {{-- Help Text --}}
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500">
                {{ __('auth.verification.request_new_link') }}
            </p>
        </div>
    </div>
</x-auth.layout>
