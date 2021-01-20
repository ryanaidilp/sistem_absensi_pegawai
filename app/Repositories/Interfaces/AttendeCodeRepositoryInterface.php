<?php

namespace App\Repositories\Interfaces;


interface AttendeCodeRepositoryInterface
{
    public function getByCode($code);
    public function getToday();
}
