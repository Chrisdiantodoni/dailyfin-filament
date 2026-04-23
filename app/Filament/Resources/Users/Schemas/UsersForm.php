<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\EditUsers;
use App\Filament\Resources\Users\UsersResource;
use App\Models\Dealer;
use App\Models\DealerUser;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Container\Attributes\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log as FacadesLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UsersForm
{

    public static function changePassword($record)
    {
        $users = User::findOrFail($record->id);
        $users->update([
            'last_renew_password_at' => Carbon::now(),
            'force_renew_password' => 1,
            'password' => 'password'
        ]);
        Notification::make()->title('Password di reset')
            ->success()->send();
        $url = UsersResource::getUrl('index');
        return redirect()->to($url);
    }

    public static function editUsers($record, array $data)
    {
        // dd($data);
        $user = User::findOrFail($record->id);

        $user->name = $data['name'];
        $user->email = strtolower($data['email']);
        $user->status = true;
        $user->save();
        $user->roles()->detach();
        if ($data['role']) {
            $role = Role::find($data['role']);
            $user->assignRole($role);
        }
        $dealer_codes = $data['dealers'];
        DealerUser::where('user_id', $record->id)->delete();
        foreach ($dealer_codes as $code) {
            $user_dealer = new DealerUser();
            $user_dealer->user_id = $user->id;
            $user_dealer->dealer_code = $code;
            $user_dealer->save();
        }
        $permissions = $data['permission'];
        $user->permissions()->detach();

        foreach ($permissions as $key => $item) {
            $permission = Permission::find($item);

            if ($permission) {
                if (!$user->hasPermissionTo($permission->name)) {
                    $user->givePermissionTo($permission->name);
                }
            } else {
                // Handle the case where the permission does not exist
                // You might want to log an error or handle it according to your needs
                // For now, we'll just skip this permission
                continue;
            }
        }
        Notification::make()->title('User diupdate')
            ->success()
            ->send();
        $url = UsersResource::getUrl('index');
        return redirect()->to($url);
    }


    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),


                Select::make('roles')
                    ->label('Jabatan')
                    ->relationship('roles', 'name') // asumsi User punya relasi roles()
                    ->required()
                    ->searchable()
                    ->placeholder('Pilih Jabatan'),
                Select::make('dealers') // ✅ Nama relationship
                    ->label('Dealer')
                    ->multiple()
                    ->relationship('dealers', 'dealer_name') // ✅ Langsung ke relationship dealers
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Dealer'),
                // Fieldset::make('Wewenang')
                //     ->schema(function () {
                //         $groups = User::getPermissionGroups();

                //         return $groups->map(function ($group) {
                //             $permissions = User::getpermissionByGroupName($group->group_name);

                //             return Fieldset::make($group->group_name)
                //                 ->schema([


                //                     // Anak checkboxlist
                //                     CheckboxList::make("permissions_{$group->group_name}")
                //                         ->hiddenLabel()
                //                         ->relationship('permissions', 'name')
                //                         ->options($permissions->pluck('name', 'id'))
                //                         ->default($permissions->pluck('id')->toArray()) // default semua centang
                //                         ->reactive()
                //                         ->columns(2)
                //                         ->bulkToggleable()
                //                         ->afterStateUpdated(function ($state, Set $set) use ($group, $permissions) {
                //                             $allSelected = count($state ?? []) === $permissions->count();
                //                             $set("select_all_{$group->group_name}", $allSelected);
                //                         })->columnSpanFull(),
                //                 ]);
                //         })->toArray();
                //     })
                //     ->columnSpanFull(),
                Fieldset::make('Wewenang')
                    ->schema(function () {
                        $groups = User::getPermissionGroups();

                        return $groups->map(function ($group) {
                            $permissions = User::getpermissionByGroupName($group->group_name);

                            return Fieldset::make($group->group_name)
                                ->schema([
                                    CheckboxList::make('permissions') // Gunakan nama relationship yang tepat
                                        ->hiddenLabel()
                                        ->relationship('permissions', 'name')
                                        ->options($permissions->pluck('name', 'id'))
                                        ->reactive()
                                        ->columns(2)
                                        ->bulkToggleable()
                                        ->afterStateUpdated(function ($state, Set $set) use ($group, $permissions) {
                                            $allSelected = count($state ?? []) === $permissions->count();
                                            $set("select_all_{$group->group_name}", $allSelected);
                                        })
                                        ->columnSpanFull(),
                                ]);
                        })->toArray();
                    })
                    ->columnSpanFull(),

                // CheckboxList::make('permissions')
                //     ->label('')
                //     ->relationship('permissions', 'name')
                //     ->bulkToggleable()
                //     ->searchable()
                //     ->gridDirection('row')
                //     ->columns(2)
                //     ->required()
                //     ->helperText('Pilih wewenang yang dimiliki oleh user ini')->columnSpanFull(),
                Actions::make([
                    Action::make('confirm')
                        ->label("Konfirmasi")
                        ->color('primary')
                        ->modalHeading('Konfirmasi Edit')
                        ->modalDescription('
                    Apakah Anda Yakin?
                    ')
                        ->modalSubmitActionLabel('Ya, Konfirmasi')
                        ->modalCancelActionLabel('Batal')
                        ->requiresConfirmation()
                        ->action(function ($record, Get $get) {
                            $formData = [
                                'name'   => $get('name'),
                                'email'  => $get('email'),
                                'role'   => $get('roles'),   // konsisten sama editUsers
                                'dealers' => $get('dealers'),
                                'permission' => $get('permissions'),          // default kosong
                            ];
                            // dd($formData['permission']);

                            // // Ambil semua permission per group
                            // foreach (User::getPermissionGroups() as $group) {
                            //     $permissions = $get("permissions_{$group->group_name}") ?? [];
                            //     dd($permissions);
                            //     $formData['permission'] = array_merge($formData['permission'], $permissions);
                            // }
                            // $formData['permission'] = array_values(array_unique($formData['permission']));

                            self::editUsers($record, $formData);
                        }),
                    Action::make('reset')
                        ->label("Reset Password")
                        ->color('danger')
                        ->outlined()
                        ->modalHeading('Reset Password')
                        ->modalDescription('
                    Apakah Anda Yakin?
                    ')
                        ->modalSubmitActionLabel('Ya, Reset')
                        ->modalCancelActionLabel('Batal')
                        ->requiresConfirmation()
                        ->action(function ($record, Get $get) {

                            self::changePassword($record);
                        }),




                ])->extraAttributes([
                    'class' => 'flex gap-2 bg-transparent',
                ])->columnSpanFull(),
            ]);
    }
}
