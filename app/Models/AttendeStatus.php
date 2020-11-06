<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendeStatus extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function kehadiran()
    {
        return $this->hasMany(Attende::class, 'attende_status_id', 'id');
    }
}
