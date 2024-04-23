<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Requests\DestroySupplierRequest;
use App\Http\Requests\EditingAtSupplierRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Http\Requests\StoreAclRequest;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SupplierController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $supplier = new Supplier();
        return response([
            'data' => $supplier->get(),
            'attributes' => [
                'totalRows' => $supplier->totalRows,
                'totalPages' => $supplier->totalPages
            ]
        ]);
    }

    public function default()
    {
        $supplier = new Supplier();
        return response([
            'status' => true,
            'data' => $supplier->default(),
        ]);
    }

    public function show($id)
    {
        $supplier = Supplier::findAll($id);
        return response([
            'status' => true,
            'data' => $supplier
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('supplier')->getColumns();
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
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'email' => $request->email,
                'telepon' => $request->telepon,
                'alamat' => $request->alamat,
                'keterangan' => $request->keterangan,
                'karyawanid' => $request->karyawanid,
                'potongan' => $request->potongan,
                'top' => $request->top,
                'status' => $request->status,
            ];
            $supplier = (new Supplier())->processStore($data);
            $supplier->position = $this->getPosition($supplier, $supplier->getTable())->position;
            if ($request->limit == 0) {
                $supplier->page = ceil($supplier->position / (10));
            } else {
                $supplier->page = ceil($supplier->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $supplier
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'email' => $request->email,
                'telepon' => $request->telepon,
                'alamat' => $request->alamat,
                'keterangan' => $request->keterangan,
                'karyawanid' => $request->karyawanid,
                'potongan' => $request->potongan,
                'top' => $request->top,
                'status' => $request->status,
            ];

            $supplier = (new Supplier())->processUpdate($supplier, $data);
            $supplier->position = $this->getPosition($supplier, $supplier->getTable())->position;
            if ($request->limit == 0) {
                $supplier->page = ceil($supplier->position / (10));
            } else {
                $supplier->page = ceil($supplier->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $supplier
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroySupplierRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $supplier = (new Supplier())->processDestroy($id);
            $selected = $this->getPosition($supplier, $supplier->getTable(), true);
            $supplier->position = $selected->position;
            $supplier->id = $selected->id;
            if ($request->limit == 0) {
                $supplier->page = ceil($supplier->position / (10));
            } else {
                $supplier->page = ceil($supplier->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $supplier
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
            $row_range    = range(2, ($row_limit-2));
            $column_range = range('A', $column_limit);
            // $startcount = 4;
            $data = array();

            $a = 0;
            foreach ($row_range as $row) {

                $nama = $sheet->getCell($this->kolomexcel(1) . $row)->getValue();
                $telepon = $sheet->getCell($this->kolomexcel(2) . $row)->getValue();
                $alamat = $sheet->getCell($this->kolomexcel(3) . $row)->getValue();
                $keterangan = $sheet->getCell($this->kolomexcel(4) . $row)->getValue();
                $karyawan = $sheet->getCell($this->kolomexcel(5) . $row)->getValue();
                $top = $sheet->getCell($this->kolomexcel(6) . $row)->getValue();
                $status = $sheet->getCell($this->kolomexcel(7) . $row)->getValue();

                $statusId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $status)
                    ->first()->id ?? 0;

                $topId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $top)
                    ->first()->id ?? 0;

                $karyawanId =  DB::table('karyawan')
                    ->select('id')
                    ->where('nama', $karyawan)
                    ->first()->id ?? 0;

                $data = [
                    'nama' => $nama,
                    'telepon' => $telepon,
                    'alamat' => $alamat,
                    'keterangan' => $keterangan,
                    'karyawanid' => $karyawanId,
                    'potongan' => 0,
                    'top' => $topId,
                    'status' => $statusId,
                    'modifiedby' => auth('api')->user()->name
                ];
                $customer = (new Supplier())->processStore($data);
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

    public function editingat(EditingAtSupplierRequest $request)
    {
       
        $supplier = new Supplier();
        return response([
            'data' => $supplier->editingAt($request->id, $request->btn),
        ]);
    }
}
