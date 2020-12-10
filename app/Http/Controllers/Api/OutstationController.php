<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Outstation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\OutstationApprovedNotification;
use App\Notifications\OutstationCreatedNotification;
use App\Notifications\OutstationRejectedNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Transformers\OutstationTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Transformers\EmployeeOutstationTransformer;

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
                'required' => ':attribute tidak boleh kosong!'
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        if (Carbon::parse($request->due_date) < today()) {
            return setJson(false, 'Gagal', [], 400, ['tanggal_kadaluarsa' => ['Hanya boleh menambahkan surat dinas luar tertanggal hari ini atau setelahnya.']]);
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
        ])->first();
        $update = $outstation->update([
            'is_approved' => $request->is_approved
        ]);

        $notification = $outstation->is_approved ?
            new OutstationApprovedNotification($outstation) :
            new OutstationRejectedNotification($outstation);

        if ($update) {
            $request->user()->notify($notification);
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
