<?php

declare(strict_types=1);

namespace App\Livewire\Audit;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogIndex extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    public const JSON_PREVIEW_LIMIT = 240;

    #[Url(except: '')]
    public string $action = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    #[Url(except: '')]
    public string $actorId = '';

    public ?int $expandedDetailsId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', ActivityLog::class);
    }

    public function updatedAction(): void
    {
        $this->validateOnly('action');
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->validateDateRange();
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->validateDateRange();
        $this->resetPage();
    }

    public function updatedActorId(): void
    {
        $this->validateOnly('actorId');
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->action = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->actorId = '';
        $this->expandedDetailsId = null;
        $this->resetPage();
        $this->resetErrorBag();
    }

    public function toggleDetails(int $logId): void
    {
        $this->authorize('viewAny', ActivityLog::class);
        $this->expandedDetailsId = $this->expandedDetailsId === $logId ? null : $logId;
    }

    public function render(): View
    {
        $this->validateDateRange();

        $hasFilters = $this->action !== ''
            || $this->dateFrom !== ''
            || $this->dateTo !== ''
            || $this->actorId !== '';

        $logs = $this->baseQuery()->paginate(self::PER_PAGE);

        return view('livewire.audit.activity-log-index', [
            'logs' => $logs,
            'actions' => ActivityAction::cases(),
            'actors' => User::query()->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'hasFilters' => $hasFilters,
        ])->extends('layouts.app', [
            'heading' => 'Activity log',
            'title' => 'Activity log — '.config('app.name'),
        ])->section('content');
    }

    /**
     * @return Builder<ActivityLog>
     */
    private function baseQuery(): Builder
    {
        $query = ActivityLog::query()
            ->with('actor')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($this->action !== '') {
            $query->where('action', $this->action);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('occurred_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('occurred_at', '<=', $this->dateTo);
        }

        if ($this->actorId !== '') {
            $query->where('user_id', $this->actorId);
        }

        return $query;
    }

    private function validateDateRange(): void
    {
        $this->validateOnly('dateFrom');
        $this->validateOnly('dateTo');

        if ($this->dateFrom !== '' && $this->dateTo !== '' && $this->dateFrom > $this->dateTo) {
            $this->addError('dateTo', 'The end date must be on or after the start date.');
        }
    }

    /**
     * @param  array<string, mixed>|null  $values
     */
    public function formatPayload(?array $values, int $logId): string
    {
        if ($values === null || $values === []) {
            return '—';
        }

        $encoded = json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $encoded = is_string($encoded) ? $encoded : '—';

        if ($this->expandedDetailsId === $logId) {
            return $encoded;
        }

        if (strlen($encoded) <= self::JSON_PREVIEW_LIMIT) {
            return $encoded;
        }

        return substr($encoded, 0, self::JSON_PREVIEW_LIMIT).'…';
    }

    /**
     * @param  array<string, mixed>|null  $values
     */
    public function payloadIsTruncated(?array $values, int $logId): bool
    {
        if ($values === null || $values === [] || $this->expandedDetailsId === $logId) {
            return false;
        }

        $encoded = json_encode($values, JSON_UNESCAPED_UNICODE);

        return is_string($encoded) && strlen($encoded) > self::JSON_PREVIEW_LIMIT;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'action' => ['nullable', 'string', Rule::in(array_map(
                fn (ActivityAction $activityAction): string => $activityAction->value,
                ActivityAction::cases(),
            ))],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date'],
            'actorId' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
