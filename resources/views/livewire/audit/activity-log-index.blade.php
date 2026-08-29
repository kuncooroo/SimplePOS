<div>
    <x-page-header title="Activity log">
        <x-slot:description>
            Read-only audit history for sensitive changes. Sales are recorded on transactions and stock movements instead.
        </x-slot:description>
    </x-page-header>

    <div class="mb-4 grid gap-3 lg:grid-cols-4">
        <div>
            <label for="action" class="text-sm font-medium text-ink">Action</label>
            <select
                id="action"
                wire:model.live="action"
                class="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
            >
                <option value="">All actions</option>
                @foreach ($actions as $activityAction)
                    <option value="{{ $activityAction->value }}">
                        {{ match ($activityAction) {
                            \App\Enums\ActivityAction::UserCreated => 'User created',
                            \App\Enums\ActivityAction::UserRoleChanged => 'User role changed',
                            \App\Enums\ActivityAction::UserStatusChanged => 'User status changed',
                            \App\Enums\ActivityAction::StockManualAdjusted => 'Stock manually adjusted',
                            \App\Enums\ActivityAction::StoreSettingsUpdated => 'Store settings updated',
                        } }}
                    </option>
                @endforeach
            </select>
            @error('action')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="actorId" class="text-sm font-medium text-ink">Actor</label>
            <select
                id="actorId"
                wire:model.live="actorId"
                class="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
            >
                <option value="">All actors</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}">{{ $actor->name }}</option>
                @endforeach
            </select>
            @error('actorId')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input
                label="From"
                type="date"
                name="dateFrom"
                wire:model.live="dateFrom"
            />
            @error('dateFrom')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input
                label="To"
                type="date"
                name="dateTo"
                wire:model.live="dateTo"
            />
            @error('dateTo')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        @if ($hasFilters)
            <div class="flex items-end lg:col-span-4">
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-brand hover:text-brand-hover">
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    @if ($logs->isEmpty() && ! $hasFilters)
        <x-empty-state title="No activity yet">
            User, stock, and settings changes will appear here after they are recorded.
        </x-empty-state>
    @elseif ($logs->isEmpty())
        <x-empty-state title="No matching activity">
            No audit entries match the current filters.
        </x-empty-state>
    @else
        <x-table>
            <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-3 py-2">Time</th>
                    <th class="px-3 py-2">Action</th>
                    <th class="px-3 py-2">Actor</th>
                    <th class="px-3 py-2">Subject</th>
                    <th class="px-3 py-2">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($logs as $log)
                    <tr wire:key="activity-log-{{ $log->id }}">
                        <td class="px-3 py-2 text-muted whitespace-nowrap">{{ $log->occurred_at?->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2">
                            <x-badge tone="neutral">
                                {{ match ($log->action) {
                                    \App\Enums\ActivityAction::UserCreated => 'User created',
                                    \App\Enums\ActivityAction::UserRoleChanged => 'User role changed',
                                    \App\Enums\ActivityAction::UserStatusChanged => 'User status changed',
                                    \App\Enums\ActivityAction::StockManualAdjusted => 'Stock manually adjusted',
                                    \App\Enums\ActivityAction::StoreSettingsUpdated => 'Store settings updated',
                                } }}
                            </x-badge>
                        </td>
                        <td class="px-3 py-2">{{ $log->actor?->name ?? 'Unknown user' }}</td>
                        <td class="px-3 py-2 text-muted">
                            @if ($log->subject_type)
                                {{ match ($log->subject_type) {
                                    'User' => 'User',
                                    'StoreSetting' => 'Store settings',
                                    default => class_basename($log->subject_type),
                                } }} #{{ $log->subject_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <button
                                type="button"
                                wire:click="toggleDetails({{ $log->id }})"
                                class="text-sm font-medium text-brand hover:text-brand-hover"
                            >
                                {{ $expandedDetailsId === $log->id ? 'Hide details' : 'View details' }}
                            </button>
                        </td>
                    </tr>
                    @if ($expandedDetailsId === $log->id)
                        <tr wire:key="activity-log-details-{{ $log->id }}">
                            <td colspan="5" class="bg-canvas px-3 py-3">
                                <div class="grid gap-3 lg:grid-cols-3">
                                    <div>
                                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted">Previous values</p>
                                        <pre class="overflow-x-auto rounded-md border border-line bg-surface p-2 text-xs text-ink">{{ $this->formatPayload($log->old_values, $log->id) }}</pre>
                                    </div>
                                    <div>
                                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted">New values</p>
                                        <pre class="overflow-x-auto rounded-md border border-line bg-surface p-2 text-xs text-ink">{{ $this->formatPayload($log->new_values, $log->id) }}</pre>
                                    </div>
                                    <div>
                                        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted">Context</p>
                                        <pre class="overflow-x-auto rounded-md border border-line bg-surface p-2 text-xs text-ink">{{ $this->formatPayload($log->context, $log->id) }}</pre>
                                    </div>
                                </div>
                                @if (
                                    $this->payloadIsTruncated($log->old_values, $log->id)
                                    || $this->payloadIsTruncated($log->new_values, $log->id)
                                    || $this->payloadIsTruncated($log->context, $log->id)
                                )
                                    <p class="mt-2 text-xs text-muted">Showing a preview. Use Hide details and View details again to collapse.</p>
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </x-table>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
