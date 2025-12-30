<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.register.teacher.success.title') }}</title>

    <!-- Vite Assets (Compiled CSS & JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- RemixIcon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Success Icon and Title -->
            <div class="text-center">
                <div class="mx-auto h-20 w-20 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="ri-checkbox-circle-fill text-green-600 text-4xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    {{ __('auth.register.teacher.success.title') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('auth.register.teacher.success.thank_you') }}
                </p>
            </div>

            <!-- Success Message -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="ri-information-fill {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }} text-blue-600"></i>
                        {{ __('auth.register.teacher.success.what_next_title') }}
                    </h3>

                    <div class="space-y-4 text-sm text-gray-600">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-xs font-bold">1</span>
                                </div>
                            </div>
                            <div class="{{ app()->getLocale() === 'ar' ? 'mr-3' : 'ml-3' }}">
                                <p class="font-medium text-gray-900">{{ __('auth.register.teacher.success.step1_title') }}</p>
                                <p>{{ __('auth.register.teacher.success.step1_description') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-xs font-bold">2</span>
                                </div>
                            </div>
                            <div class="{{ app()->getLocale() === 'ar' ? 'mr-3' : 'ml-3' }}">
                                <p class="font-medium text-gray-900">{{ __('auth.register.teacher.success.step2_title') }}</p>
                                <p>{{ __('auth.register.teacher.success.step2_description') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-xs font-bold">3</span>
                                </div>
                            </div>
                            <div class="{{ app()->getLocale() === 'ar' ? 'mr-3' : 'ml-3' }}">
                                <p class="font-medium text-gray-900">{{ __('auth.register.teacher.success.step3_title') }}</p>
                                <p>{{ __('auth.register.teacher.success.step3_description') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Notes -->
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="ri-alert-fill text-yellow-400"></i>
                    </div>
                    <div class="{{ app()->getLocale() === 'ar' ? 'mr-3' : 'ml-3' }}">
                        <h3 class="text-sm font-medium text-yellow-800">
                            {{ __('auth.register.teacher.success.important_notes') }}
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc {{ app()->getLocale() === 'ar' ? 'list-inside' : 'list-outside ml-4' }} space-y-1">
                                <li>{{ __('auth.register.teacher.success.note1') }}</li>
                                <li>{{ __('auth.register.teacher.success.note2') }}</li>
                                <li>{{ __('auth.register.teacher.success.note3') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4">
                <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
                   class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <i class="ri-login-box-line {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('auth.register.teacher.success.login_button') }}
                </a>

                <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
                   class="w-full flex justify-center py-3 px-4 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <i class="ri-home-line {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('auth.register.teacher.success.home_button') }}
                </a>
            </div>

            <!-- Contact Information -->
            <div class="text-center">
                <p class="text-sm text-gray-500">
                    {{ __('auth.register.teacher.success.contact_text') }}
                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                        {{ __('auth.register.teacher.success.contact_link') }}
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Auto-hide success message after 5 seconds -->
    <script>
        setTimeout(function() {
            const successMessage = document.querySelector('.bg-green-100');
            if (successMessage) {
                successMessage.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html> 