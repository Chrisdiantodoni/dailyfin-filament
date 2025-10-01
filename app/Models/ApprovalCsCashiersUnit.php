<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalCsCashiersUnit extends Model
{
    use HasFactory;

    protected $guarded = [];
    public function cs_units()
    {
        return $this->belongsTo(CsUnit::class, 'cs_units', 'id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
