<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AbsentPermission;
use Illuminate\Support\Facades\Storage;
use App\Notifications\AbsentPermissionCreatedNotification;
use App\Notifications\AbsentPermissionApprovedNotification;
use App\Notifications\AbsentPermissionRejectedNotification;
use App\Repositories\Interfaces\AbsentPermissionRepositoryInterface;

class AbsentPermissionRepository implements AbsentPermissionRepositoryInterface
{

    public function all()
    {
        return AbsentPermission::orderBy('created_at', 'desc')->get();
    }

    public function save(Request $request, $name = null, $id = null)
    {
        $folder = $request->user()->name;
        $userId = $request->user()->id;
        if (!is_null($name)) {
            $folder = $name;
        }
        if (!is_null($id)) {
            $userId = $id;
        }
        $realImage = base64_decode($request->photo);
        $imageName = $request->title . "-" . now()->translatedFormat('l, d F Y') . "-" . $request->file_name;

        Storage::disk('public')->put("izin/" . $folder . "/"   . $imageName,  $realImage);

        $permission =  AbsentPermission::create([
            'user_id' => $userId,
            'title' => $request->title,
            'description' => $request->description,
            'photo' => "izin/" . $folder . "/"   . $imageName,
            'due_date' => Carbon::parse($request->due_date),
            'start_date' => Carbon::parse($request->start_date),
            'is_approved' => false,
            'approval_status_id' => AbsentPermission::PENDING
        ]);

        if ($permission) {
            $request->user()->notify(new AbsentPermissionCreatedNotification($permission));
        }

        return $permission;
    }

    public function approve(Request $request)
    {
        $permission = AbsentPermission::where([
            ['id', $request->permission_id],
            ['user_id', $request->user_id]
        ])
            ->with(['user'])
            ->first();

        $status = $request->is_approved ? AbsentPermission::APPROVED : AbsentPermission::REJECTED;

        $update = $permission->update([
            'is_approved' => $request->is_approved,
            'approval_status_id' => $status
        ]);

        $notification = $permission->is_approved ?
            new AbsentPermissionApprovedNotification($permission) :
            new AbsentPermissionRejectedNotification($permission, $request->reason);

        if ($update) {
            $permission->user->notify($notification);
        }
        return $update;
    }

    public function updatePicture(Request $request)
    {
        $folder = $request->user()->name;

        $permission = AbsentPermission::where([
            ['user_id', $request->user()->id],
            ['id', $request->permission_id]
        ])->first();

        $realImage = base64_decode($request->photo);
        $imageName = $permission->title . "-" . now()->translatedFormat('l, d F Y') . "-" . $request->file_name;
        $path = "izin/" . $folder . "/"   . $imageName;


        if (Storage::exists($permission->photo)) {
            Storage::delete([$permission->photo]);
        }

        Storage::disk('public')->put($path,  $realImage);

        return $permission->update([
            'photo' => $path
        ]);
    }

    public function getByUser($userId)
    {
        return AbsentPermission::where('user_id', $userId)->latest()->get();
    }

    public function getBetweenDate($date)
    {
        $date = Carbon::parse($date);
        return AbsentPermission::with(['user', 'user.departemen', 'status'])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('due_date', '>=', $date)
            ->orderBy('created_at', 'desc')->get();
    }

    public function getByUserAndYear($userId, $year)
    {
        return AbsentPermission::select(['id', 'user_id', 'start_date', 'due_date'])->where([
            ['is_approved', 1],
            ['user_id', $userId]
        ])
            ->whereYear('created_at', $year)
            ->get();
    }

    public function getByUserAndMonth(Request $request)
    {
        $date = Carbon::parse($request->date);
        return AbsentPermission::where('user_id', $request->user()->id)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->latest()->get();
    }

    public function getByUserAndStartDate($userId, $startDate)
    {
        return AbsentPermission::whereDate('start_date', Carbon::parse($startDate))
            ->where('user_id', $userId)
            ->whereIn('approval_status_id', [AbsentPermission::APPROVED, AbsentPermission::PENDING])
            ->get();
    }
}
