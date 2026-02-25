<?php

namespace App\Livewire;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class AcademyUsersTable extends Component
{
    use WithPagination;

    #[Locked]
    public $academyId;

    public $search = '';

    public function mount($academyId)
    {
        $user = Auth::user();

        // Only super admins can view any academy; other roles can only view their own academy
        if (! $user->hasRole(UserType::SUPER_ADMIN->value) && (int) $user->academy_id !== (int) $academyId) {
            abort(403);
        }

        $this->academyId = $academyId;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::where('academy_id', $this->academyId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.academy-users-table', compact('users'));
    }
}
