<?php

namespace App\Models;

use App\Models\AttendeCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendeType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function kode_absen()
    {
        return $this->hasMany(AttendeCode::class, 'code_type_id', 'id');
    }
}
