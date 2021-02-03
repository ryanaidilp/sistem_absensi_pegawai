<?php

namespace App\Transformers\Web;



use Carbon\Carbon;
use App\Models\Attende;
use League\Fractal\TransformerAbstract;

class AllAttendeTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Attende $attende)
    {
        $status = $attende->status_kehadiran->name;
        $date = Carbon::parse($attende->created_at);
        if ($status === 'Terlambat') {
            $status = $attende->status_kehadiran->name;
            $status .= calculateLateTime($attende->kode_absen->start_time, $attende->attend_time, $date);
        }
        return [
            'percentage' => checkAttendancePercentage($attende->attende_status_id),
            'status' => $status,
            'attend_time' => $attende->attend_time == null ? "-" : Carbon::parse($attende->attend_time)->format('H:i')
        ];
        return [
            //
        ];
    }
}
