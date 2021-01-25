<?php

namespace App\Models;

use App\Models\User;
use App\Models\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Outstation extends Model
{
    use HasFactory;

    const APPROVED = 1;
    const PENDING = 2;
    const REJECTED = 3;

    protected $guarded = [];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(ApprovalStatus::class, 'approval_status_id', 'id');
    }
}
