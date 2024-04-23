<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupProductRequest;
use App\Http\Requests\UpdateGroupProductRequest;
use App\Http\Requests\DestroyGroupProductRequest;
use App\Http\Requests\EditingAtGroupProductRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Models\GroupProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;

class GroupProductController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $groupProduct = new GroupProduct();
        return response([
            'data' => $groupProduct->get(),
            'attributes' => [
                'totalRows' => $groupProduct->totalRows,
                'totalPages' => $groupProduct->totalPages
            ]
        ]);
    }

    public function default()
    {
        $groupProduct = new GroupProduct();
        return response([
            'status' => true,
            'data' => $groupProduct->default(),
        ]);
    }

    public function show($id)
    {
        $groupProduct = GroupProduct::findAll($id);
        return response([
            'status' => true,
            'data' => $groupProduct
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('groupproduct')->getColumns();
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
    public function store(StoreGroupProductRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];
            
            $groupProduct = (new GroupProduct())->processStore($data);
            $groupProduct->position = $this->getPosition($groupProduct, $groupProduct->getTable())->position;
            if ($request->limit==0) {
                $groupProduct->page = ceil($groupProduct->position / (10));
            } else {
                $groupProduct->page = ceil($groupProduct->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $groupProduct
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateGroupProductRequest $request, GroupProduct $groupProduct)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];

            $groupProduct = (new GroupProduct())->processUpdate($groupProduct, $data);
            $groupProduct->position = $this->getPosition($groupProduct, $groupProduct->getTable())->position;
            if ($request->limit==0) {
                $groupProduct->page = ceil($groupProduct->position / (10));
            } else {
                $groupProduct->page = ceil($groupProduct->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $groupProduct
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyGroupProductRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $groupProduct = (new GroupProduct())->processDestroy($id);
            $selected = $this->getPosition($groupProduct, $groupProduct->getTable(), true);
            $groupProduct->position = $selected->position;
            $groupProduct->id = $selected->id;
            if ($request->limit==0) {
                $groupProduct->page = ceil($groupProduct->position / (10));
            } else {
                $groupProduct->page = ceil($groupProduct->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $groupProduct
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
                $customer = (new GroupProduct())->processStore($data);
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

    public function editingat(EditingAtGroupProductRequest $request)
    {
       
        $groupProduct = new GroupProduct();
        return response([
            'data' => $groupProduct->editingAt($request->id, $request->btn),
        ]);
    }
}
