<div class="mb-4 space-y-2" aria-live="polite">
    @if (session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif
    @if (session('warning'))
        <x-alert type="warning">{{ session('warning') }}</x-alert>
    @endif
    @if (session('status'))
        <x-alert>{{ session('status') }}</x-alert>
    @endif
</div>
