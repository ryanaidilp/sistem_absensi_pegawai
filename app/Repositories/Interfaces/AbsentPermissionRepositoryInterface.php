<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;

interface AbsentPermissionRepositoryInterface
{
    public function all();
    public function save(Request $request, $name = null, $id = null);
    public function approve(Request $request);
    public function getByUser($userId);
    public function getBetweenDate($date);
    public function getByUserAndYear($userId, $year);
    public function getByUserAndStartDate($userId, $startDate);
}
