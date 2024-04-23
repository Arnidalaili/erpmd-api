<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\DestroyProductRequest;
use App\Http\Requests\EditAllRequest;
use App\Http\Requests\EditingAtProductRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Http\Requests\StoreAclRequest;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    /**
     * @ClassName 
     */
    public function index(Request $request)
    {
        $produk = new Product();
        return response([
            'data' => $produk->get(),
            'attributes' => [
                'totalRows' => $produk->totalRows,
                'totalPages' => $produk->totalPages
            ]
        ]);
    }

    public function getproductall(){
        $produk = new Product();
        return response([
            'detail' => $produk->getAllProduct(),
            'attributes' => [
                'totalRows' => $produk->totalRows,
                'totalPages' => $produk->totalPages
            ]
        ]);
    }

    public function default()
    {
        $product = new Product();
        return response([
            'status' => true,
            'data' => $product->default(),
        ]);
    }

    public function show($id)
    {
        $product = Product::findAll($id);
        return response([
            'status' => true,
            'data' => $product
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('product')->getColumns();
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
    public function store(StoreProductRequest $request): JsonResponse
    {

        DB::beginTransaction();
        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                // 'groupid' => $request->groupid,
                'supplierid' => $request->supplierid,
                'satuanid' => $request->satuanid,
                'keterangan' => $request->keterangan,
                'hargajual' => $request->hargajual,
                'hargabeli' => $request->hargabeli,
                'hargakontrak1' => $request->hargakontrak1,
                'hargakontrak2' => $request->hargakontrak2,
                'hargakontrak3' => $request->hargakontrak3,
                'hargakontrak4' => $request->hargakontrak4,
                'hargakontrak5' => $request->hargakontrak5,
                'hargakontrak6' => $request->hargakontrak6,
                'status' => $request->status,
            ];
            $product = (new Product())->processStore($data);
            $product->position = $this->getPosition($product, $product->getTable())->position;
            if ($request->limit == 0) {
                $product->page = ceil($product->position / (10));
            } else {
                $product->page = ceil($product->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $product
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        } 
    }

    /**
     * @ClassName 
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                // 'groupid' => $request->groupid,
                'supplierid' => $request->supplierid,
                'satuanid' => $request->satuanid,
                'keterangan' => $request->keterangan,
                'hargajual' => $request->hargajual,
                'hargabeli' => $request->hargabeli,
                'hargakontrak1' => $request->hargakontrak1,
                'hargakontrak2' => $request->hargakontrak2,
                'hargakontrak3' => $request->hargakontrak3,
                'hargakontrak4' => $request->hargakontrak4,
                'hargakontrak5' => $request->hargakontrak5,
                'hargakontrak6' => $request->hargakontrak6,
                'status' => $request->status,
            ];

            $product = (new Product())->processUpdate($product, $data);
            $product->position = $this->getPosition($product, $product->getTable())->position;
            if ($request->limit == 0) {
                $product->page = ceil($product->position / (10));
            } else {
                $product->page = ceil($product->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $product
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyProductRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $product = (new Product())->processDestroy($id);
            $selected = $this->getPosition($product, $product->getTable(), true);
            $product->position = $selected->position;
            $product->id = $selected->id;
            if ($request->limit == 0) {
                $product->page = ceil($product->position / (10));
            } else {
                $product->page = ceil($product->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $product
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function export(){
       return true;
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
                $group = $sheet->getCell($this->kolomexcel(2) . $row)->getValue();
                $supplier = $sheet->getCell($this->kolomexcel(3) . $row)->getValue();
                $satuan = $sheet->getCell($this->kolomexcel(4) . $row)->getValue();
                $hargabeli = $sheet->getCell($this->kolomexcel(5) . $row)->getValue();
                $hargajual = $sheet->getCell($this->kolomexcel(6) . $row)->getValue();
                $status = $sheet->getCell($this->kolomexcel(7) . $row)->getValue();
                // dd($supplier);

                $statusId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $status)
                    ->first()->id ?? 0;

                $supplierId =  DB::table('supplier')
                    ->select('id')
                    ->where('nama', $supplier)
                    ->first()->id ?? 0;

                $satuanId =  DB::table('satuan')
                    ->select('id')
                    ->where('nama', $satuan)
                    ->first()->id ?? 0;

                $groupId =  DB::table('groupproduct')
                    ->select('id')
                    ->where('nama', $group)
                    ->first()->id ?? 0;

                $data = [
                    'nama' => $nama,
                    'groupid' => $groupId,
                    'supplierid' => $supplierId,
                    'satuanid' => $satuanId,
                    'keterangan' => '',
                    'hargajual' => $hargajual,
                    'hargabeli' => $hargabeli,
                    'hargakontrak1' => 0,
                    'hargakontrak2' => 0,
                    'hargakontrak3' => 0,
                    'hargakontrak4' => 0,
                    'hargakontrak5' => 0,
                    'hargakontrak6' => 0,
                    'status' => $statusId,
                    'modifiedby' => auth('api')->user()->name
                ];

                $customer = (new Product())->processStore($data);
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

    public function editingat(EditingAtProductRequest $request)
    {
       
        $product = new Product();
        return response([
            'data' => $product->editingAt($request->id, $request->btn),
        ]);
    }

    public function editall(Request $request)
    {

        $allProduct = json_decode($request->data, true);

        $dataProduct = array_values($allProduct);

       
        $namas = [];
        $ids = [];
        $supplierids = [];
        $suppliernamas = [];
        $satuanids = [];
        $satuannamas = [];
        $hargabelis = [];
        $hargajuals = [];
        $fullfilleds = [];
        foreach ($dataProduct as $product ) {
            $ids[] = $product['id'];
            $namas[] = $product['nama'] ?? '';
            $supplierids[] = $product['supplierid'] ?? '';
            $suppliernamas[] = $product['suppliernama'] ?? '';
            $satuanids[] = $product['satuanid'] ?? '';
            $satuannamas[] = $product['satuannama'] ?? '';
            $hargabelis[] = $product['hargabeli'] ?? '';
            $hargajuals[] = $product['hargajual'] ?? '';
            $fullfilleds[] = $product['fullfilled'] ?? '';
        }


        $data = [
            "id" => $ids,
            "nama" => $namas,
            "supplierid" => $supplierids,
            "suppliernama" => $suppliernamas,
            "satuanid" => $satuanids,
            "satuannama" => $satuannamas,
            "hargabeli" => $hargabelis,
            "hargajual" => $hargajuals,
            "fullfilled" => $fullfilleds,
           
        ];

     
        $product = (new Product())->processEditAll($data);
        
        return response()->json([
            'status' => true,
            'message' => 'Berhasil di edit all',
            'data' => $product
        ]);
    }

    
}
