<div>
    <div class="mb-4">
        <x-page-header :title="$this->isEditing() ? 'Edit user' : 'New user'">
            <x-slot:description>
                @if ($this->isEditing())
                    Update account details. Leave the password blank to keep the current one.
                @else
                    Operational users are created here. There is no public registration.
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
            autocomplete="name"
        />
        @error('name')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <x-input
            label="Email"
            name="email"
            type="email"
            wire:model="email"
            required
            autocomplete="email"
        />
        @error('email')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <x-input
            label="{{ $this->isEditing() ? 'New password' : 'Password' }}"
            name="password"
            type="password"
            wire:model="password"
            autocomplete="new-password"
            @required(! $this->isEditing())
        />
        <p class="text-xs text-muted">At least 8 characters (Laravel default). @if ($this->isEditing()) Leave blank to keep the current password. @endif</p>
        @error('password')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <x-input
            label="Confirm password"
            name="password_confirmation"
            type="password"
            wire:model="password_confirmation"
            autocomplete="new-password"
            @required(! $this->isEditing())
        />
        @error('password_confirmation')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="flex flex-col gap-1">
            <label for="role" class="text-sm font-medium text-ink">Role</label>
            <select
                id="role"
                wire:model="role"
                @disabled($this->isEditingSelf())
                class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink disabled:cursor-not-allowed disabled:opacity-60"
            >
                @foreach ($this->assignableRoles() as $assignableRole)
                    <option value="{{ $assignableRole->value }}">{{ $assignableRole->label() }}</option>
                @endforeach
            </select>
            @if ($this->isEditingSelf())
                <p class="text-xs text-muted">You cannot change your own role on this screen.</p>
            @endif
            @error('role')
                <p class="text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink">
            <input
                type="checkbox"
                wire:model="active"
                @disabled($this->isEditingSelf())
                class="rounded border-line"
            >
            Active
        </label>
        @if ($this->isEditingSelf())
            <p class="text-xs text-muted">You cannot deactivate your own account.</p>
        @endif
        @error('active')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="flex items-center gap-2 pt-2">
            <x-button type="submit" wire:loading.attr="disabled">
                {{ $this->isEditing() ? 'Save changes' : 'Create user' }}
            </x-button>
            <a href="{{ route('users.index') }}" class="text-sm font-medium text-muted hover:text-ink">Cancel</a>
        </div>
    </form>
</div>
