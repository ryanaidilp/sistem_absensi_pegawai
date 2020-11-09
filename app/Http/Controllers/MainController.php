<?php

namespace App\Http\Controllers;

use App\Models\AttendeCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MainController extends Controller
{
    public function index()
    {
        $attendeCode = AttendeCode::with(['tipe'])->whereDate('created_at', today())->get();
        $absent = null;
        $weekend = today()->isWeekend();
        if (!$weekend) {
            $day = today()->isFriday() ? 3 : 1;
            $deadline = $this->setTimer($day, $attendeCode);
        } else {
            $days = [
                'Sabtu' => 2,
                'Minggu' => 1,
            ][today()->dayName];
            $deadline = Carbon::parse('07:30')->addDays($days);
        }
        foreach ($attendeCode as $code) {

            if (
                Carbon::parse($code->start_time) <= now()
                &&
                Carbon::parse($code->end_time) >= now()
            ) {
                $deadline = Carbon::parse($code->end_time);
                $absent = [
                    'code' => "data:image/svg+xml;base64," . base64_encode(QrCode::size(200)->style('round')->generate($code->code)),
                    'start_time' => Carbon::parse($code->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($code->end_time)->format('H:i'),
                    'type' => $code->tipe->name,
                    'date' => Carbon::parse($code->end_time)->translatedFormat('l, d F Y')
                ];
                break;
            }
        }



        return Inertia::render('Home/Index', [
            'code' => $absent,
            'weekend' => $weekend,
            'deadline' => $deadline
        ]);
    }

    public function export()
    {
        $pns = User::with(['presensi', 'departemen'])->pns()->get();
        $honorer = User::with(['presensi', 'departemen'])->honorer()->get();
        return Inertia::render('Table/Index', [
            'pns' => $pns->map(function ($pegawai) {
                return $pegawai->format();
            }),
            'honorer' => $honorer->map(function ($pegawai) {
                return $pegawai->format();
            }),
            'date' => today()->translatedFormat("l, d F Y")
        ]);
    }

    private function setTimer($days, $attendeCode)
    {
        if (now()->format('H') < 6) {
            $deadline = Carbon::parse($attendeCode[0]->start_time);
        } else if (now()->format('H') < 12) {
            $deadline = Carbon::parse($attendeCode[1]->start_time);
        } else if (now()->format('H') < 18) {
            $deadline = Carbon::parse($attendeCode[3]->start_time);
        } else {
            $deadline = Carbon::parse("07:30")->addDays($days);
        }
        return $deadline;
    }
}
