/**
 * Ensure Echo is registered with Livewire for real-time events
 */

document.addEventListener('DOMContentLoaded', () => {
    // Wait for both Echo and Livewire to be available
    const waitForDependencies = setInterval(() => {
        if (typeof window.Echo !== 'undefined' && typeof window.Livewire !== 'undefined') {
            clearInterval(waitForDependencies);

            console.log('🔗 Registering Echo with Livewire...');

            // Livewire v3 should auto-detect Echo, but let's make sure
            if (!window.Livewire.echo) {
                window.Livewire.echo = window.Echo;
                console.log('✅ Echo registered with Livewire manually');
            } else {
                console.log('✅ Echo already registered with Livewire');
            }

            // Debug: Log all Livewire components
            setTimeout(() => {
                if (window.Livewire.all) {
                    const components = window.Livewire.all();
                    console.log(`📦 Found ${components.length} Livewire components`);
                    components.forEach(component => {
                        const name = component.fingerprint?.name || component.name || 'unknown';
                        if (name.toLowerCase().includes('chat') || name.toLowerCase().includes('wirechat')) {
                            console.log(`💬 Chat component: ${name} (ID: ${component.id})`);
                        }
                    });
                }
            }, 1000);
        }
    }, 100);
});