<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <x-page-header title="Categories">
            <x-slot:description>Group products for POS browsing. Categories are deactivated, never deleted.</x-slot:description>
        </x-page-header>
        <a
            href="{{ route('categories.create') }}"
            class="inline-flex items-center justify-center rounded-md bg-brand px-3 py-2 text-sm font-medium text-white hover:bg-brand-hover"
        >
            New category
        </a>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="max-w-sm flex-1">
            <x-input
                label="Search"
                name="search"
                placeholder="Category name"
                wire:model.live.debounce.400ms="search"
            />
        </div>
        <div class="flex flex-col gap-1">
            <label for="status" class="text-sm font-medium text-ink">Status</label>
            <select
                id="status"
                wire:model.live="status"
                class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
            >
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    @if ($categories->isEmpty() && ! $hasSearch && ! $hasStatusFilter)
        <x-empty-state title="No categories yet">
            Create a category before you add products.
        </x-empty-state>
    @elseif ($categories->isEmpty())
        <x-empty-state title="No matching categories">
            No categories match the current search or filter.
        </x-empty-state>
    @else
        <x-table>
            <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td class="px-3 py-2 font-medium text-ink">{{ $category->name }}</td>
                        <td class="px-3 py-2">
                            <x-badge :tone="$category->active ? 'success' : 'danger'">
                                {{ $category->active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex justify-end gap-2">
                                @can('update', $category)
                                    <a href="{{ route('categories.edit', $category) }}" class="text-sm font-medium text-brand hover:text-brand-hover">
                                        Edit
                                    </a>
                                @endcan
                                @can('changeStatus', $category)
                                    @if ($category->active)
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-danger hover:underline"
                                            wire:click="confirmDeactivate({{ $category->id }})"
                                        >
                                            Deactivate
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-brand hover:text-brand-hover"
                                            wire:click="activate({{ $category->id }})"
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
            {{ $categories->links() }}
        </div>
    @endif

    <x-modal :open="$pendingDeactivateId !== null" title="Deactivate category">
        <p class="text-sm text-muted">
            Deactivate
            <span class="font-medium text-ink">{{ $pendingDeactivateName }}</span>?
            Products stay on file. New products will not be able to use this category.
        </p>
        <div class="mt-4 flex justify-end gap-2">
            <x-button variant="secondary" wire:click="cancelDeactivate">Cancel</x-button>
            <x-button variant="danger" wire:click="deactivate" wire:loading.attr="disabled">
                Deactivate
            </x-button>
        </div>
    </x-modal>
</div>
