{{--
    Shared Calendar Item Selection JavaScript

    Handles visual selection of schedulable items in:
    - Teacher Calendar (UnifiedTeacherCalendar)
    - Supervisor Calendar (SupervisorCalendar)
--}}
<script>
    function makeItemSelected(itemId, itemType) {
        // Remove all selections first
        document.querySelectorAll('.item-card').forEach(card => {
            card.classList.remove('item-selected');
            const cardElement = card.querySelector('.fi-card');
            if (cardElement) {
                cardElement.style.border = '';
                cardElement.style.backgroundColor = '';
                cardElement.style.boxShadow = '';
            }
        });

        // Find and select the target item
        const targetCard = document.querySelector(`[data-item-id="${itemId}"][data-item-type="${itemType}"]`);
        if (targetCard) {
            targetCard.classList.add('item-selected');

            // Force styles as backup
            const cardElement = targetCard.querySelector('.fi-card');
            if (cardElement) {
                cardElement.style.setProperty('border', '2px solid #60a5fa', 'important');
                cardElement.style.setProperty('background-color', '#eff6ff', 'important');
                cardElement.style.setProperty('box-shadow', '0 0 0 3px rgba(96, 165, 250, 0.25)', 'important');
            }

            window.__calendarSelection = { id: String(itemId), type: String(itemType) };
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced click handler for items
        document.addEventListener('click', function(e) {
            const itemCard = e.target.closest('.item-card');

            if (itemCard) {
                const itemId = itemCard.getAttribute('data-item-id');
                const itemType = itemCard.getAttribute('data-item-type');

                if (itemId && itemType) {
                    makeItemSelected(itemId, itemType);
                }
            }
        });

        // Listen for Livewire updates and reapply selection
        const reapply = () => {
            setTimeout(() => {
                const selectedCards = document.querySelectorAll('.item-selected');
                if (selectedCards.length > 0) {
                    selectedCards.forEach(card => {
                        const itemId = card.getAttribute('data-item-id');
                        const itemType = card.getAttribute('data-item-type');
                        if (itemId && itemType) makeItemSelected(itemId, itemType);
                    });
                } else if (window.__calendarSelection && window.__calendarSelection.id) {
                    makeItemSelected(window.__calendarSelection.id, window.__calendarSelection.type);
                }
            }, 50);
        };

        ['livewire:updated','livewire:load','livewire:message.processed','livewire:navigated'].forEach(evt => {
            document.addEventListener(evt, reapply);
        });
    });
</script>
