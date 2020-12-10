<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\Attende;
use App\Models\AbsentPermission;
use Illuminate\Support\Facades\Log;

class AbsentPermissionObserver
{
    /**
     * Handle the AbsentPermission "created" event.
     *
     * @param  \App\Models\AbsentPermission  $absentPermission
     * @return void
     */
    public function created(AbsentPermission $absentPermission)
    {
        if (Carbon::parse($absentPermission->start_date)->isToday()) {
            $presences = $absentPermission->user->presensi()->whereDate('created_at', today())->where('attende_status_id', Attende::ABSENT)->get();
            foreach ($presences as $presence) {
                $presence->update([
                    'attende_status_id' => Attende::PERMISSION
                ]);
            }
        }
    }

    /**
     * Handle the AbsentPermission "updated" event.
     *
     * @param  \App\Models\AbsentPermission  $absentPermission
     * @return void
     */
    public function updated(AbsentPermission $absentPermission)
    {
        if ($absentPermission->is_approved) {
            $this->updateStatus(Attende::ABSENT, Attende::PERMISSION, $absentPermission);
        } else {
            $this->updateStatus(Attende::PERMISSION, Attende::ABSENT, $absentPermission);
        }
    }

    private function updateStatus($from, $to, AbsentPermission $absentPermission)
    {
        if (Carbon::parse($absentPermission->due_date) < today()) {
            $presences = $absentPermission->user->presensi()
                ->whereDate('created_at', '>=', Carbon::parse($absentPermission->start_date)->toDateString())
                ->whereDate('created_at', '<=',  Carbon::parse($absentPermission->due_date)->toDateString())
                ->where('attende_status_id', $from)->get();
        } else {
            $presences = $absentPermission->user->presensi()->whereDate('created_at', today())->where('attende_status_id', $from)->get();
        }
        foreach ($presences as $presence) {
            $presence->update([
                'attende_status_id' => $to
            ]);
        }
    }

    /**
     * Handle the AbsentPermission "deleted" event.
     *
     * @param  \App\Models\AbsentPermission  $absentPermission
     * @return void
     */
    public function deleted(AbsentPermission $absentPermission)
    {
        //
    }

    /**
     * Handle the AbsentPermission "restored" event.
     *
     * @param  \App\Models\AbsentPermission  $absentPermission
     * @return void
     */
    public function restored(AbsentPermission $absentPermission)
    {
        //
    }

    /**
     * Handle the AbsentPermission "force deleted" event.
     *
     * @param  \App\Models\AbsentPermission  $absentPermission
     * @return void
     */
    public function forceDeleted(AbsentPermission $absentPermission)
    {
        //
    }
}
