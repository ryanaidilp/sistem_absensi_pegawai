<?php

namespace App\Transformers;

use App\Models\Outstation;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class EmployeeOutstationTransformer extends TransformerAbstract
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
            'id' => $outstation->id,
            'title' => $outstation->title,
            'description' => $outstation->description,
            'is_approved' => $outstation->is_approved ? true : false,
            'approval_status' => $outstation->status->name,
            'start_date' => $outstation->start_date,
            'due_date' => $outstation->due_date,
            'photo' => env('MEDIA_URL') . Storage::url($outstation->photo),
            'user' => [
                'id' => $outstation->user->id,
                'nip' => $outstation->user->nip,
                'name' => $outstation->user->name,
                'phone' => $outstation->user->phone,
                'gender' => $outstation->user->gender->name,
                'department' => $outstation->user->departemen->name,
                'status' => $outstation->user->status,
                'position' => $outstation->user->position,
            ]
        ];
    }
}
