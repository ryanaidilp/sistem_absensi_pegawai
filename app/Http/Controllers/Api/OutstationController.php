<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Transformers\OutstationTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Transformers\EmployeeOutstationTransformer;
use App\Repositories\Interfaces\OutstationRepositoryInterface;

class OutstationController extends Controller
{
    private $outstationRepository;

    public function __construct(OutstationRepositoryInterface $outstationRepository)
    {
        $this->outstationRepository = $outstationRepository;
    }
    /**
     * Display a listing of the outstations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $outstations = $this->outstationRepository->getByUserAndMonth($request);
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

        $outstation = $this->outstationRepository->getByUserAndStartDate($request->user()->id, $request->start_date);

        if (!is_null($outstation)) {
            return setJson(false, 'Gagal', [], 400, ['tanggal_kadaluarsa' => ['Anda sudah mengajukan dinas luar tertanggal ' . now()->translatedFormat('l, d F Y')]]);
        }

        $outstation = $this->outstationRepository->save($request);

        if ($outstation) {
            return setJson(
                true,
                'Berhasil',
                'Berhasil mengajukan Dinas Luar',
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
        $date = $request->has('date') ? $request->date : today();
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $outstations = $this->outstationRepository->getBetweenDate($date);
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

        $update = $this->outstationRepository->approve($request);

        if ($update) {
            return setJson(
                true,
                'Sukses mengubah status dinas luar!',
                'Berhasil',
                200,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }

    /**
     * Update photo the specified outstation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePicture(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'outstation_id' => '',
                'photo' => 'required',
                'file_name' => 'required'
            ],
            [
                'photo.required' => 'Foto tidak berbeda dari foto sebelumnya!',
                'file_name.required' => 'Nama file baru tidak terdeteksi!'
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        $update = $this->outstationRepository->updatePicture($request);

        if ($update) {
            return setJson(
                true,
                'Sukses mengubah gambar dinas luar!',
                'Berhasil',
                200,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }
}
