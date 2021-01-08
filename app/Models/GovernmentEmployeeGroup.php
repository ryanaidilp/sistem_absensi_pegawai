<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovernmentEmployeeGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function pegawai()
    {
        return $this->hasMany(User::class, 'government_employee_group_id', 'id');
    }
}
