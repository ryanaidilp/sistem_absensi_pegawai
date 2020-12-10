<?php

namespace App\Transformers;

use App\Models\Outstation;
use League\Fractal\TransformerAbstract;

class OutstationTransformer extends TransformerAbstract
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
    public function transform(Outstation $outstation)
    {
        return [
            'title' => $outstation->title,
            'description' => $outstation->description,
            'is_approved' => $outstation->is_approved ? true : false,
            'start_date' => $outstation->start_date,
            'due_date' => $outstation->due_date,
            'photo' => $outstation->photo
        ];
    }
}
