<?php

namespace App\Transformers;

use App\Models\User;
use App\Transformers\Serializers\CustomSerializer;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{

    protected $token;
    protected $nextPresence;

    public function __construct($token, $nextPresence)
    {
        $this->token = $token;
        $this->nextPresence = $nextPresence;
    }
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'presence',
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
            'id' => $user->id,
            'nip' => $user->nip,
            'name' => $user->name,
            'phone' => $user->phone,
            'gender' => $user->gender->name,
            'department' => $user->departemen->name,
            'status' => $user->status,
            'position' => $user->position,
            'token' => $this->token,
            'next_presence' => $this->nextPresence ? fractal()->item($this->nextPresence)->transformWith(new AttendeTransformers) : []
        ];
    }


    public function includePresence(User $user)
    {
        return $this->collection($user->presensi()->today()->get(), new AttendeTransformers);
    }
}
