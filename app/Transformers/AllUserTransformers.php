<?php

namespace App\Transformers;

use App\Models\User;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class AllUserTransformers extends TransformerAbstract
{
    private $presence;

    public function __construct($presence = null)
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
            'nip' => $user->nip ?? "",
            'name' => $user->name,
            'rank' => Str::replaceFirst('Tingkat', 'Tk.', optional($user->golongan)->rank ?? ''),
            'group' => optional($user->golongan)->group ?? '',
            'status' => $user->status,
            'department' => $user->departemen->name,
            'position' => $user->position,
            'phone' => $user->phone ?? "",
            'email' => $user->email ?? "",
            'presence' => $this->presence ?? []
        ];
    }
}
