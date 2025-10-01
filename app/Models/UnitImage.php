<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitImage extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function cs_units()
    {
        return $this->belongsTo(CsUnit::class, 'cs_units_id', 'id');
    }
}
