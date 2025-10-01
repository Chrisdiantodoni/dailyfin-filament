<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitNominalDtl extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function cs_units()
    {
        return $this->hasOne(CsUnit::class, 'unit_nominal_dtl_id', 'id');
    }
}
