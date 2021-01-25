<?php

namespace App\Transformers;

use App\Models\PaidLeave;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class PaidLeaveTransformer extends TransformerAbstract
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
    public function transform(PaidLeave $paidLeave)
    {
        return [
            'id' => $paidLeave->id,
            'category' => $paidLeave->kategori->name,
            'title' => $paidLeave->title,
            'description' => $paidLeave->description,
            'is_approved' => $paidLeave->is_approved ? true : false,
            'approval_status' => $paidLeave->status->name,
            'start_date' => $paidLeave->start_date,
            'due_date' => $paidLeave->due_date,
            'photo' => env('MEDIA_URL') . Storage::url($paidLeave->photo),
        ];
    }
}
