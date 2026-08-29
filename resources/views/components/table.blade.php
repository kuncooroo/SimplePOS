<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-md border border-line bg-surface']) }}>
    <table class="min-w-full divide-y divide-line text-left text-sm">
        {{ $slot }}
    </table>
</div>
