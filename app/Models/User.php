<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Gender;
use App\Models\Attende;
use App\Models\PaidLeave;
use App\Models\Outstation;
use App\Models\Department;
use App\Models\AbsentPermission;
use Laravel\Sanctum\HasApiTokens;
use App\Models\GovernmentEmployeeGroup;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends \TCG\Voyager\Models\User
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
        return $this->belongsTo(Gender::class, 'gender_id', 'id');
    }

    public function departemen()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function golongan()
    {
        return $this->belongsTo(GovernmentEmployeeGroup::class, 'government_employee_group_id', 'id');
    }

    public function izin()
    {
        return $this->hasMany(AbsentPermission::class, 'user_id', 'id');
    }

    public function dinas_luar()
    {
        return $this->hasMany(Outstation::class, 'user_id', 'id');
    }

    public function cuti()
    {
        return $this->hasMany(PaidLeave::class, 'user_id', 'id');
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

    public function format($date)
    {
        return [
            'nip' => $this->nip,
            'name' => $this->name,
            'department' => $this->departemen->name,
            'position' => $this->position,
            'presensi' =>
            $this->presensi()->with('status_kehadiran')->whereDate('created_at', $date)->get()->map(function ($presensi) use ($date) {
                // dd($presensi);
                $status = $presensi->status_kehadiran->name;
                if ($status === 'Terlambat') {
                    $status = $presensi->status_kehadiran->name;
                    $status .= calculateLateTime($presensi->kode_absen->start_time, $presensi->attend_time, $date);
                }
                return [
                    'status' => $status,
                    'attend_time' => $presensi->attend_time == null ? "-" : Carbon::parse($presensi->attend_time)->format('H:i')
                ];
            })

        ];
    }
}
