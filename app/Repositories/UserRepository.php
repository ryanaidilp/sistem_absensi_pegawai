<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function getByPhone($phone)
    {
        return User::where('phone', wordwrap($phone, 4, " ", true))->with(['presensi', 'dinas_luar', 'izin', 'departemen', 'gender', 'golongan'])->first();
    }

    public function all()
    {
        return User::where(function ($query) {
            return $query->pns()->orWhere->honorer();
        })->with(['departemen', 'golongan'])->get();
    }

    public function allExcept($exceptId)
    {
        return User::where(function ($query) {
            return $query->pns()->orWhere->honorer();
        })->with(['departemen', 'golongan'])->where('id', '!=', $exceptId)->get();
    }
}
