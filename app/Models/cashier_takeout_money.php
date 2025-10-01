<?php

namespace App\Models;

use App\Observers\TakeoutMoneyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

#[ObservedBy([TakeoutMoneyObserver::class])]

class cashier_takeout_money extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function dealers()
    {
        return $this->belongsTo(Dealer::class, 'dealer_code', 'dealer_code');
    }


    public function approval_takeout_money()
    {
        return $this->hasMany(approval_takeout_money::class, 'cashier_takeouts_id', 'id');
    }
}
