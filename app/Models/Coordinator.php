<?php

namespace App\Models;

use App\Observers\CoordinatorObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([CoordinatorObserver::class])]

class Coordinator extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];


    public function dealers()
    {
        return $this->belongsTo(Dealer::class, 'dealer_code', 'dealer_code');
    }
}
