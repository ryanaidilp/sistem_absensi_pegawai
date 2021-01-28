<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;

interface PaidLeaveRepositoryInterface
{
    public function all();
    public function save(Request $request, $categoryName);
    public function approve(Request $request);
    public function getByUser($userId);
    public function updatePicture(Request $request);
    public function getBetweenDate($date);
    public function getByUserAndYear($userId, $year);
    public function getByUserAndMonth(Request $request);
    public function getByUserAndCategory($userId, $categoryId);
    public function getByUserAndStartDate($userId, $startDate);
}
