<?php

namespace App\Http\Controllers\Api;

use App\Models\PaidLeave;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\LeaveCategory;
use App\Notifications\PaidLeaveApprovedNotification;
use App\Notifications\PaidLeaveCreated;
use App\Notifications\PaidLeaveCreatedNotification;
use App\Notifications\PaidLeaveRejectedNotification;
use App\Transformers\EmployeePaidLeaveTransformer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Transformers\PaidLeaveTransformer;
use App\Transformers\Serializers\CustomSerializer;
use Carbon\Carbon;

class PaidLeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paid_leaves = PaidLeave::where('user_id', $request->user()->id)->latest()->get();
        $paid_leaves = fractal()
            ->collection($paid_leaves, new PaidLeaveTransformer)
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Berhasil', $paid_leaves, 200, []);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->user()->status !== 'PNS') {
            return setJson(false, 'Gagal', [], 403, ['message' => ['Anda tidak berhak mengakses bagian ini!']]);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'category' => 'required',
                'description' => 'required',
                'photo' => 'required',
                'due_date' => 'required',
                'start_date' => 'required',
                'file_name' => 'required',
            ],
            [
                'title.required' => 'Judul tidak boleh kosong!',
                'category.required' => 'Kategori tidak boleh kosong!',
                'description.required' => 'Deskripsi tidak boleh kosong!',
                'photo.required' => 'Foto tidak boleh kosong!',
                'due_date.required' => 'Tanggal selesai tidak boleh kosong!',
                'start_date.required' => 'Tanggal mulai tidak boleh kosong!',
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        $paid_leave = PaidLeave::whereDate('start_date', Carbon::parse($request->start_date))
            ->where('user_id', $request->user()->id)
            ->first();

        if ($paid_leave) {
            return setJson(false, 'Gagal', [], 400, ['tanggal_kadaluarsa' => ['Anda sudah mengajukan cuti tertanggal ' . Carbon::parse($request->start_date)->translatedFormat('l, d F Y')]]);
        }

        $category = LeaveCategory::where('id', $request->category)->first();

        $paid_leaves = PaidLeave::whereYear('start_date', now()->year)
            ->where([
                ['user_id', $request->user()->id],
                ['is_approved', true],
                ['leave_category_id', $request->category]
            ])->get();

        $startDate = Carbon::parse($request->start_date);
        $dueDate = Carbon::parse($request->due_date);
        $totalDay = $startDate->diffInDays($dueDate) + 1;

        if ($paid_leaves->count() > 0) {
            foreach ($paid_leaves as $paid_leave) {
                $startDate = Carbon::parse($paid_leave->start_date);
                $dueDate = Carbon::parse($paid_leave->due_date);
                $diff = $startDate->diffInDays($dueDate) + 1;
                $totalDay += $diff;
            }
        }

        if ($totalDay > $category->limit) {
            return setJson(false, 'Pelanggaran', [], 400, ['tanggal_kadaluarsa' => [$category->name . ' tidak boleh lebih dari ' . $category->limit . ' hari dalam satu tahun.']]);
        }

        $realImage = base64_decode($request->photo);
        $imageName = $request->title . "-" . now()->translatedFormat('l, d F Y') . "-" . $request->file_name;
        $path = "cuti/" . $request->user()->name . "/" . $category->name . '/'  . $imageName;
        Storage::disk('public')->put($path,   $realImage);

        $paid_leave = PaidLeave::create([
            'user_id' => $request->user()->id,
            'leave_category_id' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'photo' => $path,
            'start_date' => Carbon::parse($request->start_date),
            'due_date' => Carbon::parse($request->due_date),
            'is_approved' => true
        ]);

        if ($paid_leave) {
            $request->user()->notify(new PaidLeaveCreatedNotification($paid_leave));
            return setJson(
                true,
                'Berhasil',
                fractal()->item($paid_leave)->transformWith(new PaidLeaveTransformer)->serializeWith(new CustomSerializer)->toArray(),
                201,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PaidLeave  $paid_leave
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses menu ini!']);
        }
        $paid_leaves = PaidLeave::orderBy('created_at', 'desc')->with(['kategori', 'user'])->get();
        $paid_leaves = fractal()->collection($paid_leaves, new EmployeePaidLeaveTransformer)
            ->serializeWith(new CustomSerializer)->toArray();
        return setJson(true, 'Berhasil', $paid_leaves, 200, []);
    }

    /**
     * Approve the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PaidLeave  $paid_leave
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'reason' => 'required_if:is_approved,0',
                'id' => '',
                'user_id' => '',
                'is_approved' => 'required'
            ],
            [
                'reason.required_if' => 'Alasan penolakan harus diisi!'
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        $paid_leave = PaidLeave::where([
            ['id', $request->paid_leave_id],
            ['user_id', $request->user_id],
        ])->with('user')->first();

        $update = $paid_leave->update([
            'is_approved' => $request->is_approved
        ]);

        $notification = $paid_leave->is_approved ?
            new PaidLeaveApprovedNotification($paid_leave) :
            new PaidLeaveRejectedNotification($paid_leave, $request->reason);

        if ($update) {
            $paid_leave->user->notify($notification);
            return setJson(
                true,
                'Sukses mengubah status cuti!',
                fractal()->item($paid_leave, new PaidLeaveTransformer)->serializeWith(new CustomSerializer)->toArray(),
                200,
                []
            );
        }
    }
}
