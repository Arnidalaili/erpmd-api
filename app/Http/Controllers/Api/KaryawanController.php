<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKaryawanRequest;
use App\Http\Requests\UpdateKaryawanRequest;
use App\Http\Requests\DestroyKaryawanRequest;
use App\Http\Requests\EditingAtKaryawanRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Http\Requests\StoreAclRequest;
use App\Models\Karyawan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KaryawanController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $karyawan = new Karyawan();
        return response([
            'data' => $karyawan->get(),
            'attributes' => [
                'totalRows' => $karyawan->totalRows,
                'totalPages' => $karyawan->totalPages
            ]
        ]);
    }

    public function default()
    {
        $karyawan = new Karyawan();
        return response([
            'status' => true,
            'data' => $karyawan->default(),
        ]);
    }

    public function show($id)
    {
        $karyawan = Karyawan::findAll($id);
        return response([
            'status' => true,
            'data' => $karyawan
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('karyawan')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }
        return response([
            'data' => $data
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(StoreKaryawanRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'nama2' => $request->nama2,
                'username' => $request->username,
                'email' => $request->email,
                'telepon' => $request->telepon,
                'alamat' => $request->alamat,
                'keterangan' => $request->keterangan,
                'armadaid' => $request->armadaid,
                'status' => $request->status,
            ];
            
            $karyawan = (new Karyawan())->processStore($data);
            $karyawan->position = $this->getPosition($karyawan, $karyawan->getTable())->position;
            if ($request->limit==0) {
                $karyawan->page = ceil($karyawan->position / (10));
            } else {
                $karyawan->page = ceil($karyawan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $karyawan
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateKaryawanRequest $request, Karyawan $karyawan)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'nama2' => $request->nama2,
                'username' => $request->username,
                'email' => $request->email,
                'telepon' => $request->telepon,
                'alamat' => $request->alamat,
                'keterangan' => $request->keterangan,
                'armadaid' => $request->armadaid,
                'status' => $request->status,
            ];

            $karyawan = (new Karyawan())->processUpdate($karyawan, $data);
            $karyawan->position = $this->getPosition($karyawan, $karyawan->getTable())->position;
            if ($request->limit==0) {
                $karyawan->page = ceil($karyawan->position / (10));
            } else {
                $karyawan->page = ceil($karyawan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $karyawan
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyKaryawanRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $karyawan = (new Karyawan())->processDestroy($id);
            $selected = $this->getPosition($karyawan, $karyawan->getTable(), true);
            $karyawan->position = $selected->position;
            $karyawan->id = $selected->id;
            if ($request->limit==0) {
                $karyawan->page = ceil($karyawan->position / (10));
            } else {
                $karyawan->page = ceil($karyawan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $karyawan
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function import(Request $request)
    {
        $request->validate(
            [
                'fileImport' => 'required|file|mimes:xls,xlsx'
            ],
            [
                'fileImport.mimes' => 'file import ' . app(ErrorController::class)->geterror('FXLS')->keterangan,
            ]
        );

        $the_file = $request->file('fileImport');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->getActiveSheet();
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range(2, ($row_limit));
            $column_range = range('A', $column_limit);
            // $startcount = 4;
            $data = array();

            $a = 0;
            foreach ($row_range as $row) {

                $nama = $sheet->getCell($this->kolomexcel(1) . $row)->getValue();
                $nama2 = $sheet->getCell($this->kolomexcel(2) . $row)->getValue();
                $telepon = $sheet->getCell($this->kolomexcel(3) . $row)->getValue();
                $alamat = $sheet->getCell($this->kolomexcel(4) . $row)->getValue();
                $keterangan = $sheet->getCell($this->kolomexcel(5) . $row)->getValue();
                $armada = $sheet->getCell($this->kolomexcel(6) . $row)->getValue();
                $status = $sheet->getCell($this->kolomexcel(7) . $row)->getValue();

                $statusId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $status)
                    ->first()->id ?? 0;

                $armadaId =  DB::table('armada')
                    ->select('id')
                    ->where('nama', $armada)
                    ->first()->id ?? 0;

                $data = [
                    'nama' => $nama,
                    'nama2' => $nama2,
                    'telepon' => $telepon,
                    'alamat' => $alamat,
                    'keterangan' => $keterangan,
                    'armadaid' => $armadaId,
                    'status' => $statusId,
                    'modifiedby' => auth('api')->user()->name
                ];
                $customer = (new Karyawan())->processStore($data);
            }
            return response([
                'status' => true,
                'keterangan' => 'data di update',
                'data' => $customer,

            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    private function kolomexcel($kolom)
    {
        if ($kolom >= 27 and $kolom <= 52) {
            $hasil = 'A' . chr(38 + $kolom);
        } else {
            $hasil = chr(64 + $kolom);
        }
        return $hasil;
    }

    public function editingat(EditingAtKaryawanRequest $request)
    {
        $karyawan = new Karyawan();
        return response([
            'data' => $karyawan->editingAt($request->id, $request->btn),
        ]);
    }
}
