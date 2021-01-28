<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Transformers\AbsentPermissionTransformer;
use App\Transformers\Serializers\CustomSerializer;
use App\Transformers\EmployeePermissionTransformer;
use App\Repositories\Interfaces\AbsentPermissionRepositoryInterface;

class AbsentPermissionController extends Controller
{

    private $absentPermissionRepository;


    public function __construct(AbsentPermissionRepositoryInterface $absentPermissionRepository)
    {
        $this->absentPermissionRepository = $absentPermissionRepository;
    }
    /**
     * Display a listing of the absent permissions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $permissions = $this->absentPermissionRepository->getByUserAndMonth($request);
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

        $permission = $this->absentPermissionRepository->getByUserAndStartDate(
            $request->user()->id,
            $request->start_date
        )
            ->first();

        if ($permission) {
            return setJson(false, 'Gagal', [], 400, ['tanggal_kadaluarsa' => ['Anda sudah mengajukan izin tertanggal ' . now()->translatedFormat('l, d F Y')]]);
        }

        $permission = $this->absentPermissionRepository->save($request);

        if ($permission) {
            return setJson(
                true,
                'Berhasil mengajukan izin!',
                'Berhasil!',
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
        $date = $request->has('date') ? $request->date : today();
        if ($request->user()->position !== 'Camat') {
            return setJson(false, 'Pelanggaran', [], 403, ['message' => 'Anda tidak memiliki izin untuk mengakses bagian ini!']);
        }

        $permissions = $this->absentPermissionRepository->getBetweenDate($date);
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

        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => '',
                'permission_id' => '',
                'is_approved' => 'required',
                'reason' => 'required_if:is_approved,0'
            ],
            [
                'reason.required_if' => 'Alasan penolakan harus diisi!'
            ]
        );

        if ($validator->fails()) {
            return setJson(false, 'Gagal', [], 400, $validator->errors());
        }

        $update = $this->absentPermissionRepository->approve($request);

        if ($update) {
            return setJson(
                true,
                'Sukses mengubah status izin!',
                'Berhasil',
                200,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }

    /**
     * Approve the specified absent permission in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AbsentPermission  $absentPermission
     * @return \Illuminate\Http\Response
     */
    public function updatePicture(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'permission_id' => '',
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

        $update = $this->absentPermissionRepository->updatePicture($request);

        if ($update) {
            return setJson(
                true,
                'Sukses mengubah gambar izin!',
                'Berhasil',
                200,
                []
            );
        }
        return setJson(false, 'Gagal', [], 400, ['message' => ['Kesalahan tidak diketahui!']]);
    }
}
