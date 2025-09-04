document.addEventListener('DOMContentLoaded', function() {
    // Function to add profile breadcrumb
    function addProfileBreadcrumb() {
        // Look for breadcrumb navigation
        const breadcrumbNav = document.querySelector('nav[aria-label="Breadcrumb"], nav ol, .fi-breadcrumbs');
        
        if (breadcrumbNav) {
            const ol = breadcrumbNav.querySelector('ol') || breadcrumbNav;
            const firstItem = ol?.querySelector('li:first-child');
            
            if (firstItem && !firstItem.textContent.includes('ملفي الشخصي')) {
                // Get current subdomain from URL
                const subdomain = window.location.pathname.split('/')[1] || 'itqan-academy';
                const profileUrl = `/${subdomain}/profile`;
                
                // Create profile breadcrumb item
                const profileLi = document.createElement('li');
                profileLi.className = firstItem.className;
                
                const profileLink = document.createElement('a');
                profileLink.href = profileUrl;
                profileLink.textContent = 'ملفي الشخصي';
                profileLink.className = firstItem.querySelector('a')?.className || 'text-primary hover:underline';
                
                profileLi.appendChild(profileLink);
                
                // Create separator
                const separatorLi = document.createElement('li');
                separatorLi.className = 'text-gray-400';
                separatorLi.textContent = '/';
                
                // Insert at the beginning
                ol.insertBefore(separatorLi, firstItem);
                ol.insertBefore(profileLi, separatorLi);
            }
        }
    }
    
    // Run immediately
    addProfileBreadcrumb();
    
    // Also run after any dynamic content loads (for SPA-like behavior)
    setTimeout(addProfileBreadcrumb, 500);
    setTimeout(addProfileBreadcrumb, 1000);
    
    // Use MutationObserver to watch for content changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                setTimeout(addProfileBreadcrumb, 100);
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
