<div>
    <div class="mb-4">
        <x-page-header :title="$this->isEditing() ? 'Edit product' : 'New product'">
            <x-slot:description>
                @if ($this->isEditing())
                    Update catalog details. Opening stock here is master data; later stock changes use inventory adjustments.
                @else
                    SKU must be unique. Barcode is optional. Choose an active category.
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
            maxlength="200"
        />
        @error('name')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input
                    label="SKU"
                    name="sku"
                    wire:model="sku"
                    required
                    maxlength="100"
                />
                @error('sku')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <x-input
                    label="Barcode"
                    name="barcode"
                    wire:model="barcode"
                    maxlength="100"
                />
                <p class="text-xs text-muted">Optional. Leave blank if unused.</p>
                @error('barcode')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-col gap-1">
            <label for="category_id" class="text-sm font-medium text-ink">Category</label>
            <select
                id="category_id"
                wire:model="category_id"
                required
                class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
            >
                <option value="">Select a category</option>
                @foreach ($this->assignableCategories() as $category)
                    <option value="{{ $category->id }}">
                        {{ $category->name }}{{ $category->active ? '' : ' (inactive)' }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <p class="text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input
                    label="Selling price"
                    name="selling_price"
                    inputmode="decimal"
                    wire:model="selling_price"
                    required
                />
                @error('selling_price')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <x-input
                    label="Cost price"
                    name="cost_price"
                    inputmode="decimal"
                    wire:model="cost_price"
                />
                <p class="text-xs text-muted">Optional.</p>
                @error('cost_price')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-input
            label="Stock quantity"
            name="stock_quantity"
            inputmode="decimal"
            wire:model="stock_quantity"
            required
        />
        <p class="text-xs text-muted">Opening stock on the product. Later changes should use stock adjustment.</p>
        @error('stock_quantity')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <label class="flex items-center gap-2 text-sm text-ink">
            <input
                type="checkbox"
                wire:model="active"
                class="rounded border-line"
            >
            Active (sellable)
        </label>
        @error('active')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="flex items-center gap-2 pt-2">
            <x-button type="submit" wire:loading.attr="disabled">
                {{ $this->isEditing() ? 'Save changes' : 'Create product' }}
            </x-button>
            <a href="{{ route('products.index') }}" class="text-sm font-medium text-muted hover:text-ink">Cancel</a>
        </div>
    </form>
</div>
