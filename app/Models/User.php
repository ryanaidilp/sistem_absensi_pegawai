<?php

namespace App\Models;

use App\Models\Gender;
use App\Models\Attende;
use App\Models\Licensing;
use App\Models\Department;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function gender()
    {
        return $this->hasOne(Gender::class, 'gender_id', 'id');
    }

    public function departemen()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function izin()
    {
        return $this->hasMany(Licensing::class, 'user_id', 'local_key');
    }

    public function absensi()
    {
        return $this->hasMany(Attende::class, 'user_id', 'id');
    }

    public function scopePria($query)
    {
        return $query->where('gender_id', 1);
    }

    public function scopeWanita($query)
    {
        return $query->where('gender_id', 2);
    }
}
