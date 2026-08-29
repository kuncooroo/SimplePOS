<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <x-page-header title="Users">
            <x-slot:description>Create and manage operational accounts. Users are deactivated, never deleted.</x-slot:description>
        </x-page-header>
        <a
            href="{{ route('users.create') }}"
            class="inline-flex items-center justify-center rounded-md bg-brand px-3 py-2 text-sm font-medium text-white hover:bg-brand-hover"
        >
            New user
        </a>
    </div>

    <div class="mb-4 max-w-sm">
        <x-input
            label="Search"
            name="search"
            placeholder="Name or email"
            wire:model.live.debounce.400ms="search"
        />
    </div>

    @if ($users->isEmpty() && ! $hasSearch)
        <x-empty-state title="No users yet">
            Create the first operational user to start assigning roles.
        </x-empty-state>
    @elseif ($users->isEmpty())
        <x-empty-state title="No matching users">
            No users match “{{ $search }}”. Try a different name or email.
        </x-empty-state>
    @else
        <x-table>
            <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Email</th>
                    <th class="px-3 py-2">Role</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-3 py-2 font-medium text-ink">{{ $user->name }}</td>
                        <td class="px-3 py-2 text-muted">{{ $user->email }}</td>
                        <td class="px-3 py-2">{{ $user->resolvedRole()?->label() ?? 'Unknown' }}</td>
                        <td class="px-3 py-2">
                            <x-badge :tone="$user->active ? 'success' : 'danger'">
                                {{ $user->active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex justify-end gap-2">
                                @can('update', $user)
                                    <a href="{{ route('users.edit', $user) }}" class="text-sm font-medium text-brand hover:text-brand-hover">
                                        Edit
                                    </a>
                                @endcan
                                @can('changeStatus', $user)
                                    @if ($user->active)
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-danger hover:underline"
                                            wire:click="confirmDeactivate({{ $user->id }})"
                                        >
                                            Deactivate
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-brand hover:text-brand-hover"
                                            wire:click="activate({{ $user->id }})"
                                        >
                                            Activate
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    @endif

    <x-modal :open="$pendingDeactivateId !== null" title="Deactivate user">
        <p class="text-sm text-muted">
            Deactivate
            <span class="font-medium text-ink">{{ $pendingDeactivateName }}</span>
            ({{ $pendingDeactivateEmail }})? They will not be able to sign in. Historical records stay intact.
        </p>
        <div class="mt-4 flex justify-end gap-2">
            <x-button variant="secondary" wire:click="cancelDeactivate">Cancel</x-button>
            <x-button variant="danger" wire:click="deactivate" wire:loading.attr="disabled">
                Deactivate
            </x-button>
        </div>
    </x-modal>
</div>
