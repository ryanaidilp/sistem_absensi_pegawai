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
        })->with(['departemen', 'golongan', 'gender'])->get();
    }

    public function allExcept($exceptId)
    {
        return User::where(function ($query) {
            return $query->pns()->orWhere->honorer();
        })->with(['departemen', 'golongan', 'gender'])->where('id', '!=', $exceptId)->get();
    }

    public function allByBirthday($birthday)
    {
        return User::where(function ($query) {
            return $query->pns()->orWhere->honorer();
        })->whereMonth('date_of_birth', $birthday->format('m'))
            ->whereDay('date_of_birth', $birthday->format('d'))->get();
    }
}
