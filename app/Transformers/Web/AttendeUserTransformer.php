<?php

namespace App\Transformers\Web;

use App\Models\User;
use Illuminate\Support\Str;
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
            'department' => $user->departemen->name,
            'position' => $user->position,
            'gender' => $user->gender->name,
            'status' => $user->status,
            'nip' => optional($user)->nip ?? '',
            'group' => optional($user->golongan)->group ?? '',
            'rank' => Str::replaceFirst('Tingkat', 'Tk.', optional($user->golongan)->rank) ?? '',
            'presensi' => $this->presence
        ];
    }
}
