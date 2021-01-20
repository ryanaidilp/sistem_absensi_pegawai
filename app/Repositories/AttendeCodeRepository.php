<?php

namespace App\Repositories;

use App\Models\AttendeCode;
use App\Repositories\Interfaces\AttendeCodeRepositoryInterface;

class AttendeCodeRepository implements AttendeCodeRepositoryInterface
{
    public function getByCode($code)
    {
        return AttendeCode::where('code', $code)->first();
    }

    public function getToday()
    {
        return AttendeCode::with(['tipe'])->whereDate('created_at', today())->get();
    }
}
