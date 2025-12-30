<x-auth.layout title="{{ __('auth.forgot_password.title') }}" subtitle="{{ __('auth.forgot_password.subtitle') }}" :academy="$academy">
    <!-- Success Message -->
    @if(session('status'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <i class="ri-checkbox-circle-fill text-green-500 text-xl ms-3"></i>
                <p class="text-sm text-green-800">{{ session('status') }}</p>
            </div>
        </div>
    @endif

    <!-- Forgot Password Form -->
    <form method="POST"
          action="{{ route('password.email', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
          x-data="{ loading: false }"
          @submit="loading = true">
        @csrf

        <div class="space-y-5">
            <!-- Info Text -->
            <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg">
                <div class="flex">
                    <i class="ri-information-line text-blue-500 text-xl ms-3 flex-shrink-0"></i>
                    <p class="text-sm text-blue-800">
                        {{ __('auth.forgot_password.info') }}
                    </p>
                </div>
            </div>

            <!-- Email Input -->
            <x-auth.input
                label="{{ __('auth.forgot_password.email') }}"
                name="email"
                type="email"
                icon="ri-mail-line"
                placeholder="{{ __('auth.forgot_password.email_placeholder') }}"
                :required="true"
                autocomplete="email"
            />

            <!-- Submit Button -->
            <button type="submit"
                    :class="{ 'btn-loading': loading }"
                    :disabled="loading"
                    class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-medium rounded-button hover:shadow-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-smooth disabled:opacity-70 disabled:cursor-not-allowed">
                <i class="ri-mail-send-line text-lg"></i>
                <span>{{ __('auth.forgot_password.submit') }}</span>
            </button>
        </div>
    </form>

    <!-- Divider -->
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-gray-500">{{ __('auth.forgot_password.or') }}</span>
        </div>
    </div>

    <!-- Back to Login -->
    <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
       class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-button hover:bg-gray-200 transition-smooth">
        <i class="ri-arrow-left-line text-lg rtl:rotate-180"></i>
        <span>{{ __('auth.forgot_password.back_to_login') }}</span>
    </a>

    <x-slot name="footer">
        <p class="text-sm text-gray-600">
            {{ __('auth.footer.rights') }} {{ date('Y') }} {{ $academy ? $academy->name : __('auth.footer.platform_name') }}
        </p>
    </x-slot>
</x-auth.layout>
