<?php

namespace App\Observers;

use App\Models\AbsentPermission;
use App\Models\Attende;
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
        //
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
            $presences = $absentPermission->user->presensi()->whereDate('created_at', today())->where('attende_status_id', Attende::ABSENT)->get();
            foreach ($presences as $presence) {
                $presence->update([
                    'attende_status_id' => Attende::PERMISSION
                ]);
            }
        } else {
            $presences = $absentPermission->user->presensi()->whereDate('created_at', today())->where('attende_status_id', Attende::PERMISSION)->get();
            foreach ($presences as $presence) {
                $presence->update([
                    'attende_status_id' => Attende::ABSENT
                ]);
            }
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
