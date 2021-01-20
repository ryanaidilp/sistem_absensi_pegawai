<?php

namespace App\Repositories\Interfaces;

interface HolidayRepositoryInterface
{
    public function getByYear($year);
    public function getToday();
}
