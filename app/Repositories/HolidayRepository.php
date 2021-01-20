<?php

namespace App\Repositories;

use App\Models\Holiday;
use App\Repositories\Interfaces\HolidayRepositoryInterface;

class HolidayRepository implements HolidayRepositoryInterface
{
    public function getByYear($year)
    {
        return Holiday::whereYear('date', $year)->get()->map(function ($holiday) {
            return [
                'date' => $holiday->date,
                'name' => $holiday->name,
                'description' => $holiday->description
            ];
        })->toArray();
    }

    public function getToday()
    {
        return Holiday::whereDate('date', today())->first();
    }
}
