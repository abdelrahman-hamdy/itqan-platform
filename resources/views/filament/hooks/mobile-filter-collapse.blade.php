<script>
(function () {
    var COLLAPSE_BP = 1024;
    var openStates = {};

    function getStateKey(container) {
        var c = container.closest('[wire\\:id]');
        return c ? c.getAttribute('wire:id') : 'default';
    }

    function buildToggle() {
        var w = document.createElement('div');
        w.className = 'fi-ta-filter-mobile-toggle-wrapper';
        w.innerHTML =
            '<span class="fi-ta-filter-mobile-toggle-label">\u0627\u0644\u0641\u0644\u0627\u062a\u0631</span>' +
            '<button type="button" class="fi-ta-filter-mobile-toggle-btn">' +
                '<span class="fi-ta-filter-toggle-text">\u0625\u0638\u0647\u0627\u0631</span>' +
                '<svg class="fi-ta-filter-toggle-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                    '<path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>' +
                '</svg>' +
            '</button>';
        return w;
    }

    function initContainer(container) {
        var existing = container.querySelector('.fi-ta-filter-mobile-toggle-wrapper');
        if (existing) return;

        var filtersEl = container.querySelector('.fi-ta-filters');
        if (!filtersEl) return;

        var key = getStateKey(container);
        var isOpen = key in openStates ? openStates[key] : false;

        var wrapper = buildToggle();
        var btn     = wrapper.querySelector('.fi-ta-filter-mobile-toggle-btn');
        var text    = wrapper.querySelector('.fi-ta-filter-toggle-text');
        var chevron = wrapper.querySelector('.fi-ta-filter-toggle-chevron');

        container.insertBefore(wrapper, filtersEl);

        function applyState() {
            if (window.innerWidth < COLLAPSE_BP) {
                wrapper.style.display   = 'flex';
                filtersEl.style.display = isOpen ? '' : 'none';
                text.textContent        = isOpen ? '\u0625\u062e\u0641\u0627\u0621' : '\u0625\u0638\u0647\u0627\u0631';
                chevron.style.transform = isOpen ? 'rotate(180deg)' : '';
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            } else {
                wrapper.style.display   = 'none';
                filtersEl.style.display = '';
            }
        }

        btn.addEventListener('click', function () {
            isOpen = !isOpen;
            openStates[key] = isOpen;
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

    // After Livewire SPA navigations
    document.addEventListener('livewire:navigated', initAll);

    // After every Livewire 3 commit (fires after DOM morphing is complete)
    document.addEventListener('livewire:init', function () {
        Livewire.hook('commit', function ({ succeed }) {
            succeed(function () {
                queueMicrotask(initAll);
            });
        });
    });
}());
</script>
