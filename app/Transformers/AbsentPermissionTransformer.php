<?php

namespace App\Transformers;

use App\Models\AbsentPermission;
use Illuminate\Support\Facades\Storage;
use League\Fractal\TransformerAbstract;

class AbsentPermissionTransformer extends TransformerAbstract
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
    public function transform(AbsentPermission $permission)
    {
        return [
            'id' => $permission->id,
            'title' => $permission->title,
            'description' => $permission->description,
            'is_approved' => $permission->is_approved ? true : false,
            'approval_status' => $permission->status->name,
            'start_date' => $permission->start_date,
            'due_date' => $permission->due_date,
            'photo' => env('MEDIA_URL') . Storage::url($permission->photo),
        ];
    }
}
