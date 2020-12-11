<?php

namespace App\Observers;

use App\Models\Attende;
use App\Models\Outstation;
use Carbon\Carbon;

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
        if (Carbon::parse($outstation->start_date)->isToday()) {
            $presences = $outstation->user->presensi()->today()->absen()->get();
            foreach ($presences as $presence) {
                $presence->update([
                    'attende_status_id' => Attende::OUTSTATION
                ]);
            }
        }
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
            $this->updateStatus(Attende::ABSENT, Attende::OUTSTATION, $outstation);
        } else {
            $this->updateStatus(Attende::OUTSTATION, Attende::ABSENT, $outstation);
        }
    }

    private function updateStatus($from, $to, $outstation)
    {

        if (Carbon::parse($outstation->due_date)->isAfter(today())) {
            return;
        }

        if (Carbon::parse($outstation->due_date)->isBefore(today())) {
            $presences = $outstation->user->presensi()
                ->whereDate('created_at', '>=', Carbon::parse($outstation->start_date))
                ->whereDate('created_at', '<=', Carbon::parse($outstation->due_date))
                ->where('attende_status_id', $from)->get();
        } else {
            $presences = $outstation->user->presensi()->today()->where('attende_status_id', $from)->get();
        }

        foreach ($presences as $presence) {
            $presence->update([
                'attende_status_id' => $to
            ]);
        }
    }
}
