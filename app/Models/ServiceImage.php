<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceImage extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function cs_service_spareparts()
    {
        return $this->belongsTo(CsServiceSparepart::class, 'cs_service_spareparts_id', 'id');
    }
}
