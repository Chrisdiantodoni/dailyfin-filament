<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilters;
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

class LatestTransactionsTable extends Widget
{
    use HasDashboardFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'lg' => 6,
    ];

    protected ?string $heading = 'Transaksi Terbaru';

    protected string $view = 'filament.widgets.latest-transactions';

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function getTransactions(): Collection
    {
        $range = $this->dashboardDateRange();

        return cache()->remember($this->dashboardCacheKey('latest-transactions', $range), now()->addMinutes(2), function () use ($range) {
            return collect([
                $this->transactionRows(CashierDeposit::query(), $range, 'Setoran Brankas', 'total_deposit'),
                $this->transactionRows(CashMutate::query(), $range, 'Mutasi Kas', 'physical_cash'),
                $this->transactionRows(ValidationDeposit::query(), $range, 'Validasi Setoran', 'nominal_deposit'),
                $this->transactionRows(cashier_takeout_money::query(), $range, 'Pengambilan Uang', 'takeout_nominal'),
                $this->transactionRows(CsServiceSparepart::query(), $range, 'CS Sparepart', 'total_income'),
                $this->transactionRows(CsUnit::query(), $range, 'CS Unit', 'total_income'),
            ])
                ->flatten(1)
                ->sortByDesc('created_at')
                ->take(10)
                ->values();
        });
    }

    protected function transactionRows(Builder $query, array $range, string $type, string $amountColumn): Collection
    {
        return $this->applyDashboardFilters($query, $range)
            ->select('date_published', 'status', DB::raw("'{$type}' as type"), DB::raw("{$amountColumn} as amount"), 'created_at')
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

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'request' => 'warning',
            'approve' => 'success',
            'reject' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'request' => 'Menunggu',
            'approve' => 'Disetujui',
            'reject' => 'Ditolak',
            default => ucfirst($status),
        };
    }
}
