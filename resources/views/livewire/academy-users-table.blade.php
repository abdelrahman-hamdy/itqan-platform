<div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
    <!-- Search Bar -->
    <div class="fi-ta-header-ctn divide-y divide-gray-200 dark:divide-white/10">
        <div class="flex flex-col gap-3 p-4 sm:px-6 sm:flex-row sm:items-center">
            <div class="grid flex-1 gap-y-1">
                <div class="flex items-center gap-x-3">
                    <h3 class="fi-ta-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        مستخدمو الأكاديمية
                    </h3>
                </div>
            </div>
            
            <div class="flex shrink-0 items-center gap-x-3">
                <div class="fi-ta-search-field">
                    <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                        <input 
                            type="text" 
                            wire:model.live="search" 
                            placeholder="البحث بالاسم أو البريد الإلكتروني..."
                            class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3"
                        >
                    </div>
                </div>
                
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    إجمالي: {{ $users->total() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:border-t-white/10">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr class="divide-x divide-gray-200 dark:divide-white/5">
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                الاسم
                            </span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                البريد الإلكتروني
                            </span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                الدور
                            </span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                الحالة
                            </span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                تاريخ التسجيل
                            </span>
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap justify-start">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                الإجراءات
                            </span>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                @forelse($users as $user)
                    <tr class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        <div class="flex items-center gap-1.5">
                                            <div class="fi-avatar flex items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400" style="height: 2rem; width: 2rem;">
                                                <span class="text-sm font-medium">
                                                    {{ substr($user->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div class="flex max-w-max flex-col">
                                                <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                                    <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
                                                        {{ $user->name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                            <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
                                                {{ $user->email }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        @php
                                            $roleColors = [
                                                'academy_admin' => 'primary',
                                                'teacher' => 'success',
                                                'student' => 'info',
                                                'parent' => 'warning',
                                                'supervisor' => 'gray',
                                            ];
                                            $roleNames = [
                                                'academy_admin' => 'مدير أكاديمية',
                                                'teacher' => 'مدرس',
                                                'student' => 'طالب',
                                                'parent' => 'ولي أمر',
                                                'supervisor' => 'مشرف',
                                            ];
                                            $color = $roleColors[$user->role] ?? 'gray';
                                        @endphp
                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                            <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-h-6 fi-color-{{ $color }} fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30" style="--c-50:var(--{{ $color }}-50);--c-400:var(--{{ $color }}-400);--c-600:var(--{{ $color }}-600);">
                                                {{ $roleNames[$user->role] ?? $user->role }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                            @if($user->is_active)
                                                <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-h-6 fi-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                                    نشط
                                                </span>
                                            @else
                                                <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-h-6 fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                                    غير نشط
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                            <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
                                                {{ $user->created_at->format('Y-m-d H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        @if(in_array($user->role, ['teacher', 'student', 'parent']))
                                            <a href="#" class="fi-link group/link relative inline-flex items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-link-size-sm fi-size-sm gap-1.5 px-3 py-2 text-sm text-custom-600 hover:text-custom-500 focus-visible:ring-custom-600/50 dark:text-custom-400 dark:hover:text-custom-300 dark:focus-visible:ring-custom-400/50 fi-color-primary" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">
                                                <span class="fi-link-label">
                                                    عرض الملف
                                                </span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="fi-ta-placeholder-row fi-ta-row">
                        <td colspan="6" class="fi-ta-placeholder p-6">
                            <div class="fi-ta-placeholder-content flex flex-col items-center justify-center gap-4 text-center">
                                <div class="fi-ta-placeholder-icon-ctn flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                    <svg class="fi-ta-placeholder-icon h-6 w-6 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                </div>
                                <div class="grid gap-1 text-center">
                                    <p class="fi-ta-placeholder-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                        لا توجد مستخدمين
                                    </p>
                                    <p class="fi-ta-placeholder-description text-sm text-gray-500 dark:text-gray-400">
                                        لم يتم العثور على مستخدمين في هذه الأكاديمية.
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
        <div class="fi-ta-footer-ctn border-t border-gray-200 px-3 py-4 dark:border-white/10 sm:px-6">
            {{ $users->links() }}
        </div>
    @endif
</div>
