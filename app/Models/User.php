<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Yebor974\Filament\RenewPassword\Contracts\RenewPasswordContract;
use Yebor974\Filament\RenewPassword\Traits\RenewPassword;

class User extends Authenticatable implements AuthorizableContract, RenewPasswordContract
{
    use HasFactory, HasUuids, HasRoles, HasPermissions, Notifiable, Authorizable, HasApiTokens, RenewPassword;
    public function getRouteKeyName(): string
    {
        // Default tetap pakai uuid
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        // Kalau integer (admin)
        if (ctype_digit((string) $value)) {
            return $this->where('id', (int) $value)->firstOrFail();
        }

        // Kalau string (uuid user biasa)
        return $this->where('id', $value)->firstOrFail();
    }


    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function dealer_users()
    {
        return $this->hasMany(DealerUser::class, 'user_id', 'id');
    }

    public function dealers()
    {
        return $this->belongsToMany(Dealer::class, 'dealer_users', 'user_id', 'dealer_code');
        // atau jika ikut convention Laravel:
        // return $this->belongsToMany(Dealer::class, 'dealer_users');
    }

    public static function getPermissionGroups()
    {
        $permission_groups = DB::table('permissions')->select('group_name')->groupBy('group_name')->get();
        return $permission_groups;
    }

    public static function getpermissionByGroupName($group_name)
    {
        $permissions = DB::table('permissions')
            ->select('name', 'id')
            ->where('group_name', $group_name)
            ->get();
        return $permissions;
    }


    public static function roleHasPermissions($user, $permissions)
    {
        $hasPermission = true;
        foreach ($permissions as $permission) {
            if (!$user->hasPermissionTo($permission->name)) {
                $hasPermission = false;
            }
            return $hasPermission;
        }
    } // End Method 

    public function canUser($permission)
    {
        // Check if the user has the given permission directly
        $userPermissions = $this->getAllPermissions()->pluck('name')->toArray();

        // Check if the user has the specified permission(s)
        return is_array($permission)
            ? count(array_intersect($permission, $userPermissions)) > 0
            : in_array($permission, $userPermissions);
        // return $this->permissions()->where('name', $permission)->exists();
    }

    public function permissions()
    {
        // Define the relationship with the user_has_permission table
        return $this->belongsToMany(Permission::class, 'user_has_permissions');
    }

    public function receivesBroadcastNotificationsOn()
    {
        return 'App.Models.User.' . $this->id;
    }
}
