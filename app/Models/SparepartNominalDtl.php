<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SparepartNominalDtl extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function cs_service_spareparts()
    {
        return $this->hasOne(CsServiceSparepart::class, 'sparepart_nominal_dtl_id', 'id');
    }
}
