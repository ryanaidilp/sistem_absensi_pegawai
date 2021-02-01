<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Outstation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Notifications\OutstationCreatedNotification;
use App\Notifications\OutstationApprovedNotification;
use App\Notifications\OutstationRejectedNotification;
use App\Repositories\Interfaces\OutstationRepositoryInterface;

class OutstationRepository implements OutstationRepositoryInterface
{
    public function all()
    {
        return  Outstation::orderBy('created_at', 'desc')->get();
    }

    public function save(Request $request, $name = null, $id = null)
    {
        $folder = is_null($name) ? $request->user()->name : $name;
        $userId = is_null($id) ? $request->user()->id : $id;
        $realImage = base64_decode($request->photo);
        $imageName = $request->title . "-" . now()->translatedFormat('l, d F Y') . "-" . $request->file_name;

        Storage::disk('public')->put("dinas_luar/" . $folder . "/"   . $imageName,  $realImage);

        $outstation = Outstation::create([
            'user_id' => $userId,
            'title' => $request->title,
            'description' => $request->description,
            'photo' => "dinas_luar/" . $folder . "/"   . $imageName,
            'due_date' => Carbon::parse($request->due_date),
            'start_date' => Carbon::parse($request->start_date),
            'is_approved' => false,
            'approval_status_id' => Outstation::PENDING
        ]);

        if ($outstation && is_null($id)) {
            $request->user()->notify(new OutstationCreatedNotification($outstation));
        }

        return $outstation;
    }

    public function approve(Request $request)
    {
        $outstation = Outstation::where([
            ['id', $request->outstation_id],
            ['user_id', $request->user_id]
        ])
            ->with(['user'])
            ->first();

        $status = $request->is_approved ? Outstation::APPROVED : Outstation::REJECTED;

        $update = $outstation->update([
            'is_approved' => $request->is_approved,
            'approval_status_id' => $status
        ]);

        $notification = $outstation->is_approved ?
            new OutstationApprovedNotification($outstation) :
            new OutstationRejectedNotification($outstation, $request->reason);

        if ($update) {
            $outstation->user->notify($notification);
        }

        return $update;
    }

    public function updatePicture(Request $request)
    {
        $folder = $request->user()->name;

        $outstation = Outstation::where([
            ['user_id', $request->user()->id],
            ['id', $request->outstation_id]
        ])->first();

        $realImage = base64_decode($request->photo);
        $imageName = $outstation->title . "-" . now()->translatedFormat('l, d F Y') . "-" . $request->file_name;
        $path = "dinas_luar/" . $folder . "/"   . $imageName;


        if (Storage::exists($outstation->photo)) {
            Storage::delete([$outstation->photo]);
        }

        Storage::disk('public')->put($path,  $realImage);

        return $outstation->update([
            'photo' => $path
        ]);
    }

    public function getByUser($userId)
    {
        return Outstation::where('user_id', $userId)->latest()->get();
    }

    public function getBetweenDate($date)
    {
        $date = Carbon::parse($date);
        return Outstation::with(['user', 'user.departemen', 'status'])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('due_date', '>=', $date)
            ->orderBy('created_at', 'desc')->get();
    }

    public function getByUserAndMonth(Request $request)
    {
        $date = Carbon::parse($request->date);
        return Outstation::where('user_id', $request->user()->id)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->latest()->get();
    }

    public function getByUserAndYear($userId, $year)
    {
        return Outstation::select(['id', 'user_id', 'start_date', 'due_date'])
            ->where([
                ['is_approved', true],
                ['user_id', $userId]
            ])
            ->whereYear('created_at', $year)
            ->get();
    }

    public function getByUserAndStartDate($userId, $startDate)
    {
        return Outstation::whereDate('start_date', Carbon::parse($startDate))
            ->where('user_id', $userId)
            ->whereIn('approval_status_id', [Outstation::APPROVED, Outstation::PENDING])
            ->first();
    }
}
