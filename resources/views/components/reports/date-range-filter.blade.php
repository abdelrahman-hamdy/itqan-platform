@props([
    'filterPeriod' => 'all',
    'customStartDate' => '',
    'customEndDate' => '',
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <label for="period" class="block text-sm font-medium text-gray-700 mb-2">الفترة الزمنية</label>
            <select id="period" name="period" class="w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all" {{ $filterPeriod === 'all' ? 'selected' : '' }}>كل الوقت</option>
                <option value="this_month" {{ $filterPeriod === 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                <option value="last_3_months" {{ $filterPeriod === 'last_3_months' ? 'selected' : '' }}>آخر 3 شهور</option>
                <option value="custom" {{ $filterPeriod === 'custom' ? 'selected' : '' }}>فترة مخصصة</option>
            </select>
        </div>

        <div id="customDateRange" class="flex gap-4 flex-1" style="display: {{ $filterPeriod === 'custom' ? 'flex' : 'none' }}">
            <div class="flex-1 min-w-[150px]">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">من تاريخ</label>
                <input type="date" id="start_date" name="start_date" value="{{ $customStartDate }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1 min-w-[150px]">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">إلى تاريخ</label>
                <input type="date" id="end_date" name="end_date" value="{{ $customEndDate }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2">
            <i class="ri-filter-3-line"></i>
            تطبيق الفلتر
        </button>
    </form>
</div>

<script>
    document.getElementById('period').addEventListener('change', function() {
        const customDateRange = document.getElementById('customDateRange');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        if (this.value === 'custom') {
            customDateRange.style.display = 'flex';
        } else {
            customDateRange.style.display = 'none';
            startDateInput.value = '';
            endDateInput.value = '';
        }
    });
</script>
