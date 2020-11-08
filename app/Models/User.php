<?php

namespace App\Models;

use App\Models\Gender;
use App\Models\Attende;
use App\Models\Department;
use App\Models\AbsentPermission;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

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
        return $this->hasMany(AbsentPermission::class, 'user_id', 'id');
    }

    public function presensi()
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

    public function scopePns($query)
    {
        return $query->where('status', 'PNS');
    }

    public function scopeHonorer($query)
    {
        return $query->where('status', 'Honorer');
    }

    public function format()
    {
        return [
            'nip' => $this->nip,
            'name' => $this->name,
            'department' => $this->departemen->name,
            'position' => $this->position,
            'presensi' =>
            $this->presensi()->with('status_kehadiran')->today()->get()->map(function ($presensi) {
                // dd($presensi);
                return [
                    'status' => $presensi->status_kehadiran->name,
                    'jam_absen' => $presensi->attende_time == null ? "Belum Ada" : $presensi->attende_time->format('H:i')
                ];
            })

        ];
    }
}
