<x-layouts.teacher :title="__('teacher.recordings.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $filterOptions = [];
    if ($courses->isNotEmpty()) {
        $filterOptions[''] = __('teacher.recordings.all_courses');
        foreach ($courses as $courseId => $courseTitle) {
            $filterOptions[(string) $courseId] = $courseTitle;
        }
    }

    $stats = [
        [
            'icon' => 'ri-video-line',
            'bgColor' => 'bg-purple-100',
            'iconColor' => 'text-purple-600',
            'value' => $totalRecordings ?? 0,
            'label' => __('teacher.recordings.total_recordings'),
        ],
    ];
@endphp

    <x-teacher.entity-list-page
        :title="__('teacher.recordings.page_title')"
        :subtitle="__('teacher.recordings.page_description')"
        :items="$recordings"
        :stats="$stats"
        :filter-options="$filterOptions"
        filter-param="course_id"
        :breadcrumbs="[['label' => __('teacher.recordings.breadcrumb')]]"
        theme-color="purple"
        :list-title="__('teacher.recordings.list_title')"
        empty-icon="ri-video-line"
        :empty-title="__('teacher.recordings.empty_title')"
        :empty-description="__('teacher.recordings.empty_description')"
        :empty-filter-description="__('teacher.recordings.empty_filter_description')"
        :clear-filter-route="route('teacher.recordings.index', ['subdomain' => $subdomain])"
        :clear-filter-text="__('teacher.recordings.view_all')"
    >
        @foreach($recordings as $recording)
            @php
                $session = $recording->recordable;
                $courseTitle = $session?->course?->title ?? __('teacher.recordings.unknown_course');
                $sessionTitle = $session?->title ?? $recording->display_name;

                $metadata = [
                    ['icon' => 'ri-book-open-line', 'text' => $courseTitle],
                ];

                if ($recording->started_at) {
                    $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $recording->started_at->format('Y/m/d')];
                    $metadata[] = ['icon' => 'ri-time-line', 'text' => $recording->started_at->format('H:i')];
                }

                if ($recording->duration) {
                    $metadata[] = ['icon' => 'ri-timer-line', 'text' => $recording->formatted_duration];
                }

                if ($recording->file_size) {
                    $metadata[] = ['icon' => 'ri-hard-drive-line', 'text' => $recording->formatted_file_size];
                }

                $actions = [];
                if ($recording->isAvailable()) {
                    $streamUrl = $recording->getStreamUrl();
                    $downloadUrl = $recording->getDownloadUrl();

                    if ($streamUrl) {
                        $actions[] = [
                            'href' => $streamUrl,
                            'icon' => 'ri-play-circle-line',
                            'label' => __('teacher.recordings.play'),
                            'shortLabel' => __('teacher.recordings.play'),
                            'class' => 'bg-purple-600 hover:bg-purple-700 text-white',
                        ];
                    }
                    if ($downloadUrl) {
                        $actions[] = [
                            'href' => $downloadUrl,
                            'icon' => 'ri-download-line',
                            'label' => __('teacher.recordings.download'),
                            'shortLabel' => __('teacher.recordings.download'),
                            'class' => 'bg-gray-100 hover:bg-gray-200 text-gray-700',
                        ];
                    }
                }
            @endphp

            <x-teacher.entity-list-item
                :title="$sessionTitle"
                :metadata="$metadata"
                :actions="$actions"
                icon="ri-video-line"
                icon-bg-class="bg-gradient-to-br from-purple-500 to-purple-600"
            />
        @endforeach
    </x-teacher.entity-list-page>
</x-layouts.teacher>
