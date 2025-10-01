<?php

namespace App\Models;

use App\Observers\CashMutationObserver;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


#[ObservedBy([CashMutationObserver::class])]

class CashMutate extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // public function setUpRichContent(): void
    // {
    //     $this->registerRichContent('description2');
    // }

    public function dealers()
    {
        return $this->belongsTo(Dealer::class, 'dealer_code', 'dealer_code');
    }

    public function approval_mutate()
    {
        return $this->hasMany(ApprovalMutateCash::class, 'cash_mutates_id', 'id');
    }

    public function cash_images()
    {
        return $this->hasMany(CashImages::class, 'cash_mutates_id', 'id');
    }
}
