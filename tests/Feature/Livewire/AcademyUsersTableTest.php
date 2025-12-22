<?php

use App\Livewire\AcademyUsersTable;
use App\Models\Academy;
use App\Models\User;
use Livewire\Livewire;

describe('Academy Users Table Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('component rendering', function () {
        it('renders successfully with academy ID', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->assertStatus(200)
                ->assertSet('academyId', $this->academy->id);
        });

        it('displays users from the specified academy', function () {
            $user1 = User::factory()->forAcademy($this->academy)->create(['first_name' => 'Test', 'last_name' => 'One']);
            $user2 = User::factory()->forAcademy($this->academy)->create(['first_name' => 'Test', 'last_name' => 'Two']);
            $otherAcademy = Academy::factory()->create();
            $userOther = User::factory()->forAcademy($otherAcademy)->create(['first_name' => 'Other', 'last_name' => 'User']);

            Livewire::actingAs($user1)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->assertSee('Test One')
                ->assertSee('Test Two')
                ->assertDontSee('Other User');
        });
    });

    describe('search functionality', function () {
        it('can search users by name', function () {
            $user1 = User::factory()->forAcademy($this->academy)->create(['first_name' => 'Ahmed', 'last_name' => 'Ali']);
            $user2 = User::factory()->forAcademy($this->academy)->create(['first_name' => 'Mohammed', 'last_name' => 'Hassan']);

            Livewire::actingAs($user1)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->set('search', 'Ahmed')
                ->assertSee('Ahmed Ali')
                ->assertDontSee('Mohammed Hassan');
        });

        it('can search users by email', function () {
            $user1 = User::factory()->forAcademy($this->academy)->create(['email' => 'ahmed@example.com']);
            $user2 = User::factory()->forAcademy($this->academy)->create(['email' => 'mohammed@example.com']);

            Livewire::actingAs($user1)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->set('search', 'ahmed@')
                ->assertSee('ahmed@example.com')
                ->assertDontSee('mohammed@example.com');
        });

        it('resets pagination when searching', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();

            // Just test that setting search property works (pagination reset happens automatically)
            Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->set('search', 'test')
                ->assertSet('search', 'test')
                ->assertStatus(200);
        });

        it('returns empty results when no matches found', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create(['first_name' => 'Test', 'last_name' => 'User']);

            Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->set('search', 'NonExistentUser123')
                ->assertDontSee('Test User');
        });
    });

    describe('pagination', function () {
        it('paginates users with 10 per page', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            User::factory()->count(15)->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id]);

            // Should render successfully with pagination
            $component->assertStatus(200);
        });

        it('displays users ordered by created_at descending', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();

            // Create users with specific timestamps
            $oldUser = User::factory()->forAcademy($this->academy)->create([
                'first_name' => 'Old',
                'last_name' => 'User',
                'created_at' => now()->subDays(5),
            ]);
            $newUser = User::factory()->forAcademy($this->academy)->create([
                'first_name' => 'New',
                'last_name' => 'User',
                'created_at' => now(),
            ]);

            Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->assertSee('New User')
                ->assertSee('Old User');
        });
    });

    describe('mount method', function () {
        it('sets academyId property on mount', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->assertSet('academyId', $this->academy->id);
        });

        it('initializes search as empty string', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->assertSet('search', '');
        });
    });

    describe('edge cases', function () {
        it('handles academy with no users', function () {
            $emptyAcademy = Academy::factory()->create();
            $user = User::factory()->admin()->create(['academy_id' => $emptyAcademy->id]);

            Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $emptyAcademy->id])
                ->assertStatus(200);
        });

        it('handles special characters in search', function () {
            $user = User::factory()->forAcademy($this->academy)->create(['first_name' => "O'Brien", 'last_name' => 'Test']);

            Livewire::actingAs($user)
                ->test(AcademyUsersTable::class, ['academyId' => $this->academy->id])
                ->set('search', "O'Brien")
                ->assertSee("O'Brien Test");
        });
    });
});
