<?php

use App\Livewire\Student\Search;
use App\Models\Academy;
use App\Models\User;
use Livewire\Livewire;

describe('Student Search', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('render', function () {
        it('renders for authenticated user', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();

            $this->actingAs($admin);

            Livewire::test(Search::class)
                ->assertStatus(200);
        });
    });

    describe('search', function () {
        it('finds students by name', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create([
                'name' => 'Ahmed Mohamed',
            ]);

            $this->actingAs($admin);

            Livewire::test(Search::class)
                ->set('query', 'Ahmed')
                ->assertSee('Ahmed Mohamed');
        });

        it('shows no results for non-matching search', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();

            $this->actingAs($admin);

            Livewire::test(Search::class)
                ->set('query', 'NonExistentStudentName12345')
                ->assertDontSee('NonExistentStudentName12345');
        });
    });
});
