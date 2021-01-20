<?php

namespace App\Transformers\Web;

use Carbon\Carbon;
use App\Models\AttendeCode;
use League\Fractal\TransformerAbstract;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendeCodeTransformer extends TransformerAbstract
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
    public function transform(AttendeCode $code)
    {
        return [
            'code' => "data:image/svg+xml;base64," . base64_encode(QrCode::size(200)->style('round')->generate($code->code)),
            'start_time' => Carbon::parse($code->start_time)->format('H:i'),
            'end_time' => Carbon::parse($code->end_time)->format('H:i'),
            'type' => $code->tipe->name,
            'date' => Carbon::parse($code->end_time)->translatedFormat('l, d F Y')
        ];
    }
}
