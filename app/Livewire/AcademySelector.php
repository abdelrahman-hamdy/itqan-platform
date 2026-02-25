<?php

namespace App\Livewire;

use App\Models\Academy;
use App\Services\AcademyContextService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AcademySelector extends Component
{
    #[Locked]
    public $selectedAcademyId;

    public $academies = [];

    public $currentAcademy;

    #[Locked]
    public $isGlobalView = false;

    public function mount()
    {
        // Only show for super admin
        if (! AcademyContextService::isSuperAdmin()) {
            return;
        }

        $this->academies = AcademyContextService::getAvailableAcademies();
        $this->selectedAcademyId = AcademyContextService::getCurrentAcademyId();
        $this->currentAcademy = AcademyContextService::getCurrentAcademy();
        $this->isGlobalView = AcademyContextService::isGlobalViewMode();
    }

    public function selectAcademy($academyId)
    {
        if (! AcademyContextService::isSuperAdmin()) {
            return;
        }

        // Check if this is the global view selection
        if ($academyId === 'global') {
            $this->enableGlobalView();

            return;
        }

        $this->selectedAcademyId = $academyId;

        // Disable global view when selecting a specific academy
        if ($this->isGlobalView) {
            AcademyContextService::disableGlobalView();
            $this->isGlobalView = false;
        }

        // Set academy context using the service
        if (AcademyContextService::setAcademyContext($academyId)) {
            // Force session save
            session()->save();

            // Update current academy
            $this->currentAcademy = AcademyContextService::getCurrentAcademy();

            // Dispatch browser event for academy change
            $this->dispatch('academy-selected', academyId: $academyId);

            // Redirect to dashboard to avoid 404 on resources from different academy
            $this->js("window.location.href = '/admin'");
        }
    }

    public function enableGlobalView()
    {
        if (! AcademyContextService::isSuperAdmin()) {
            return;
        }

        // Enable global view and clear academy context
        AcademyContextService::enableGlobalView();

        // Force session save
        session()->save();

        // Update component state
        $this->isGlobalView = true;
        $this->selectedAcademyId = null;
        $this->currentAcademy = null;

        // Dispatch browser event and redirect to dashboard
        $this->dispatch('global-view-enabled');
        $this->js("window.location.href = '/admin'");
    }

    public function toggleGlobalView()
    {
        if (! AcademyContextService::isSuperAdmin()) {
            return;
        }

        if ($this->isGlobalView) {
            // Switch to academy-specific view
            AcademyContextService::disableGlobalView();
            $this->isGlobalView = false;
        } else {
            // Switch to global view
            AcademyContextService::enableGlobalView();
            $this->isGlobalView = true;
        }

        // Trigger page refresh to reload all resources
        $this->dispatch('global-view-toggled', isGlobalView: $this->isGlobalView);
    }

    // clearAcademy method removed to prevent dashboard pages from disappearing

    public function render()
    {
        // Only render for super admin
        if (! AcademyContextService::isSuperAdmin()) {
            return view('livewire.empty-component');
        }

        return view('livewire.academy-selector');
    }
}
