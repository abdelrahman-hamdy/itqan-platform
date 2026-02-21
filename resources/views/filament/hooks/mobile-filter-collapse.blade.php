<script>
(function () {
    var MOBILE_BP = 768;

    function buildToggle() {
        var wrapper = document.createElement('div');
        wrapper.className = 'fi-ta-filter-mobile-toggle-wrapper';
        wrapper.innerHTML =
            '<span class="fi-ta-filter-mobile-toggle-label">الفلاتر</span>' +
            '<button type="button" class="fi-ta-filter-mobile-toggle-btn">' +
                '<span class="fi-ta-filter-toggle-text">إظهار</span>' +
                '<svg class="fi-ta-filter-toggle-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                    '<path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>' +
                '</svg>' +
            '</button>';
        return wrapper;
    }

    function initContainer(container) {
        if (container.querySelector('.fi-ta-filter-mobile-toggle-wrapper')) return;

        var filtersEl = container.querySelector('.fi-ta-filters');
        if (!filtersEl) return;

        var isOpen = false;
        var wrapper = buildToggle();
        var btn     = wrapper.querySelector('.fi-ta-filter-mobile-toggle-btn');
        var text    = wrapper.querySelector('.fi-ta-filter-toggle-text');
        var chevron = wrapper.querySelector('.fi-ta-filter-toggle-chevron');

        container.insertBefore(wrapper, filtersEl);

        function applyState() {
            if (window.innerWidth < MOBILE_BP) {
                wrapper.style.display    = 'flex';
                filtersEl.style.display  = isOpen ? '' : 'none';
                text.textContent         = isOpen ? 'إخفاء' : 'إظهار';
                chevron.style.transform  = isOpen ? 'rotate(180deg)' : '';
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            } else {
                wrapper.style.display   = 'none';
                filtersEl.style.display = '';
                chevron.style.transform = '';
                btn.setAttribute('aria-expanded', 'true');
            }
        }

        btn.addEventListener('click', function () {
            isOpen = !isOpen;
            applyState();
        });

        window.addEventListener('resize', applyState);
        applyState();
    }

    function initAll() {
        document.querySelectorAll('.fi-ta-filters-above-content-ctn').forEach(initContainer);
    }

    // Initial run
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        setTimeout(initAll, 0);
    }

    // After Livewire full-page navigations
    document.addEventListener('livewire:navigated', initAll);

    // After Livewire component DOM morphing updates (filter change, pagination, etc.)
    document.addEventListener('livewire:update', function () {
        setTimeout(initAll, 50);
    });
}());
</script>
