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
            'id' => $user->id,
            'nip' => $user->nip ?? "",
            'rank' => Str::replaceFirst('Tingkat', 'Tk.', optional($user->golongan)->rank ?? ''),
            'group' => optional($user->golongan)->group ?? '',
            'name' => $user->name,
            'phone' => $user->phone ?? "",
            'gender' => $user->gender->name,
            'department' => $user->departemen->name,
            'status' => $user->status,
            'position' => $user->position,
            'is_weekend' => today()->isWeekend(),
            'presence' => $this->presence ?? []
        ];
    }
}
