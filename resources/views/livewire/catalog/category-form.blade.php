<div>
    <div class="mb-4">
        <x-page-header :title="$this->isEditing() ? 'Edit category' : 'New category'">
            <x-slot:description>
                @if ($this->isEditing())
                    Update the category name or availability. Deactivating does not remove products.
                @else
                    Categories group products on the POS. Keep names short and clear.
                @endif
            </x-slot:description>
        </x-page-header>
    </div>

    <form wire:submit="save" class="max-w-xl space-y-4 rounded-md border border-line bg-surface p-4">
        <x-input
            label="Name"
            name="name"
            wire:model="name"
            required
            maxlength="150"
        />
        @error('name')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <label class="flex items-center gap-2 text-sm text-ink">
            <input
                type="checkbox"
                wire:model="active"
                class="rounded border-line"
            >
            Active
        </label>
        @error('active')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="flex items-center gap-2 pt-2">
            <x-button type="submit" wire:loading.attr="disabled">
                {{ $this->isEditing() ? 'Save changes' : 'Create category' }}
            </x-button>
            <a href="{{ route('categories.index') }}" class="text-sm font-medium text-muted hover:text-ink">Cancel</a>
        </div>
    </form>
</div>
