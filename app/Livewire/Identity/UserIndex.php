<?php

declare(strict_types=1);

namespace App\Livewire\Identity;

use App\Actions\Identity\ChangeUserStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UserIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public ?int $pendingDeactivateId = null;

    public ?string $pendingDeactivateName = null;

    public ?string $pendingDeactivateEmail = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDeactivate(int $userId): void
    {
        $target = User::query()->findOrFail($userId);
        $this->authorize('changeStatus', $target);
        $this->pendingDeactivateId = $target->id;
        $this->pendingDeactivateName = $target->name;
        $this->pendingDeactivateEmail = $target->email;
    }

    public function cancelDeactivate(): void
    {
        $this->pendingDeactivateId = null;
        $this->pendingDeactivateName = null;
        $this->pendingDeactivateEmail = null;
    }

    public function deactivate(ChangeUserStatus $action): void
    {
        if ($this->pendingDeactivateId === null) {
            return;
        }

        $target = User::query()->findOrFail($this->pendingDeactivateId);
        $this->authorize('changeStatus', $target);

        $action->execute(auth()->user(), $target, false);

        $this->cancelDeactivate();
        session()->flash('success', 'User deactivated.');
    }

    public function activate(int $userId, ChangeUserStatus $action): void
    {
        $target = User::query()->findOrFail($userId);
        $this->authorize('changeStatus', $target);

        $action->execute(auth()->user(), $target, true);

        session()->flash('success', 'User activated.');
    }

    public function render(): View
    {
        $query = User::query()->orderBy('name')->orderBy('id');

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        return view('livewire.identity.user-index', [
            'users' => $query->paginate(15),
            'hasSearch' => $term !== '',
        ])->extends('layouts.app', [
            'heading' => 'Users',
            'title' => 'Users — '.config('app.name'),
        ])->section('content');
    }
}
