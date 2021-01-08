<?php

namespace App\Models;

use App\Models\User;
use App\Models\AttendeCode;
use App\Models\AttendeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attende extends Model
{
    use HasFactory;


    const ON_TIME = 1;
    const LATE = 2;
    const ABSENT = 3;
    const PERMISSION = 4;
    const OUTSTATION = 5;
    const ANNUAL_LEAVE = 6;
    const IMPORTANT_REASON_LEAVE = 7;
    const SICK_LEAVE = 8;
    const MATERNITY_LEAVE = 9;
    const OUT_OF_LIABILITY_LEAVE = 10;

    protected $guarded = [];

    public function pegawai()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function kode_absen()
    {
        return $this->belongsTo(AttendeCode::class, 'attende_code_id', 'id');
    }

    public function status_kehadiran()
    {
        return $this->belongsTo(AttendeStatus::class, 'attende_status_id', 'id');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeHadir($query)
    {
        return $query->where('attende_status_id', self::ON_TIME);
    }

    public function scopeTerlambat($query)
    {
        return $query->where('attende_status_id', self::LATE);
    }

    public function scopeAbsen($query)
    {
        return $query->where('attende_status_id', self::ABSENT);
    }

    public function scopeIzin($query)
    {
        return $query->where('attende_status_id', self::PERMISSION);
    }

    public function scopeDinasLuar($query)
    {
        return $query->where('attende_status_id', self::OUTSTATION);
    }

    public function scopeCutiTahunan($query)
    {
        return $query->where('attende_status_id', self::ANNUAL_LEAVE);
    }

    public function scopeCutiAlasanPenting($query)
    {
        return $query->where('attende_status_id', self::IMPORTANT_REASON_LEAVE);
    }

    public function scopeCutiSakit($query)
    {
        return $query->where('attende_status_id', self::SICK_LEAVE);
    }

    public function scopeCutiBersalin($query)
    {
        return $query->where('attende_status_id', self::MATERNITY_LEAVE);
    }

    public function scopeCutiDiluarTanggungan($query)
    {
        return $query->where('attende_status_id', self::OUT_OF_LIABILITY_LEAVE);
    }

    public function format()
    {
        return [
            'user' => [
                'name' => $this->pegawai->name,
                'position' => $this->pegawai->position,
                'department' => $this->pegawai->departemen->name,
                'status' => $this->pegawai->status
            ],
            'status_kehadiran' => $this->status_kehadiran->name,
            'jam_absen' => $this->attend_time,
        ];
    }
}
