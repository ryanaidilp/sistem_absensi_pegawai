<?php

namespace App\Transformers\Web;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class AttendeUserTransformer extends TransformerAbstract
{
    private $presence;

    public function __construct($presence)
    {
        $this->presence = $presence;
    }
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
            'name' => $user->name,
            'status' => $user->status,
            'nip' => $user->nip,
            'gender' => $user->gender->name,
            'department' => $user->departemen->name,
            'position' => $user->position,
            'presensi' => $this->presence
        ];
    }
}
