<?php

namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
    public function all();
    public function getByPhone($phone);
    public function allExcept($exceptId);
    public function allByBirthday($birthday);
}
