<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cashier_takeout_moneys', 'status_deadline')) {
            Schema::table('cashier_takeout_moneys', function (Blueprint $table): void {
                $table->string('status_deadline')->nullable()->after('status');
            });
        }

        $this->backfillApprovalDeadlineStatus();
    }

    public function down(): void
    {
        if (Schema::hasColumn('cashier_takeout_moneys', 'status_deadline')) {
            Schema::table('cashier_takeout_moneys', function (Blueprint $table): void {
                $table->dropColumn('status_deadline');
            });
        }
    }

    private function backfillApprovalDeadlineStatus(): void
    {
        if (
            ! Schema::hasTable('approval_takeout_moneys')
            || ! Schema::hasTable('model_has_roles')
            || ! Schema::hasTable('roles')
        ) {
            return;
        }

        DB::table('cashier_takeout_moneys as takeouts')
            ->joinSub(
                DB::table('approval_takeout_moneys as approvals')
                    ->join('model_has_roles', function ($join): void {
                        $join->on('model_has_roles.model_id', '=', 'approvals.user_id')
                            ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
                    })
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('approvals.status', 'approve')
                    ->where('roles.name', 'Finance Operation')
                    ->groupBy('approvals.cashier_takeouts_id')
                    ->select([
                        'approvals.cashier_takeouts_id',
                        DB::raw('MIN(approvals.created_at) as approved_at'),
                    ]),
                'finance_approvals',
                'finance_approvals.cashier_takeouts_id',
                '=',
                'takeouts.id'
            )
            ->whereNull('takeouts.status_deadline')
            ->whereNotNull('takeouts.date_published')
            ->select([
                'takeouts.id',
                'takeouts.date_published',
                'finance_approvals.approved_at',
            ])
            ->orderBy('takeouts.id')
            ->get()
            ->each(function ($row): void {
                $deadline = Carbon::parse($row->date_published)->setTime(12, 0);
                $approvedAt = Carbon::parse($row->approved_at);

                DB::table('cashier_takeout_moneys')
                    ->where('id', $row->id)
                    ->update([
                        'status_deadline' => $approvedAt->lte($deadline) ? 'On-time' : 'Late',
                    ]);
            });
    }
};
