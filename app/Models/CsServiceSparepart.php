<?php

namespace App\Models;

use App\Observers\CounterServiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


#[ObservedBy([CounterServiceObserver::class])]
class CsServiceSparepart extends Model
{
    use HasFactory, HasUuids, BroadcastsEvents;
    protected $guarded = [];

    public function service_nominal_dtls()
    {
        return $this->belongsTo(ServiceNominalDtl::class, 'service_sparepart_dtl', 'id');
    }


    public function sparepart_images()
    {
        return $this->hasMany(SparepartImage::class, 'cs_service_spareparts_id', 'id');
    }
    public function service_images()
    {
        return $this->hasMany(ServiceImage::class, 'cs_service_spareparts_id', 'id');
    }
    public function approval_cs_cashiers()
    {

        return $this->hasMany(ApprovalCsCashier::class, 'cs_service_spareparts_id', 'id');
    }
    public function users()
    {

        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function dealers()
    {
        return $this->belongsTo(Dealer::class, 'dealer_code', 'dealer_code');
    }
}
