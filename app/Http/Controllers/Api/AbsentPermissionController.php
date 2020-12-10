<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AbsentPermission;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Transformers\AbsentPermissionTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Transformers\EmployeePermissionTransformer;
use App\Notifications\AbsentPermissionCreatedNotification;
use App\Notifications\AbsentPermissionApprovedNotification;
use App\Notifications\AbsentPermissionRejectedNotification;

class AbsentPermissionController extends Controller
{
    /**
     * Display a listing of the absent permissions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $permissions = AbsentPermission::where('user_id', $request->user()->id)->get();
        $permissions = fractal()
            ->collection($permissions, new AbsentPermissionTransformer)
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Berhasil', $permissions, 200, []);
    }

    /**
     * Store a newly created absent permission in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'description' => 'required',
                'photo' => 'required',
                'due_date' => 'required',
                'start_date' => 'required',
                'file_name' => 'required',
            ],
            [
                'required' => ':attribute tidak boleh kosong!'
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        if (Carbon::parse($request->due_date) < today()) {
            return setJson(false, 'Gagal', [], 400, ['tanggal_kadaluarsa' => ['Hanya boleh menambahkan surat izin tertanggal hari ini dan setelahnya.']]);
        }

        $realImage = base64_decode($request->photo);
        $imageName = $request->title . "-" . now()->translatedFormat('l, d F Y') . "-" . $request->file_name;

        Storage::disk('public')->put("izin/" . $request->user()->name . "/"   . $imageName,  $realImage);

        $permission = AbsentPermission::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'photo' => "izin/" . $request->user()->name . "/"   . $imageName,
            'due_date' => Carbon::parse($request->due_date),
            'start_date' => Carbon::parse($request->start_date),
            'is_approved' => $request->user()->position === 'Camat' ? true : false
        ]);

        if ($permission) {
            $request->user()->notify(new AbsentPermissionCreatedNotification($permission));
            sendNotification("Izin baru diajukan oleh  {$request->user()->name} : \n$request->title", 'Pengajuan izin!', 2);
            return setJson(
                true,
                'Berhasil',
                fractal()->item($permission)
                    ->transformWith(new AbsentPermissionTransformer)
                    ->serializeWith(new CustomSerializer)->toArray(),
                201,
                []
            );
        }

        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }

    /**
     * Display all employee absent permission.
     *
     * @param  \App\Models\AbsentPermission  $absentPermission
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $permissions = AbsentPermission::orderBy('created_at', 'desc')->get();
        $permissions = fractal()->collection($permissions, new EmployeePermissionTransformer)
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Berhasil', $permissions, 200, []);
    }


    /**
     * Approve the specified absent permission in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AbsentPermission  $absentPermission
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $permission = AbsentPermission::where([
            ['id', $request->permission_id],
            ['user_id', $request->user_id]
        ])->first();
        $update = $permission->update([
            'is_approved' => $request->is_approved
        ]);

        $notification = $permission->is_approved ?
            new AbsentPermissionApprovedNotification($permission) :
            new AbsentPermissionRejectedNotification($permission);

        if ($update) {
            $request->user()->notify($notification);
            return setJson(
                true,
                'Sukses mengubah status izin!',
                fractal()->item($permission)->transformWith(new EmployeePermissionTransformer)->serializeWith(new CustomSerializer),
                200,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }
}
