<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;


interface AttendeRepositoryInterface
{
    public function cancel($attendanceId, $reason);
    public function presence(Request $request, $code, $attende);
    public function getByDate($date, $exludeUser = null);
    public function getByYear($date);
    public function getByMonth($date);
    public function getByUserAndCode($userId, $codeId);
    public function getByUserAndYear($userId, $year);
    public function formatUserAttendes($attendes);
}
