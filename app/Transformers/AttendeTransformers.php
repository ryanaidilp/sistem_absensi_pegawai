<?php

namespace App\Transformers;

use App\Models\Attende;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class AttendeTransformers extends TransformerAbstract
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
        $time = !is_null($attende->attend_time) ? Carbon::parse($attende->attend_time)->format('H:i') : "";
        return [
            'date' =>  $attende->created_at->format('d-m-Y'),
            'code_type' => $attende->kode_absen->tipe->name,
            'status' => $attende->status_kehadiran->name,
            'attend_time' => $time,
            'location' => [
                'latitude' => $attende->latitude,
                'longitude' => $attende->longitude,
            ],
            'photo' => $attende->photo

        ];
    }
}
