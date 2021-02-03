<?php

namespace App\Transformers;

use App\Models\User;
use App\Models\Holiday;
use Illuminate\Support\Str;
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
        $holiday = Holiday::whereDate('date', today())->first();
        if ($holiday !== null) {
            $holiday = [
                'name' => optional($holiday)->name,
                'date' => optional($holiday)->date,
                'description' => optional($holiday)->description,
            ];
        }
        return [
            'id' => $user->id,
            'nip' => $user->nip ?? '',
            'rank' => Str::replaceFirst('Tingkat', 'Tk.', optional($user->golongan)->rank ?? ''),
            'group' => optional($user->golongan)->group ?? '',
            'name' => $user->name,
            'phone' => $user->phone,
            'gender' => $user->gender->name,
            'department' => $user->departemen->name,
            'holiday' => $holiday,
            'is_weekend' => today()->isWeekend(),
            'status' => $user->status,
            'position' => $user->position,
            'unread_notifications' => $user->unreadNotifications->count(),
            'token' => $this->token,
            'next_presence' => $this->nextPresence ? fractal()->item($this->nextPresence)->transformWith(new AttendeTransformers) : null
        ];
    }


    public function includePresence(User $user)
    {
        return $this->collection($user->presensi()->today()->get(), new AttendeTransformers);
    }
}
