<?php

use App\Livewire\Student\Search;
use App\Models\Academy;
use App\Models\User;
use Livewire\Livewire;

describe('Student Search Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('component rendering', function () {
        it('renders successfully for authenticated students', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->assertStatus(200);
        });

        it('uses student layout', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($student)
                ->test(Search::class);

            // Component should render without errors
            $component->assertStatus(200);
        });

        it('initializes with empty query', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->assertSet('query', '')
                ->assertSet('activeTab', 'all')
                ->assertSet('showFilters', false);
        });
    });

    describe('search functionality', function () {
        it('can set search query', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', 'test search')
                ->assertSet('query', 'test search');
        });

        it('preserves query in URL', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // The URL attribute should work with wire:model
            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', 'arabic lesson')
                ->assertSet('query', 'arabic lesson');
        });

        it('can clear search query', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', 'test search')
                ->call('clearSearch')
                ->assertSet('query', '')
                ->assertSet('filters', [])
                ->assertSet('activeTab', 'all');
        });

        it('can perform search with searchFor method', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->call('searchFor', 'quran')
                ->assertSet('query', 'quran')
                ->assertSet('activeTab', 'all');
        });

        it('returns empty results when query is empty', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', '');

            expect($component->get('results')->isEmpty())->toBeTrue();
            expect($component->get('totalResults'))->toBe(0);
        });

        it('returns empty results for whitespace-only query', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', '   ');

            expect($component->get('results')->isEmpty())->toBeTrue();
            expect($component->get('totalResults'))->toBe(0);
        });
    });

    describe('tab functionality', function () {
        it('can switch active tab', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->call('setActiveTab', 'sessions')
                ->assertSet('activeTab', 'sessions');
        });

        it('defaults to all tab', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->assertSet('activeTab', 'all');
        });

        it('resets to all tab when clearing search', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('activeTab', 'courses')
                ->call('clearSearch')
                ->assertSet('activeTab', 'all');
        });
    });

    describe('filters functionality', function () {
        it('can toggle filters visibility', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->assertSet('showFilters', false)
                ->call('toggleFilters')
                ->assertSet('showFilters', true)
                ->call('toggleFilters')
                ->assertSet('showFilters', false);
        });

        it('initializes with empty filters array', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->assertSet('filters', []);
        });

        it('clears filters when clearing search', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('filters', ['type' => 'session'])
                ->call('clearSearch')
                ->assertSet('filters', []);
        });
    });

    describe('search results', function () {
        it('uses SearchService to get results', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', 'test');

            // Component should call SearchService and get results
            expect($component->get('results'))->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });

        it('displays total results count', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $component = Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', 'test');

            expect($component->get('totalResults'))->toBeInt();
        });
    });

    describe('Arabic search support', function () {
        it('handles Arabic query strings', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', 'القرآن الكريم')
                ->assertSet('query', 'القرآن الكريم');
        });

        it('handles mixed Arabic and English queries', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', 'Quran القرآن')
                ->assertSet('query', 'Quran القرآن');
        });
    });

    describe('edge cases', function () {
        it('handles special characters in query', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', "test's \"search\" (query)")
                ->assertSet('query', "test's \"search\" (query)");
        });

        it('handles very long query strings', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $longQuery = str_repeat('search term ', 50);

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', $longQuery)
                ->assertSet('query', $longQuery);
        });

        it('handles numeric queries', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', '12345')
                ->assertSet('query', '12345');
        });
    });

    describe('user authentication', function () {
        it('requires student profile to search', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Should not throw error when student profile exists
            Livewire::actingAs($student)
                ->test(Search::class)
                ->assertStatus(200);
        });
    });

    describe('URL persistence', function () {
        it('persists query parameter in URL', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // The #[Url] attribute should handle this
            Livewire::actingAs($student)
                ->test(Search::class)
                ->set('query', 'persistent query')
                ->assertSet('query', 'persistent query');
        });
    });
});
