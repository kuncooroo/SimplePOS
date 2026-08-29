<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use Illuminate\Support\Carbon;

final readonly class ReportingPeriod
{
    public Carbon $startsAt;

    public Carbon $endsAt;

    public function __construct(
        public string $dateFrom,
        public string $dateTo,
    ) {
        $timezone = (string) config('app.timezone');

        $this->startsAt = Carbon::parse($dateFrom, $timezone)->startOfDay();
        $this->endsAt = Carbon::parse($dateTo, $timezone)->endOfDay();
    }
}
