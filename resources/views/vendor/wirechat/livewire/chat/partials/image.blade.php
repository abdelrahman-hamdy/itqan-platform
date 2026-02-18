


@php

   $isSameAsNext = ($message?->sendable_id === $nextMessage?->sendable_id) && ($message?->sendable_type === $nextMessage?->sendable_type);
   $isNotSameAsNext = !$isSameAsNext;
   $isSameAsPrevious = ($message?->sendable_id === $previousMessage?->sendable_id) && ($message?->sendable_type === $previousMessage?->sendable_type);
   $isNotSameAsPrevious = !$isSameAsPrevious;
@endphp



<img @click="$dispatch('open-lightbox', { url: '{{ $attachment?->url }}', type: '{{ $attachment?->mime_type }}' })"
        @class([

        'max-w-max  h-[200px] min-h-[210px] bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)]   object-scale-down  grow-0 shrink  overflow-hidden  rounded-3xl cursor-pointer hover:opacity-90 transition-opacity',

        'rounded-ee-md rounded-se-2xl' => ($isSameAsNext && $isNotSameAsPrevious && $belongsToAuth),

        // Middle message on END
        'rounded-e-md' => ($isSameAsPrevious && $belongsToAuth),

        // Standalone message END
        'rounded-ee-xl rounded-e-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && $belongsToAuth),

        // Last Message on END
        'rounded-ee-2xl' => ($isNotSameAsNext && $belongsToAuth),

        // START (non-auth messages)
        // First message on START
        'rounded-es-md rounded-ss-2xl' => ($isSameAsNext && $isNotSameAsPrevious && !$belongsToAuth),

        // Middle message on START
        'rounded-s-md' => ($isSameAsPrevious && !$belongsToAuth),

        // Standalone message START
        'rounded-es-xl rounded-s-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && !$belongsToAuth),

        // Last message on START
        'rounded-es-2xl' => ($isNotSameAsNext && !$belongsToAuth),
        ])

        loading="lazy" src="{{$attachment?->url}}" alt="{{  __('wirechat::chat.labels.attachment') }}">
