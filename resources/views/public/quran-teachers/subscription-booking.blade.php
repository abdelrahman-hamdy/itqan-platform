<x-subscription.page-layout
    :academy="$academy"
    :title="__('public.booking.top_bar.new_subscription') . ' - ' . $package->getDisplayName() . ' - ' . $teacher->full_name">

    <x-booking.top-bar
        :academy="$academy"
        :title="__('public.booking.top_bar.new_subscription')"
        :backRoute="route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id])" />

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
