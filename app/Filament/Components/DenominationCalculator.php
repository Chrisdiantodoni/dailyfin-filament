<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Field;

class DenominationCalculator extends Field
{
    protected string $view = 'partials.rincian-fisik-kas';

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (DenominationCalculator $component, $state) {
            // Set state default untuk setiap denominasi jika belum ada
            $defaults = [
                'denom_100k' => 0,
                'denom_75k' => 0,
                'denom_50k' => 0,
                'denom_20k' => 0,
                'denom_10k' => 0,
                'denom_5k' => 0,
                'denom_2k' => 0,
                'denom_1k' => 0,
                'denom_500' => 0,
                'denom_200' => 0,
                'denom_100' => 0,
            ];

            $currentState = $state ?? [];
            $newState = array_merge($defaults, $currentState);

            $component->state($newState);
        });

        $this->dehydrateStateUsing(function (DenominationCalculator $component, $state) {
            // Pastikan state selalu berupa array dengan semua denominasi
            return array_merge([
                'denom_100k' => 0,
                'denom_75k' => 0,
                'denom_50k' => 0,
                'denom_20k' => 0,
                'denom_10k' => 0,
                'denom_5k' => 0,
                'denom_2k' => 0,
                'denom_1k' => 0,
                'denom_500' => 0,
                'denom_200' => 0,
                'denom_100' => 0,
            ], $state ?? []);
        });
    }
}
