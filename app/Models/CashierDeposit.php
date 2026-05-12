<?php

namespace App\Models;

use App\Observers\CashierDepositObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


#[ObservedBy([CashierDepositObserver::class])]
class CashierDeposit extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];

    protected $casts = [
        'is_seen_ops' => 'boolean',
        'is_seen_spv' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function cashier_images()
    {
        return $this->hasMany(CashierDepositImage::class, 'cashier_deposit_id', 'id');
    }

    public function approval_cashier()
    {
        return $this->hasMany(ApprovalCashierDeposit::class, 'cashier_deposit_id', 'id');
    }

    public function dealers()
    {
        return $this->belongsTo(Dealer::class, 'dealer_code', 'dealer_code');
    }
}
