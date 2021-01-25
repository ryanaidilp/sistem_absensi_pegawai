<?php

namespace App\Models;

use App\Models\PaidLeave;
use App\Models\Outstation;
use App\Models\AbsentPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalStatus extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function permissions()
    {
        return $this->hasMany(AbsentPermission::class, 'approval_status_id', 'id');
    }

    public function outstations()
    {
        return $this->hasMany(Outstation::class, 'approval_status_id', 'id');
    }

    public function paidLeaves()
    {
        return $this->hasMany(PaidLeave::class, 'approval_status_id', 'id');
    }
}
