<?php

namespace App\Observers;

use App\Models\Attende;
use App\Models\Outstation;

class OutstationObserver
{
    /**
     * Handle the Outstation "created" event.
     *
     * @param  \App\Models\Outstation  $outstation
     * @return void
     */
    public function created(Outstation $outstation)
    {
        // 
    }

    /**
     * Handle the Outstation "updated" event.
     *
     * @param  \App\Models\Outstation  $outstation
     * @return void
     */
    public function updated(Outstation $outstation)
    {
        if ($outstation->is_approved) {
            updateStatus(Attende::ABSENT, Attende::OUTSTATION, $outstation);
        } else {
            updateStatus(Attende::OUTSTATION, Attende::ABSENT, $outstation);
        }
    }


    /**
     * Handle the Outstation "deleted" event.
     *
     * @param  \App\Models\Outstation  $outstation
     * @return void
     */
    public function deleted(Outstation $outstation)
    {
        if ($outstation->is_approved) {
            updateStatus(Attende::OUTSTATION, Attende::ABSENT, $outstation);
        }
    }
}
