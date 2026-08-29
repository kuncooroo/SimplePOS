<div>
    <x-page-header title="Profile">
        <x-slot:description>
            Update your name and password. Role and login email are managed by store administrators.
        </x-slot:description>
    </x-page-header>

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
            value="{{ $email }}"
            readonly
            disabled
            autocomplete="username"
        />
        <p class="text-xs text-muted">Email cannot be changed from your profile.</p>

        <div class="flex flex-col gap-1">
            <label for="role" class="text-sm font-medium text-ink">Role</label>
            <input
                id="role"
                type="text"
                value="{{ $roleLabel }}"
                readonly
                disabled
                class="rounded-md border border-line bg-canvas px-3 py-2 text-sm text-muted"
            >
        </div>

        <div class="border-t border-line pt-4">
            <p class="mb-3 text-sm font-medium text-ink">Change password</p>

            <div class="space-y-4">
                <x-input
                    label="Current password"
                    name="current_password"
                    type="password"
                    wire:model="current_password"
                    autocomplete="current-password"
                />
                @error('current_password')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror

                <x-input
                    label="New password"
                    name="password"
                    type="password"
                    wire:model="password"
                    autocomplete="new-password"
                />
                <p class="text-xs text-muted">Leave blank to keep your current password.</p>
                @error('password')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror

                <x-input
                    label="Confirm new password"
                    name="password_confirmation"
                    type="password"
                    wire:model="password_confirmation"
                    autocomplete="new-password"
                />
                @error('password_confirmation')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <x-button type="submit" wire:loading.attr="disabled">Save profile</x-button>
        </div>
    </form>
</div>
