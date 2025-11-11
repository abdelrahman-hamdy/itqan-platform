@props(['message' => 'لا توجد جلسات مسجلة بعد'])

<div class="text-center py-12">
    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-gray-100 mb-4">
        <i class="ri-calendar-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $message }}</h3>
    <p class="text-sm text-gray-500">
        ستظهر الجلسات هنا بمجرد جدولتها أو إكمالها
    </p>
</div>
