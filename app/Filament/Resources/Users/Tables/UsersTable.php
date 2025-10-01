<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Pengguna')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')->label('Jabatan')
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('roles', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->getStateUsing(fn($record) => $record->roles->first()->name ?? "-"),


                TextColumn::make('dealers')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dealer & NEQ')
                    ->html()
                    ->getStateUsing(function ($record) {
                        return $record->dealer_users->map(function ($dealerUser) {
                            if (empty($dealerUser->dealers)) {
                                return null;
                            }

                            // Dealer badge (primary solid)
                            $badges = "<span class='text-xs inline-block max-w-xs mx-1 px-2 py-1 font-semibold text-white bg-primary-600 rounded mb-1'>
                {$dealerUser->dealers->dealer_name}
            </span>";

                            // NEQ badge (primary outline)
                            if (!empty($dealerUser->neqs) && $dealerUser->neqs->count() > 0) {
                                foreach ($dealerUser->neqs as $neq) {
                                    $badges .= "<span  class='text-xs  inline-block max-w-xs mx-1 px-2 py-1 font-semibold border border-primary-600 text-primary-600 bg-transparent rounded mb-1'>
                        {$neq->neq_name}
                    </span>";
                                }
                            } else {
                                $badges .= "<span class='text-xs  inline-block max-w-xs mx-1 px-2 py-1 font-semibold border border-red-600 text-red-600 bg-transparent rounded mb-1'>
                    Tidak Terdapat NEQ
                </span>";
                            }

                            return $badges;
                        })->implode('');
                    })->extraAttributes([
                        'class' => 'max-w-[250px] whitespace-normal'
                    ])->searchable(query: function ($query, $search) {
                        return $query->whereHas('dealer_users.dealers', function ($q) use ($search) {
                            $q->where('dealer_name', 'like', "%{$search}%");
                        })->orWhereHas('dealer_users.neqs', function ($q) use ($search) {
                            $q->where('neq_name', 'like', "%{$search}%");
                        });
                    }),
                ToggleColumn::make('status')
                    ->label('Status')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
                    ->offColor('danger'),




            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
