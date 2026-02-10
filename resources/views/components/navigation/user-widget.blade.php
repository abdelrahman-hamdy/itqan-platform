{{--
    Unified User Widget Component
    Used in both topbar.blade.php (main landing page) and public-nav.blade.php (public resources pages)

    Props:
    - academy: The academy model (required)
    - height: CSS height class (default: 'h-20')
--}}
@props([
    'academy',
    'height' => 'h-20',
])

@auth
@php
    $user = auth()->user();
    $isAdminOrSuperAdminOrSupervisor = $user->isAdmin() || $user->isSuperAdmin() || $user->isSupervisor();
    $profileRouteName = $user->isTeacher() ? 'teacher.profile' : 'student.profile';

    // Dashboard route for admin roles
    $dashboardRoute = match($user->user_type) {
        'supervisor' => route('filament.supervisor.pages.dashboard'),
        'admin' => '/panel',
        'super_admin' => route('filament.admin.pages.dashboard'),
        default => '/panel'
    };
@endphp

{{-- Sessions Icon for Supervisors & SuperAdmins (Desktop) --}}
@if($user->isSupervisor() || $user->isSuperAdmin())
@php
    $sessionsUrl = $user->isSuperAdmin()
        ? url('/admin/live-sessions')
        : url('/supervisor-panel/monitored-all-sessions');
@endphp
<a href="{{ $sessionsUrl }}"
   target="_blank"
   class="relative hidden md:flex items-center justify-center {{ $height }} px-3 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200"
   aria-label="{{ __('supervisor.observation.observe_session') }}">
    <i class="ri-eye-line text-xl"></i>
</a>
@endif

{{-- Chat Icon for Supervisors (Desktop) --}}
@if($user->user_type === 'supervisor')
<a href="{{ route('chats', ['subdomain' => $academy->subdomain]) }}"
   class="relative hidden md:flex items-center justify-center {{ $height }} px-3 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200"
   aria-label="{{ __('chat.messages') }}">
    <i class="ri-message-3-line text-xl"></i>
    {{-- Unread badge --}}
    @php
        $unreadCount = $user->unreadMessagesCount();
    @endphp
    @if($unreadCount > 0)
        <span class="absolute top-4 -end-1 flex items-center justify-center min-w-[18px] h-[18px] text-xs font-bold text-white bg-red-500 rounded-full px-1">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
    @endif
</a>
@endif

{{-- Desktop User Dropdown --}}
<div class="relative {{ $height }} hidden md:flex items-center" x-data="{ open: false }">
    <button @click="open = !open"
            class="flex items-center gap-2 {{ $height }} px-3 text-gray-700 hover:text-primary hover:bg-gray-50 focus:outline-none transition-colors duration-200"
            aria-label="{{ __('academy.nav.user_menu') }}"
            aria-expanded="false">
        <x-avatar :user="$user" size="xs" />
        <span class="hidden sm:block text-sm font-medium">{{ $user->name }}</span>
        <i class="ri-arrow-down-s-line text-sm transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open"
         x-cloak
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute rtl:right-0 ltr:left-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg z-50 overflow-hidden"
         role="menu">
        <div class="py-1">
            {{-- User Info Header --}}
            <div class="px-4 py-2 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                @if($isAdminOrSuperAdminOrSupervisor)
                <p class="text-xs text-primary mt-1">{{ $user->getUserTypeLabel() }}</p>
                @endif
            </div>

            @if($isAdminOrSuperAdminOrSupervisor)
                {{-- Dashboard Link (opens in new tab) for Admin/SuperAdmin/Supervisor --}}
                <a href="{{ $dashboardRoute }}"
                   target="_blank"
                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                   role="menuitem">
                    <i class="ri-dashboard-line ms-2"></i>
                    {{ __('components.navigation.public.dashboard') }}
                    <i class="ri-external-link-line text-gray-400 ms-auto text-xs"></i>
                </a>

                {{-- Sessions Monitoring Link for Supervisors & SuperAdmins --}}
                @if($user->isSupervisor() || $user->isSuperAdmin())
                <a href="{{ $user->isSuperAdmin() ? url('/admin/live-sessions') : url('/supervisor-panel/monitored-all-sessions') }}"
                   target="_blank"
                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                   role="menuitem">
                    <i class="ri-eye-line ms-2"></i>
                    {{ __('supervisor.observation.observe_session') }}
                    <i class="ri-external-link-line text-gray-400 ms-auto text-xs"></i>
                </a>
                @endif
            @else
                {{-- Profile Link for Students/Teachers --}}
                <a href="{{ route($profileRouteName, ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                   role="menuitem">
                    <i class="ri-user-line ms-2"></i>
                    {{ __('academy.user.profile') }}
                </a>
            @endif

            <div class="border-t border-gray-100"></div>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="block">
                @csrf
                <button type="submit"
                        class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200"
                        role="menuitem">
                    <i class="ri-logout-box-line ms-2"></i>
                    {{ __('academy.user.logout') }}
                </button>
            </form>
        </div>
    </div>
</div>
@else
{{-- Login Button for Guests (Desktop) --}}
<a href="{{ route('login', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
   class="hidden md:flex items-center {{ $height }} px-4 text-primary hover:text-primary/80 hover:bg-primary/5 transition-colors duration-200 font-medium"
   aria-label="{{ __('academy.user.login') }}">
    <i class="ri-login-box-line ms-2"></i>
    {{ __('academy.user.login') }}
</a>
@endauth
