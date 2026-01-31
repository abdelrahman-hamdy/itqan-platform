<x-auth.layout :academy="$academy" :title="__('auth.verification.success_title')" maxWidth="md">
    <div class="text-center">
        {{-- Success Icon --}}
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
            <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        {{-- Title --}}
        <h2 class="text-2xl font-bold text-gray-900 mb-4">
            {{ __('auth.verification.success_title') }}
        </h2>

        {{-- Message --}}
        <p class="text-gray-600 mb-8">
            {{ __('auth.verification.success_message') }}
        </p>

        {{-- Login Button --}}
        <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center justify-center gap-2 px-8 py-3 bg-gradient-to-r from-primary to-secondary text-white font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            {{ __('auth.verification.login_now') }}
        </a>

        {{-- Additional Info --}}
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500">
                {{ __('auth.verification.can_now_access') }}
            </p>
        </div>
    </div>
</x-auth.layout>
