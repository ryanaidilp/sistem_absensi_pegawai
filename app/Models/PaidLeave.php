<?php

namespace App\Models;

use App\Models\User;
use App\Models\LeaveCategory;
use App\Models\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaidLeave extends Model
{
    use HasFactory;

    const ANNUAL = 1;
    const IMPORTANT_REASON = 2;
    const MATERNITY = 3;
    const SICK = 4;
    const OUT_OF_LIABILITY = 5;

    const APPROVED = 1;
    const PENDING = 2;
    const REJECTED = 3;

    protected $guarded = [];

    public function kategori()
    {
        return $this->belongsTo(LeaveCategory::class, 'leave_category_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(ApprovalStatus::class, 'approval_status_id', 'id');
    }

    public function scopeTahunan($query)
    {
        return $query->where('leave_category_id', self::ANNUAL);
    }

    public function scopeAlasanPenting($query)
    {
        return $query->where('leave_category_id', self::IMPORTANT_REASON);
    }

    public function scopeBersalin($query)
    {
        return $query->where('leave_category_id', self::MATERNITY);
    }

    public function scopeSakit($query)
    {
        return $query->where('leave_category_id', self::SICK);
    }


    public function scopeDiluarTanggungan($query)
    {
        return $query->where('leave_category_id', self::OUT_OF_LIABILITY);
    }
}
