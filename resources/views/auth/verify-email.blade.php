@php
    $academy = auth()->user()->academy ?? \App\Helpers\AcademyHelper::getCurrentAcademy();
@endphp

<x-auth.layout :academy="$academy" :title="__('auth.verification.page_title')" maxWidth="md">
    <div class="text-center">
        {{-- Icon --}}
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 mb-6">
            <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/>
            </svg>
        </div>

        {{-- Title --}}
        <h2 class="text-2xl font-bold text-gray-900 mb-4">
            {{ __('auth.verification.page_title') }}
        </h2>

        {{-- Message --}}
        <p class="text-gray-600 mb-2">
            {{ __('auth.verification.page_message') }}
        </p>
        <p class="text-sm text-gray-500 mb-6">
            {{ __('auth.verification.check_spam') }}
        </p>

        {{-- Success Message --}}
        @if (session('status') === 'verification-link-sent')
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center justify-center gap-2 text-green-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium">{{ __('auth.verification.email_sent') }}</span>
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            {{-- Resend Button --}}
            <form method="POST" action="{{ route('verification.resend', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}">
                @csrf
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    {{ __('auth.verification.resend_button') }}
                </button>
            </form>

            {{-- Logout Button --}}
            <form method="POST" action="{{ route('logout', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}">
                @csrf
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('auth.verification.logout_button') }}
                </button>
            </form>
        </div>

        {{-- Email Display --}}
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500">
                {{ __('common.email') }}:
                <span class="font-medium text-gray-700">{{ auth()->user()->email }}</span>
            </p>
        </div>
    </div>
</x-auth.layout>
