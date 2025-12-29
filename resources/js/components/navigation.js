/**
 * Navigation component JavaScript
 */

/**
 * Handle navigation search form submission
 */
export function handleNavSearch(event) {
    const form = event.target;
    const input = form.querySelector('#nav-search-input');
    const query = input.value.trim();

    if (!query || query.length === 0) {
        event.preventDefault();
        window.toast?.warning('الرجاء إدخال كلمة بحث');
        return false;
    }

    return true;
}

/**
 * Child selector Alpine.js component for parent navigation
 */
export function childSelector(selectChildUrl, csrfToken) {
    return {
        open: false,
        init() {
            this.$watch('open', value => {
                if (value) {
                    document.addEventListener('keydown', this.handleEscape.bind(this));
                } else {
                    document.removeEventListener('keydown', this.handleEscape.bind(this));
                }
            });
        },
        handleEscape(e) {
            if (e.key === 'Escape') this.open = false;
        },
        selectChild(childId) {
            fetch(selectChildUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ child_id: childId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error selecting child:', error);
            });

            this.open = false;
        }
    };
}

/**
 * Initialize navigation functionality
 */
export function initNavigation() {
    // Navigation search - handle Enter key
    const navSearchInput = document.getElementById('nav-search-input');
    if (navSearchInput) {
        navSearchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const form = document.getElementById('nav-search-form');
                if (form) {
                    form.submit();
                }
            }
        });
    }
}

// Make functions available globally for inline usage
window.handleNavSearch = handleNavSearch;
window.childSelector = childSelector;

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', initNavigation);
