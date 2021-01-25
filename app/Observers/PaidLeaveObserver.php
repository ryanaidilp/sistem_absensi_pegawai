<?php

namespace App\Observers;

use App\Models\Attende;
use App\Models\PaidLeave;
use App\Models\AttendeStatus;
use App\Models\LeaveCategory;

class PaidLeaveObserver
{
    /**
     * Handle the PaidLeave "created" event.
     *
     * @param  \App\Models\PaidLeave  $paidLeave
     * @return void
     */
    public function created(PaidLeave $paidLeave)
    {
        // 
    }

    /**
     * Handle the PaidLeave "updated" event.
     *
     * @param  \App\Models\PaidLeave  $paidLeave
     * @return void
     */
    public function updated(PaidLeave $paidLeave)
    {
        $category = LeaveCategory::where('id', $paidLeave->leave_category_id)->first();
        $status = AttendeStatus::where('name', $category->name)->first();
        if ($paidLeave->is_approved) {
            updateStatus(Attende::ABSENT, $status->id, $paidLeave);
        } else {
            updateStatus($status->id, Attende::ABSENT, $paidLeave);
        }
    }

    /**
     * Handle the PaidLeave "deleted" event.
     *
     * @param  \App\Models\PaidLeave  $paidLeave
     * @return void
     */
    public function deleted(PaidLeave $paidLeave)
    {
        $category = LeaveCategory::where('id', $paidLeave->leave_category_id)->first();
        $status = AttendeStatus::where('name', $category->name)->first();
        if ($paidLeave->is_approved) {
            updateStatus($status->id, Attende::ABSENT, $paidLeave);
        }
    }

    /**
     * Handle the PaidLeave "restored" event.
     *
     * @param  \App\Models\PaidLeave  $paidLeave
     * @return void
     */
    public function restored(PaidLeave $paidLeave)
    {
        //
    }

    /**
     * Handle the PaidLeave "force deleted" event.
     *
     * @param  \App\Models\PaidLeave  $paidLeave
     * @return void
     */
    public function forceDeleted(PaidLeave $paidLeave)
    {
        //
    }
}
