@props([
    'notes',
    'title' => 'ملاحظات الإدارة',
    'visibilityNote' => 'مرئية للإدارة والمعلمين والمشرفين فقط',
    'showBorder' => true,
])

@if($notes)
<div @class(['mt-6 pt-6 border-t border-gray-200' => $showBorder])>
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold text-orange-800 flex items-center">
            <i class="ri-information-line text-orange-600 ms-2"></i>
            {{ $title }}
        </h3>
        @if($visibilityNote)
            <span class="text-xs text-orange-400 italic">{{ $visibilityNote }}</span>
        @endif
    </div>
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
        <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $notes }}</p>
    </div>
</div>
@endif
