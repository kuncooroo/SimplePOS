@props(['checks', 'canContinue' => false, 'continueRoute', 'backRoute' => null, 'backLabel' => 'Back'])

<ul class="space-y-3">
    @foreach ($checks as $check)
        <li class="rounded-md border border-line px-3 py-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-ink">{{ $check->label }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $check->message }}</p>
                    @if ($check->detail)
                        <p class="mt-1 text-xs text-muted">{{ $check->detail }}</p>
                    @endif
                </div>
                <span @class([
                    'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold uppercase tracking-wide',
                    'bg-emerald-50 text-emerald-700' => $check->status === \App\Support\Install\InstallCheck::STATUS_PASS,
                    'bg-amber-50 text-amber-700' => $check->status === \App\Support\Install\InstallCheck::STATUS_WARN,
                    'bg-red-50 text-red-700' => $check->status === \App\Support\Install\InstallCheck::STATUS_FAIL,
                ])>
                    {{ $check->status }}
                </span>
            </div>
        </li>
    @endforeach
</ul>

@if ($errors->has('checks'))
    <p class="mt-4 text-sm text-red-600">{{ $errors->first('checks') }}</p>
@endif

<div class="mt-6 flex flex-col gap-3">
    <form method="POST" action="{{ $continueRoute }}">
        @csrf
        <button
            type="submit"
            @disabled(! $canContinue)
            @class([
                'inline-flex w-full items-center justify-center rounded-md px-4 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2',
                'bg-brand text-white hover:bg-brand/90' => $canContinue,
                'cursor-not-allowed bg-line text-muted' => ! $canContinue,
            ])
        >
            Continue
        </button>
    </form>

    @if ($backRoute)
        <a
            href="{{ $backRoute }}"
            class="inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
        >
            {{ $backLabel }}
        </a>
    @endif
</div>

@if (! $canContinue)
    <p class="mt-3 text-sm text-muted">
        Resolve every required failure above before continuing.
    </p>
@endif
