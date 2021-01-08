<?php

namespace App\Observers;

use App\Models\Attende;
use App\Models\AttendeStatus;
use App\Models\LeaveCategory;
use App\Models\PaidLeave;
use Carbon\Carbon;

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
        $category = LeaveCategory::where('id', $paidLeave->leave_category_id)->first();
        $status = AttendeStatus::where('name', $category->name)->first();
        if (Carbon::parse($paidLeave->start_date)->isToday()) {
            $presences = $paidLeave->user->presensi()->whereDate('created_at', today())->where('attende_status_id', Attende::ABSENT)->get();
            foreach ($presences as $presence) {
                $presence->update([
                    'attende_status_id' => $status
                ]);
            }
        }
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
            $this->updateStatus(Attende::ABSENT, $status->id, $paidLeave);
        } else {
            $this->updateStatus($status->id, Attende::ABSENT, $paidLeave);
        }
    }

    private function updateStatus($from, $to, PaidLeave $paidLeave)
    {

        if (
            Carbon::parse($paidLeave->due_date)->isBefore(today()) ||
            Carbon::parse($paidLeave->start_date)->isBefore(today())
        ) {
            $presences = $paidLeave->user->presensi()
                ->whereDate('created_at', '>=', Carbon::parse($paidLeave->start_date)->toDateString())
                ->whereDate('created_at', '<=',  Carbon::parse($paidLeave->due_date)->toDateString())
                ->where('attende_status_id', $from)->get();
        } else if (Carbon::parse($paidLeave->start_date)->isToday()) {
            $presences = $paidLeave->user->presensi()->today()->where('attende_status_id', $from)->get();
        } else {
            return;
        }
        foreach ($presences as $presence) {
            $presence->update([
                'attende_status_id' => $to
            ]);
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
        //
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
