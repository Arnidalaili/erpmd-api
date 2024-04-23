<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSatuanRequest;
use App\Http\Requests\UpdateSatuanRequest;
use App\Http\Requests\DestroySatuanRequest;
use App\Http\Requests\EditingAtSatuanRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Models\Satuan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $satuan = new Satuan();
        return response([
            'data' => $satuan->get(),
            'attributes' => [
                'totalRows' => $satuan->totalRows,
                'totalPages' => $satuan->totalPages
            ]
        ]);
    }

    public function default()
    {
        $satuan = new Satuan();
        return response([
            'status' => true,
            'data' => $satuan->default(),
        ]);
    }

    public function show($id)
    {
        $satuan = Satuan::findAll($id);
        return response([
            'status' => true,
            'data' => $satuan
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('satuan')->getColumns();
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
    public function store(StoreSatuanRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];
            
            $satuan = (new Satuan())->processStore($data);
            $satuan->position = $this->getPosition($satuan, $satuan->getTable())->position;
            if ($request->limit==0) {
                $satuan->page = ceil($satuan->position / (10));
            } else {
                $satuan->page = ceil($satuan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $satuan
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateSatuanRequest $request, Satuan $satuan)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];

            $satuan = (new Satuan())->processUpdate($satuan, $data);
            $satuan->position = $this->getPosition($satuan, $satuan->getTable())->position;
            if ($request->limit==0) {
                $satuan->page = ceil($satuan->position / (10));
            } else {
                $satuan->page = ceil($satuan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $satuan
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroySatuanRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $satuan = (new Satuan())->processDestroy($id);
            $selected = $this->getPosition($satuan, $satuan->getTable(), true);
            $satuan->position = $selected->position;
            $satuan->id = $selected->id;
            if ($request->limit==0) {
                $satuan->page = ceil($satuan->position / (10));
            } else {
                $satuan->page = ceil($satuan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $satuan
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
                $keterangan = $sheet->getCell($this->kolomexcel(2) . $row)->getValue();
                $status = $sheet->getCell($this->kolomexcel(3) . $row)->getValue();

                $statusId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $status)
                    ->first()->id ?? 0;

                $data = [
                    'nama' => $nama,
                    'keterangan' => $keterangan,
                    'status' => $statusId,
                    'modifiedby' => auth('api')->user()->name
                ];
                $customer = (new Satuan())->processStore($data);
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

    public function editingat(EditingAtSatuanRequest $request)
    {
        $satuan = new Satuan();
        return response([
            'data' => $satuan->editingAt($request->id, $request->btn),
        ]);
    }
}
