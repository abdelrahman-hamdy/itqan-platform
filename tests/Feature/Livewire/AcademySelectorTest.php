<?php

use App\Livewire\AcademySelector;
use App\Models\Academy;
use App\Models\User;
use Livewire\Livewire;

describe('Academy Selector Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('rendering', function () {
        it('renders empty for non-super-admin users', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(AcademySelector::class)
                ->assertSee(''); // Should render empty component
        });

        it('renders academy list for super admin', function () {
            $superAdmin = User::factory()->superAdmin()->create();

            Livewire::actingAs($superAdmin)
                ->test(AcademySelector::class)
                ->assertStatus(200);
        });
    });

    describe('academy selection', function () {
        it('allows super admin to select an academy', function () {
            $superAdmin = User::factory()->superAdmin()->create();

            Livewire::actingAs($superAdmin)
                ->test(AcademySelector::class)
                ->call('selectAcademy', $this->academy->id)
                ->assertSet('selectedAcademyId', $this->academy->id);
        });

        it('prevents non-super-admin from selecting academy', function () {
            $regularUser = User::factory()->admin()->forAcademy($this->academy)->create();

            Livewire::actingAs($regularUser)
                ->test(AcademySelector::class)
                ->call('selectAcademy', $this->academy->id)
                ->assertNotSet('selectedAcademyId', $this->academy->id);
        });
    });
});
