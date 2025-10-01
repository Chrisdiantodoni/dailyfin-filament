<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceNominalDtl extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function cs_service_spareparts()
    {
        return $this->hasOne(CsServiceSparepart::class, 'service_nominal_dtl_id', 'id');
    }
}
