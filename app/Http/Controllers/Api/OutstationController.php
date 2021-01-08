<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Outstation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Transformers\OutstationTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Transformers\EmployeeOutstationTransformer;
use App\Notifications\OutstationCreatedNotification;
use App\Notifications\OutstationRejectedNotification;
use App\Notifications\OutstationApprovedNotification;

class OutstationController extends Controller
{
    /**
     * Display a listing of the outstations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $outstations = Outstation::where('user_id', $request->user()->id)->get();
        $outstations = fractal()
            ->collection($outstations, new OutstationTransformer)
            ->serializeWith(new CustomSerializer)->toArray();
        return setJson(true, 'Berhasil', $outstations, 200, []);
    }

    /**
     * Store a newly created outstation in storage.
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
                'title.required' => 'Judul tidak boleh kosong!',
                'description.required' => 'Deskripsi tidak boleh kosong!',
                'photo.required' => 'Foto tidak boleh kosong!',
                'due_date.required' => 'Tanggal selesai tidak boleh kosong!',
                'start_date.required' => 'Tanggal mulai tidak boleh kosong!',
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        $outstation = Outstation::whereDate('start_date', now())->first();

        if ($outstation) {
            return setJson(false, 'Gagal', [], 400, ['tanggal_kadaluarsa' => ['Anda sudah mengajukan dinas luar tertanggal ' . now()->translatedFormat('l, d F Y')]]);
        }

        $realImage = base64_decode($request->photo);
        $imageName = $request->title . "-" . now()->translatedFormat('l, d F Y') . "-" . $request->file_name;

        Storage::disk('public')->put("dinas_luar/" . $request->user()->name . "/"   . $imageName,  $realImage);

        $outstation = Outstation::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'photo' => "dinas_luar/" . $request->user()->name . "/"   . $imageName,
            'due_date' => Carbon::parse($request->due_date),
            'start_date' => Carbon::parse($request->start_date),
            'is_approved' => $request->user()->position === 'Camat' ? true : false
        ]);

        if ($outstation) {
            $request->user()->notify(new OutstationCreatedNotification($outstation));
            sendNotification("Dinas Luar baru diajukan oleh  {$request->user()->name} : \n$request->title", 'Pengajuan Dinas Luar!', 2);
            return setJson(
                true,
                'Berhasil',
                fractal()->item($outstation)
                    ->transformWith(new OutstationTransformer)
                    ->serializeWith(new CustomSerializer)->toArray(),
                201,
                []
            );
        }

        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }

    /**
     * Display all employee outstations.
     *
     * @param  \App\Models\Outstation  $outstation
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $outstations = Outstation::orderBy('created_at', 'desc')->get();
        $outstations = fractal()->collection($outstations, new EmployeeOutstationTransformer)
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Berhasil', $outstations, 200, []);
    }



    /**
     * Approve the specified outstation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Outstation  $outstation
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request)
    {
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $outstation = Outstation::where([
            ['id', $request->outstation_id],
            ['user_id', $request->user_id]
        ])
            ->with(['user'])
            ->first();
        $update = $outstation->update([
            'is_approved' => $request->is_approved
        ]);

        $notification = $outstation->is_approved ?
            new OutstationApprovedNotification($outstation) :
            new OutstationRejectedNotification($outstation);

        if ($update) {
            $outstation->user->notify($notification);
            return setJson(
                true,
                'Sukses mengubah status dinas luar!',
                fractal()->item($outstation)->transformWith(new EmployeeOutstationTransformer)->serializeWith(new CustomSerializer),
                200,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }
}
