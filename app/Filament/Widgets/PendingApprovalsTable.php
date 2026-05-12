<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CashierDeposits\CashierDepositResource;
use App\Filament\Resources\CashMutates\CashMutateResource;
use App\Filament\Widgets\Concerns\HasDashboardFilters;
use App\Filament\Resources\CounterServiceDeposits\CounterServiceDepositResource;
use App\Filament\Resources\CounterServiceUnits\CounterServiceUnitResource;
use App\Filament\Resources\TakeoutMoney\TakeoutMoneyResource;
use App\Filament\Resources\ValidationDeposits\ValidationDepositResource;
use App\Models\cashier_takeout_money;
use App\Models\CashierDeposit;
use App\Models\CashMutate;
use App\Models\CsServiceSparepart;
use App\Models\CsUnit;
use App\Models\ValidationDeposit;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PendingApprovalsTable extends Widget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 6,
    ];

    protected ?string $heading = 'Menunggu Persetujuan';

    protected string $view = 'filament.widgets.pending-approvals';

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function getPendingTransactions(): Collection
    {
        $range = $this->dashboardDateRange();

        return cache()->remember($this->dashboardCacheKey('pending-approvals', $range), now()->addMinutes(2), function () use ($range) {
            return collect([
                $this->pendingRows(CashierDeposit::query(), $range, 'Setoran Brankas', 'total_deposit'),
                $this->pendingRows(CashMutate::query(), $range, 'Mutasi Kas', 'physical_cash'),
                $this->pendingRows(ValidationDeposit::query(), $range, 'Validasi Setoran', 'nominal_deposit'),
                $this->pendingRows(cashier_takeout_money::query(), $range, 'Pengambilan Uang', 'takeout_nominal'),
                $this->pendingRows(CsServiceSparepart::query(), $range, 'CS Sparepart', 'total_income'),
                $this->pendingRows(CsUnit::query(), $range, 'CS Unit', 'total_income'),
            ])
                ->flatten(1)
                ->sortByDesc('created_at')
                ->take(10)
                ->values();
        });
    }

    protected function pendingRows(Builder $query, array $range, string $type, string $amountColumn): Collection
    {
        return $this->applyDashboardFilters($query, $range)
            ->where('status', 'request')
            ->select('id', 'date_published', DB::raw("'{$type}' as type"), DB::raw("{$amountColumn} as amount"), 'created_at')
            ->latest()
            ->limit(5)
            ->get();
    }

    public function getColor(string $type): string
    {
        return match ($type) {
            'Setoran Brankas' => 'primary',
            'Mutasi Kas' => 'info',
            'Validasi Setoran' => 'success',
            'Pengambilan Uang' => 'danger',
            'CS Sparepart', 'CS Unit' => 'warning',
            default => 'gray',
        };
    }

    public function getResourceUrl(string $type, string|int $id): string
    {
        $resource = match ($type) {
            'Setoran Brankas' => CashierDepositResource::class,
            'Mutasi Kas' => CashMutateResource::class,
            'Validasi Setoran' => ValidationDepositResource::class,
            'Pengambilan Uang' => TakeoutMoneyResource::class,
            'CS Sparepart' => CounterServiceDepositResource::class,
            'CS Unit' => CounterServiceUnitResource::class,
            default => null,
        };

        if (! $resource) {
            return '#';
        }

        return $resource::getUrl('detail', ['record' => $id]);
    }
}
