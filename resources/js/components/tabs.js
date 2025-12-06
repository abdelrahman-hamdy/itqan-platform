export default function tabsComponent(config) {
    console.log('[Tabs] Creating component with config:', config);
    return {
        id: config.id,
        activeTab: null,
        loadedTabs: [],
        persistState: config.persistState,
        urlSync: config.urlSync,
        lazy: config.lazy,
        animated: config.animated,
        rootElement: null, // Store reference to root element

        init() {
            console.log('[Tabs] Initializing component:', this.id);
            // Store root element reference
            this.rootElement = this.$el;
            console.log('[Tabs] Stored root element:', this.rootElement);

            // Determine initial active tab
            this.activeTab = this.getInitialTab();
            console.log('[Tabs] Initial active tab:', this.activeTab);

            // Load initial tab if lazy loading
            if (this.lazy && this.activeTab) {
                this.loadedTabs.push(this.activeTab);
            }

            // Set up keyboard navigation
            this.setupKeyboardNav();

            // Listen for hash changes if URL sync enabled
            if (this.urlSync) {
                window.addEventListener('hashchange', () => {
                    const hash = window.location.hash.replace('#', '');
                    if (hash && this.isValidTab(hash)) {
                        this.activeTab = hash;
                    }
                });
            }

            // Listen for external tab change events
            window.addEventListener(`tabs:switch:${this.id}`, (e) => {
                this.switchTab(e.detail.tabId);
            });
        },

        getInitialTab() {
            // Priority: URL hash > localStorage > default > first tab
            if (this.urlSync) {
                const hash = window.location.hash.replace('#', '');
                if (hash && this.isValidTab(hash)) {
                    return hash;
                }
            }

            if (this.persistState) {
                const stored = localStorage.getItem(`tabs:${this.id}`);
                if (stored && this.isValidTab(stored)) {
                    return stored;
                }
            }

            return config.defaultTab || this.getFirstTabId();
        },

        getFirstTabId() {
            const root = this.rootElement || this.$root || this.$el;
            const firstTab = root.querySelector('[role="tab"]');
            return firstTab ? firstTab.dataset.tab : null;
        },

        isValidTab(tabId) {
            // Use stored rootElement to get the component's root element
            const root = this.rootElement || this.$root || this.$el;
            console.log('[Tabs] root element:', root);
            console.log('[Tabs] root.tagName:', root?.tagName);

            const allTabs = root.querySelectorAll('[data-tab]');
            console.log('[Tabs] All elements with data-tab:', allTabs);
            console.log('[Tabs] Number of tabs found:', allTabs.length);

            const tab = root.querySelector(`[data-tab="${tabId}"]`);
            console.log(`[Tabs] Checking if tab "${tabId}" is valid:`, !!tab);
            if (!tab) {
                console.log('[Tabs] Available tabs:', Array.from(allTabs).map(t => t.dataset.tab));
            }
            return !!tab;
        },

        switchTab(tabId) {
            console.log('[Tabs] switchTab called with:', tabId);
            console.log('[Tabs] Current activeTab:', this.activeTab);
            console.log('[Tabs] isValidTab result:', this.isValidTab(tabId));

            if (this.activeTab === tabId) {
                console.log('[Tabs] Switch cancelled - already on this tab');
                return;
            }

            if (!this.isValidTab(tabId)) {
                console.log('[Tabs] Switch cancelled - invalid tab ID');
                return;
            }

            console.log('[Tabs] Switching to tab:', tabId);

            // Preserve scroll position before switching
            const scrollY = window.scrollY;
            const tabsElement = this.rootElement || this.$el;
            const tabsTop = tabsElement.getBoundingClientRect().top + window.scrollY;

            this.activeTab = tabId;

            // Restore scroll position after DOM updates and transitions
            this.$nextTick(() => {
                // Use requestAnimationFrame to ensure layout is complete
                requestAnimationFrame(() => {
                    // If user was scrolled past the tabs, keep them in the same relative position
                    if (scrollY >= tabsTop) {
                        window.scrollTo({
                            top: scrollY,
                            behavior: 'instant'
                        });
                    }
                    // Otherwise, let the browser handle scroll naturally
                });
            });

            // Load tab content if lazy loading
            if (this.lazy && !this.loadedTabs.includes(tabId)) {
                this.loadedTabs.push(tabId);
            }

            // Persist state
            if (this.persistState) {
                localStorage.setItem(`tabs:${this.id}`, tabId);
            }

            // Update URL hash
            if (this.urlSync) {
                history.replaceState(null, '', `#${tabId}`);
            }

            // Dispatch event
            this.$dispatch('tab-changed', { tabId, tabsId: this.id });
        },

        setupKeyboardNav() {
            const root = this.rootElement || this.$root || this.$el;
            const tabs = root.querySelectorAll('[role="tab"]');

            tabs.forEach((tab, index) => {
                tab.addEventListener('keydown', (e) => {
                    let targetIndex;

                    switch(e.key) {
                        case 'ArrowRight':
                        case 'ArrowLeft':
                            e.preventDefault();
                            // RTL support: reverse arrow behavior
                            const direction = e.key === 'ArrowRight' ? -1 : 1;
                            targetIndex = (index + direction + tabs.length) % tabs.length;
                            tabs[targetIndex].focus();
                            this.switchTab(tabs[targetIndex].dataset.tab);
                            break;
                        case 'Home':
                            e.preventDefault();
                            tabs[0].focus();
                            this.switchTab(tabs[0].dataset.tab);
                            break;
                        case 'End':
                            e.preventDefault();
                            tabs[tabs.length - 1].focus();
                            this.switchTab(tabs[tabs.length - 1].dataset.tab);
                            break;
                    }
                });
            });
        }
    }
}
