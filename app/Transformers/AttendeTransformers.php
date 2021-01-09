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
            'id' => $attende->id,
            'date' =>  $attende->created_at->format('Y-m-d'),
            'code_type' => $attende->kode_absen->tipe->name,
            'status' => $attende->status_kehadiran->name,
            'attend_time' => $time,
            'start_time' => Carbon::parse($attende->kode_absen->start_time)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::parse($attende->kode_absen->end_time)->format('Y-m-d H:i:s'),
            'location' => [
                'latitude' => (float) $attende->latitude,
                'longitude' => (float) $attende->longitude,
                'address' => $attende->address ?? ""
            ],
            'photo' => $attende->photo ?? ""

        ];
    }
}
