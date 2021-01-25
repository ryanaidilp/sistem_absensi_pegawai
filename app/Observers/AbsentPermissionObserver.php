<?php

namespace App\Observers;

use App\Models\Attende;
use App\Models\AbsentPermission;

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
            updateStatus(Attende::ABSENT, Attende::PERMISSION, $absentPermission);
        } else {
            updateStatus(Attende::PERMISSION, Attende::ABSENT, $absentPermission);
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
        if ($absentPermission->is_approved) {
            updateStatus(Attende::PERMISSION, Attende::ABSENT, $absentPermission);
        }
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
