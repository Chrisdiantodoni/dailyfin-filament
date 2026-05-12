<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UserDealerContext
{
    public static function dealerUsers(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        $user->loadMissing('dealer_users.dealers');

        return $user->dealer_users;
    }

    public static function hasMultipleDealers(): bool
    {
        return self::dealerUsers()->count() > 1;
    }

    public static function hasSingleDealer(): bool
    {
        return self::dealerUsers()->count() === 1;
    }

    public static function firstDealerName(): ?string
    {
        return self::dealerUsers()->first()?->dealers?->dealer_name;
    }

    public static function firstDealerCode(): ?string
    {
        return self::dealerUsers()->first()?->dealers?->dealer_code;
    }
}
