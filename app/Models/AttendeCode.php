<?php

namespace App\Models;

use App\Models\AttendeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendeCode extends Model
{
    use HasFactory;

    const MORNING = 1;
    const LUNCH_BREAK = 2;
    const AFTERNOON = 3;
    const EVENING = 4;

    protected $guarded = [];

    public function tipe()
    {
        return $this->belongsTo(AttendeType::class, 'code_type_id', 'id');
    }

    public function kehadiran()
    {
        return $this->hasMany(Attende::class, 'attende_code_id', 'id');
    }

    public function scopePagi($query)
    {
        return $query->where('code_type_id', self::MORNING);
    }

    public function scopeIstrahat($query)
    {
        return $query->where('code_type_id', self::LUNCH_BREAK);
    }

    public function scopeSiang($query)
    {
        return $query->where('code_type_id', self::AFTERNOON);
    }

    public function scopeSore($query)
    {
        return $query->where('code_type_id', self::EVENING);
    }
}
