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
}
