<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Dealer extends Model
{
    use HasFactory, HasRoles;
    protected $guarded = [];
    protected $guard_name = 'web';
    protected $primaryKey = 'dealer_code'; // ✅ Set primary key
    public $incrementing = false;          // ✅ Non-incrementing
    protected $keyType = 'string';
    public function users()
    {
        return $this->belongsToMany(User::class, 'dealer_users', 'dealer_code', 'user_id');
    }
}
