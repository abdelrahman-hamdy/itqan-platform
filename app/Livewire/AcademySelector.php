<?php

namespace App\Livewire;

use App\Models\Academy;
use App\Services\AcademyContextService;
use Livewire\Component;

class AcademySelector extends Component
{
    public $selectedAcademyId;
    public $academies = [];
    public $currentAcademy;
    public $isGlobalView = false;

    public function mount()
    {
        // Only show for super admin
        if (!AcademyContextService::isSuperAdmin()) {
            return;
        }

        $this->academies = AcademyContextService::getAvailableAcademies();
        $this->selectedAcademyId = AcademyContextService::getCurrentAcademyId();
        $this->currentAcademy = AcademyContextService::getCurrentAcademy();
        $this->isGlobalView = AcademyContextService::isGlobalViewMode();
    }

    public function selectAcademy($academyId)
    {
        if (!AcademyContextService::isSuperAdmin()) {
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
            // Update current academy
            $this->currentAcademy = AcademyContextService::getCurrentAcademy();
            
            // Use JavaScript redirect for full page reload to refresh all resources
            $this->dispatch('academy-selected', academyId: $academyId);
        }
    }

    public function enableGlobalView()
    {
        if (!AcademyContextService::isSuperAdmin()) {
            return;
        }

        // Enable global view and clear academy context
        AcademyContextService::enableGlobalView();
        
        // Update component state
        $this->isGlobalView = true;
        $this->selectedAcademyId = null;
        $this->currentAcademy = null;

        // Trigger page refresh to reload all resources in global mode
        $this->dispatch('global-view-enabled');
    }

    public function toggleGlobalView()
    {
        if (!AcademyContextService::isSuperAdmin()) {
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
        if (!AcademyContextService::isSuperAdmin()) {
            return view('livewire.empty-component');
        }

        return view('livewire.academy-selector');
    }
} 