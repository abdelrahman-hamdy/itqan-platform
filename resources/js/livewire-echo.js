/**
 * Ensure Echo is registered with Livewire for real-time events
 */

document.addEventListener('DOMContentLoaded', () => {
    // Wait for both Echo and Livewire to be available
    const waitForDependencies = setInterval(() => {
        if (typeof window.Echo !== 'undefined' && typeof window.Livewire !== 'undefined') {
            clearInterval(waitForDependencies);

            // Livewire v3 should auto-detect Echo, but let's make sure
            if (!window.Livewire.echo) {
                window.Livewire.echo = window.Echo;
            }
        }
    }, 100);
});
