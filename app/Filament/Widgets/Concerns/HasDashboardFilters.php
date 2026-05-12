<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasDashboardFilters
{
    protected ?array $dashboardDealerCodesCache = null;

    protected function dashboardDateRange(?int $defaultDays = 1): array
    {
        $end = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : Carbon::today()->endOfDay();

        $start = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : $end->copy()->subDays($defaultDays - 1)->startOfDay();

        return [$start, $end];
    }

    protected function dashboardDealerCodes(): array
    {
        if ($this->dashboardDealerCodesCache !== null) {
            return $this->dashboardDealerCodesCache;
        }

        $allowedDealerCodes = Auth::user()
            ? Auth::user()->dealer_users()->pluck('dealer_code')->filter()->values()->all()
            : [];

        $selectedDealerCode = $this->pageFilters['dealer_code'] ?? null;

        if (filled($selectedDealerCode) && in_array($selectedDealerCode, $allowedDealerCodes, true)) {
            return $this->dashboardDealerCodesCache = [$selectedDealerCode];
        }

        return $this->dashboardDealerCodesCache = $allowedDealerCodes;
    }

    protected function applyDashboardFilters(Builder $query, ?array $range = null): Builder
    {
        [$start, $end] = $range ?? $this->dashboardDateRange();
        $dealerCodes = $this->dashboardDealerCodes();

        return $query
            ->whereBetween('date_published', [$start->toDateString(), $end->toDateString()])
            ->when(
                $dealerCodes !== [],
                fn (Builder $query) => $query->whereIn('dealer_code', $dealerCodes),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
            );
    }

    protected function dashboardCacheKey(string $name, ?array $range = null): string
    {
        [$start, $end] = $range ?? $this->dashboardDateRange();

        return implode(':', [
            'dashboard',
            $name,
            $start->toDateString(),
            $end->toDateString(),
            md5(implode(',', $this->dashboardDealerCodes())),
        ]);
    }
}
