<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $transactionTables = [
            'cashier_deposits' => 'cd',
            'cash_mutates' => 'cm',
            'validation_deposits' => 'vd',
            'cs_service_spareparts' => 'css',
            'cs_units' => 'cu',
            'cashier_takeout_moneys' => 'ctm',
        ];

        foreach ($transactionTables as $table => $prefix) {
            Schema::table($table, function (Blueprint $table) use ($prefix) {
                $table->index(['dealer_code', 'date_published', 'status'], "idx_{$prefix}_dealer_date_status");
                $table->index(['dealer_code', 'created_at'], "idx_{$prefix}_dealer_created");
            });
        }

        Schema::table('dealer_users', function (Blueprint $table) {
            $table->index(['user_id', 'dealer_code'], 'idx_du_user_dealer');
        });

        Schema::table('approval_cashier_deposits', function (Blueprint $table) {
            $table->index('cashier_deposit_id', 'idx_acd_deposit');
            $table->index('user_id', 'idx_acd_user');
        });

        Schema::table('approval_cs_cashiers', function (Blueprint $table) {
            $table->index('cs_service_spareparts_id', 'idx_acc_sparepart');
            $table->index('user_id', 'idx_acc_user');
        });

        Schema::table('approval_cs_cashiers_units', function (Blueprint $table) {
            $table->index('cs_units_id', 'idx_accu_unit');
            $table->index('user_id', 'idx_accu_user');
        });

        Schema::table('approval_mutate_cashes', function (Blueprint $table) {
            $table->index('cash_mutates_id', 'idx_amc_mutate');
            $table->index('user_id', 'idx_amc_user');
        });

        Schema::table('approval_takeout_moneys', function (Blueprint $table) {
            $table->index('cashier_takeouts_id', 'idx_atm_takeout');
            $table->index('user_id', 'idx_atm_user');
        });

        Schema::table('approval_validations', function (Blueprint $table) {
            $table->index('validation_deposits_id', 'idx_av_validation');
            $table->index('user_id', 'idx_av_user');
        });

        Schema::table('cash_images', function (Blueprint $table) {
            $table->index('cash_mutates_id', 'idx_ci_mutate');
        });

        Schema::table('cashier_deposit_images', function (Blueprint $table) {
            $table->index('cashier_deposit_id', 'idx_cdi_deposit');
        });

        Schema::table('service_images', function (Blueprint $table) {
            $table->index('cs_service_spareparts_id', 'idx_si_sparepart');
        });

        Schema::table('unit_images', function (Blueprint $table) {
            $table->index('cs_units_id', 'idx_ui_unit');
        });

        Schema::table('validate_images', function (Blueprint $table) {
            $table->index('validate_deposits_id', 'idx_vi_validation');
        });

        Schema::table('validation_proof_images', function (Blueprint $table) {
            $table->index('validation_deposits_id', 'idx_vpi_validation');
        });
    }

    public function down(): void
    {
        $transactionTables = [
            'cashier_deposits' => 'cd',
            'cash_mutates' => 'cm',
            'validation_deposits' => 'vd',
            'cs_service_spareparts' => 'css',
            'cs_units' => 'cu',
            'cashier_takeout_moneys' => 'ctm',
        ];

        foreach ($transactionTables as $table => $prefix) {
            Schema::table($table, function (Blueprint $table) use ($prefix) {
                $table->dropIndex("idx_{$prefix}_dealer_date_status");
                $table->dropIndex("idx_{$prefix}_dealer_created");
            });
        }

        Schema::table('dealer_users', function (Blueprint $table) {
            $table->dropIndex('idx_du_user_dealer');
        });

        Schema::table('approval_cashier_deposits', function (Blueprint $table) {
            $table->dropIndex('idx_acd_deposit');
            $table->dropIndex('idx_acd_user');
        });

        Schema::table('approval_cs_cashiers', function (Blueprint $table) {
            $table->dropIndex('idx_acc_sparepart');
            $table->dropIndex('idx_acc_user');
        });

        Schema::table('approval_cs_cashiers_units', function (Blueprint $table) {
            $table->dropIndex('idx_accu_unit');
            $table->dropIndex('idx_accu_user');
        });

        Schema::table('approval_mutate_cashes', function (Blueprint $table) {
            $table->dropIndex('idx_amc_mutate');
            $table->dropIndex('idx_amc_user');
        });

        Schema::table('approval_takeout_moneys', function (Blueprint $table) {
            $table->dropIndex('idx_atm_takeout');
            $table->dropIndex('idx_atm_user');
        });

        Schema::table('approval_validations', function (Blueprint $table) {
            $table->dropIndex('idx_av_validation');
            $table->dropIndex('idx_av_user');
        });

        Schema::table('cash_images', function (Blueprint $table) {
            $table->dropIndex('idx_ci_mutate');
        });

        Schema::table('cashier_deposit_images', function (Blueprint $table) {
            $table->dropIndex('idx_cdi_deposit');
        });

        Schema::table('service_images', function (Blueprint $table) {
            $table->dropIndex('idx_si_sparepart');
        });

        Schema::table('unit_images', function (Blueprint $table) {
            $table->dropIndex('idx_ui_unit');
        });

        Schema::table('validate_images', function (Blueprint $table) {
            $table->dropIndex('idx_vi_validation');
        });

        Schema::table('validation_proof_images', function (Blueprint $table) {
            $table->dropIndex('idx_vpi_validation');
        });
    }
};
