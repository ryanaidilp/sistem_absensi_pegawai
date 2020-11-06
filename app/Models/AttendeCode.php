<?php

namespace App\Models;

use App\Models\AttendeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendeCode extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function tipe()
    {
        return $this->belongsTo(AttendeType::class, 'attende_type_id', 'id');
    }

    public function kehadiran()
    {
        return $this->hasMany(Attende::class, 'attende_code_id', 'id');
    }
}
