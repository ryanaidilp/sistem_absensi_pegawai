<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class AllUserTransformers extends TransformerAbstract
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
    public function transform(User $user)
    {
        return [
            'nip' => $user->nip,
            'name' => $user->name,
            'gender' => $user->gender->name,
            'status' => $user->status,
            'department' => $user->departemen->name,
            'position' => $user->position,
            'phone' => $user->phone,
            'email' => $user->email,
        ];
    }
}
