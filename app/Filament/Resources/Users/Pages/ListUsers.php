<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UsersResource;
use App\Models\Dealer;
use App\Models\DealerUser;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ListUsers extends ListRecords
{
    protected static string $resource = UsersResource::class;

    public function createUser(array $data): void
    {

        try {
            // dd($data);
            //code...
            DB::beginTransaction();
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'role' => 'admin',
                'password' =>  'password',
                'dealer_code' => "9F0004",
                'is_password_changed' => Carbon::now(),
                'status' => 1,
            ]);
            $role = Role::find($data['role']);
            if ($role) {
                $user->assignRole($role->name);
            }
            $permissions = Permission::where('group_name', '=', $role->name)->get();
            // ddd($permissions);
            foreach ($permissions as $key => $item) {
                $data['user_id'] = $user->id;
                $data['permission_id'] = $item->id;
                DB::table('user_has_permissions')->insert([
                    'permission_id' => $item->id,
                    'user_id' => $user->id
                ]);
            }
            $dealers = $data['dealers'];
            foreach ($dealers as $code) {
                $user_dealer = new DealerUser();
                $user_dealer->user_id = $user->id;
                $user_dealer->dealer_code = $code;
                $user_dealer->save();
            }
            DB::commit();

            Notification::make()
                ->title('User Berhasil Dibuat')
                ->success()
                ->send();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            Notification::make()
                ->title('Gagal Membuat User: ' . $th->getMessage())
                ->danger()
                ->send();
        }
        // dd($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_user')
                ->label('Create User')
                ->icon('heroicon-o-plus')
                ->modalHeading('Buat User Baru')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->required(),

                    Select::make('dealers')
                        ->label('Dealer')
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->placeholder('Pilih Dealer')
                        ->options(Dealer::pluck('dealer_name', 'dealer_code')),

                    Select::make('role')
                        ->label('Jabatan')
                        ->required()
                        ->options(Role::pluck('name', 'id')) // ambil daftar role
                        ->searchable()
                        ->placeholder('Pilih Jabatan'),

                ])
                ->action(function (array $data) {
                    $this->createUser($data);
                })
        ];
    }
}
