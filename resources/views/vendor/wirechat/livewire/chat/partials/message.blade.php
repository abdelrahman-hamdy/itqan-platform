@use('Wirechat\Wirechat\Facades\Wirechat')


@php

   $isSameAsNext = ($message?->sendable_id === $nextMessage?->sendable_id) && ($message?->sendable_type === $nextMessage?->sendable_type);
   $isNotSameAsNext = !$isSameAsNext;
   $isSameAsPrevious = ($message?->sendable_id === $previousMessage?->sendable_id) && ($message?->sendable_type === $previousMessage?->sendable_type);
   $isNotSameAsPrevious = !$isSameAsPrevious;
@endphp

<div


{{-- We use style here to make it easy for dynamic and safe injection --}}
@style([
'background-color:var(--wc-brand-primary)' => $belongsToAuth==true
])

@class([
    'flex flex-wrap max-w-fit text-[15px] border border-gray-200/40 dark:border-gray-700 rounded-xl p-2.5 flex flex-col text-black',
    'text-white' => $belongsToAuth, // Background color for messages sent by the authenticated user
    'bg-white dark:bg-gray-800 dark:text-white shadow-sm' => !$belongsToAuth,

    // Message styles based on position and ownership (using logical properties for RTL support)

    // END (auth messages - right in LTR, left in RTL)
    // First message on END
    'rounded-ee-md rounded-se-2xl' => ($isSameAsNext && $isNotSameAsPrevious && $belongsToAuth),

    // Middle message on END
    'rounded-e-md' => ($isSameAsPrevious && $belongsToAuth),

    // Standalone message END
    'rounded-ee-xl rounded-e-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && $belongsToAuth),

    // Last Message on END
    'rounded-ee-2xl' => ($isNotSameAsNext && $belongsToAuth),

    // START (non-auth messages - left in LTR, right in RTL)
    // First message on START
    'rounded-es-md rounded-ss-2xl' => ($isSameAsNext && $isNotSameAsPrevious && !$belongsToAuth),

    // Middle message on START
    'rounded-s-md' => ($isSameAsPrevious && !$belongsToAuth),

    // Standalone message START
    'rounded-es-xl rounded-s-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && !$belongsToAuth),

    // Last message on START
    'rounded-es-2xl' => ($isNotSameAsNext && !$belongsToAuth),
])
>
@if (!$belongsToAuth && $isGroup)
<div
    @class([
        'shrink-0 font-medium text-purple-500',
        // Hide avatar if the next message is from the same user
        'hidden' => $isSameAsPrevious
    ])>
    {{ $message?->sendable?->display_name }}
</div>
@endif

<pre class="whitespace-pre-line break-all tracking-normal text-sm md:text-base dark:text-white lg:tracking-normal"
    style="font-family: inherit;">
    {{$message?->body}}
</pre>

{{-- Display the created time based on different conditions --}}
<span
@class(['text-[11px] ms-auto ',  'text-gray-700 dark:text-gray-300' => !$belongsToAuth,'text-gray-100' => $belongsToAuth])>
    @php
        // If the message was created today, show only the time (e.g., 1:00 AM)
        echo $message?->created_at->format('H:i');
    @endphp
</span>

</div>
