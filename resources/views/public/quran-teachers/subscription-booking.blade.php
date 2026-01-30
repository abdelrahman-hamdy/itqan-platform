<x-subscription.page-layout
    :academy="$academy"
    :title="__('public.booking.top_bar.new_subscription') . ' - ' . $package->getDisplayName() . ' - ' . $teacher->full_name">

    <x-booking.top-bar
        :academy="$academy"
        :title="__('public.booking.top_bar.new_subscription')"
        :backRoute="route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id])" />

    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="container mx-auto px-4 max-w-4xl mt-4">
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-3" role="alert">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="container mx-auto px-4 max-w-4xl mt-4">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3" role="alert">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <section class="py-8">
        <div class="container mx-auto px-4 max-w-4xl">

            {{-- Teacher & Package Info --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <x-subscription.teacher-info-card :teacher="$teacher" teacherType="quran" />
                <x-subscription.package-info-card :package="$package" packageType="quran" :selectedPeriod="$selectedPeriod ?? 'monthly'" />
            </div>

            {{-- Subscription Form --}}
            <x-subscription.booking-form
                type="quran"
                :teacher="$teacher"
                :package="$package"
                :academy="$academy"
                :formAction="route('quran-teachers.subscribe.submit', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id, 'packageId' => $package->id])"
                :cancelUrl="route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id])"
                :selectedPeriod="$selectedPeriod ?? 'monthly'" />

        </div>
    </section>

</x-subscription.page-layout>
