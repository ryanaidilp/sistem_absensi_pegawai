<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\LeaveCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Transformers\PaidLeaveTransformer;
use App\Transformers\EmployeePaidLeaveTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Repositories\Interfaces\PaidLeaveRepositoryInterface;

class PaidLeaveController extends Controller
{
    private $paidLeaveRepository;


    public function __construct(
        PaidLeaveRepositoryInterface $paidLeaveRepository
    ) {
        $this->paidLeaveRepository = $paidLeaveRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paidLeaves = $this->paidLeaveRepository->getByUserAndMonth($request);
        $paidLeaves = fractal()
            ->collection($paidLeaves, new PaidLeaveTransformer)
            ->serializeWith(new CustomSerializer)->toArray();

        return setJson(true, 'Berhasil', $paidLeaves, 200, []);
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
            return setJson(
                false,
                'Gagal',
                [],
                403,
                ['message' => ['Anda tidak berhak mengakses bagian ini!']]
            );
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

        $paidLeave = $this->paidLeaveRepository->getByUserAndStartDate(
            $request->user()->id,
            $request->start_date
        )->first();

        if ($paidLeave) {
            return setJson(
                false,
                'Gagal',
                [],
                400,
                ['tanggal_kadaluarsa' => ['Anda sudah mengajukan cuti tertanggal ' . Carbon::parse($request->start_date)->translatedFormat('l, d F Y')]]
            );
        }

        $category = LeaveCategory::where('id', $request->category)->first();
        $paidLeaves = $this->paidLeaveRepository->getByUserAndCategory(
            $request->user()->id,
            $request->category
        );

        $startDate = Carbon::parse($request->start_date);
        $dueDate = Carbon::parse($request->due_date);
        $totalDay = $startDate->diffInDays($dueDate) + 1;

        if ($paidLeaves->count() > 0) {
            foreach ($paidLeaves as $paidLeave) {
                $startDate = Carbon::parse($paidLeave->start_date);
                $dueDate = Carbon::parse($paidLeave->due_date);
                $diff = $startDate->diffInDays($dueDate) + 1;
                $totalDay += $diff;
            }
        }

        if ($totalDay > $category->limit) {
            return setJson(
                false,
                'Pelanggaran',
                [],
                400,
                [
                    'tanggal_kadaluarsa' => [
                        $category->name . ' tidak boleh lebih dari ' . $category->limit . ' hari dalam satu tahun.'
                    ],
                ]
            );
        }

        $paidLeave = $this->paidLeaveRepository->save($request, $category->name);

        if ($paidLeave) {
            return setJson(
                true,
                'Berhasil',
                fractal()->item($paidLeave)->transformWith(new PaidLeaveTransformer)->serializeWith(new CustomSerializer)->toArray(),
                201,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PaidLeave  $paidLeave
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request)
    {
        $date = $request->has('date') ? $request->date : today();
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses menu ini!']);
        }
        $paidLeaves = $this->paidLeaveRepository->getBetweenDate($date);
        $paidLeaves = fractal()->collection($paidLeaves, new EmployeePaidLeaveTransformer)
            ->serializeWith(new CustomSerializer)->toArray();
        return setJson(true, 'Berhasil', $paidLeaves, 200, []);
    }

    /**
     * Approve the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PaidLeave  $paidLeave
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
                'paid_leave_id' => '',
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

        $update = $this->paidLeaveRepository->approve($request);

        if ($update) {
            return setJson(
                true,
                'Sukses mengubah status cuti!',
                'Berhasil',
                200,
                []
            );
        }
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
                'paid_leave_id' => '',
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

        $update = $this->paidLeaveRepository->updatePicture($request);

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
