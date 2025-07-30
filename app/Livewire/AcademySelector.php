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

    public function mount()
    {
        // Only show for super admin
        if (!AcademyContextService::isSuperAdmin()) {
            return;
        }

        $this->academies = AcademyContextService::getAvailableAcademies();
        $this->selectedAcademyId = AcademyContextService::getCurrentAcademyId();
        $this->currentAcademy = AcademyContextService::getCurrentAcademy();
    }

    public function selectAcademy($academyId)
    {
        if (!AcademyContextService::isSuperAdmin()) {
            return;
        }

        $this->selectedAcademyId = $academyId;
        
        // Set academy context using the service
        if (AcademyContextService::setAcademyContext($academyId)) {
            // Update current academy
            $this->currentAcademy = AcademyContextService::getCurrentAcademy();
            
            // Use JavaScript redirect for full page reload to refresh all resources
            $this->dispatch('academy-selected', academyId: $academyId);
        }
    }

    public function clearAcademy()
    {
        if (!AcademyContextService::isSuperAdmin()) {
            return;
        }

        $this->selectedAcademyId = null;
        $this->currentAcademy = null;
        
        // Clear academy context using the service
        AcademyContextService::clearAcademyContext();
        
        $this->dispatch('academy-cleared');
    }

    public function render()
    {
        // Only render for super admin
        if (!AcademyContextService::isSuperAdmin()) {
            return view('livewire.empty-component');
        }

        return view('livewire.academy-selector');
    }
} 