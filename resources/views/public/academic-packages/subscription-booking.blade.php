<x-subscription.page-layout
    :academy="$academy"
    :title="__('public.booking.academic.title', ['default' => 'اشتراك أكاديمي جديد']) . ' - ' . $package->name . ' - ' . $teacher->user->name">

    <x-booking.top-bar
        :academy="$academy"
        :title="__('public.booking.academic.title', ['default' => 'اشتراك أكاديمي جديد'])"
        :backRoute="route('public.academic-packages.teacher', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id])" />

    <section class="py-8">
        <div class="container mx-auto px-4 max-w-4xl">

            {{-- Teacher & Package Info --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <x-subscription.teacher-info-card :teacher="$teacher" teacherType="academic" />
                <x-subscription.package-info-card :package="$package" packageType="academic" :selectedPeriod="$selectedPeriod ?? 'monthly'" />
            </div>

            {{-- Subscription Form --}}
            <x-subscription.booking-form
                type="academic"
                :teacher="$teacher"
                :package="$package"
                :academy="$academy"
                :subjects="$teacher->subjects->pluck('name', 'id')->toArray()"
                :gradeLevels="$teacher->gradeLevels->pluck('name', 'id')->toArray()"
                :formAction="route('public.academic-packages.subscribe.submit', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id, 'packageId' => $package->id])"
                :cancelUrl="route('public.academic-packages.teacher', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id])"
                :selectedPeriod="$selectedPeriod ?? 'monthly'" />

        </div>
    </section>

</x-subscription.page-layout>
