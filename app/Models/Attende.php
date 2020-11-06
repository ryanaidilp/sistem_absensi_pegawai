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

    protected $guarded = [];

    public function pegawai()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function kode_absen()
    {
        return $this->belongsTo(AttendeCode::class, 'attende_code_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(AttendeStatus::class, 'attende_status_id', 'id');
    }

    public function scopeToday($query)
    {
        return $query->where('created_at', today());
    }
}
