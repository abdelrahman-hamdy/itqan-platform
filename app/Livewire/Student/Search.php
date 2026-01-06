<?php

namespace App\Livewire\Student;

use App\Services\SearchService;
use Livewire\Attributes\Url;
use Livewire\Component;

class Search extends Component
{
    #[Url(as: 'q', keep: true, history: true)]
    public string $query = '';

    public array $filters = [];

    public bool $showFilters = false;

    public string $activeTab = 'all';

    protected SearchService $searchService;

    public function boot(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function updatedQuery()
    {
        // Debounce is handled in the view with wire:model.live.debounce
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function toggleFilters()
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function clearSearch()
    {
        $this->query = '';
        $this->filters = [];
        $this->activeTab = 'all';
    }

    public function searchFor($term)
    {
        $this->query = $term;
        $this->activeTab = 'all';
    }

    public function render()
    {
        $student = auth()->user()->studentProfile;
        $results = collect();
        $totalResults = 0;

        if (! empty(trim($this->query))) {
            $results = $this->searchService->searchAll($this->query, $student, $this->filters);
            $totalResults = $this->searchService->getTotalResultsCount($results);
        }

        return view('livewire.student.search', [
            'results' => $results,
            'totalResults' => $totalResults,
        ])->layout('components.layouts.student', [
            'title' => __('student.search.page_title').' - '.(auth()->user()->academy->name ?? __('student.common.academy_default')),
            'description' => __('student.search.page_description'),
        ]);
    }
}
