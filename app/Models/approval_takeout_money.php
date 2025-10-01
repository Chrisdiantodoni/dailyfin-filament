<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class approval_takeout_money extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function cashier_takeout_moneys()
    {
        return $this->belongsTo(cashier_takeout_money::class, 'cashier_takeouts_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
