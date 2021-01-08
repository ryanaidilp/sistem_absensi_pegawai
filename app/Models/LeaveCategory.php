<?php

namespace App\Models;

use App\Models\PaidLeave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveCategory extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function cuti()
    {
        return $this->hasMany(PaidLeave::class, 'leave_category_id', 'id');
    }
}
