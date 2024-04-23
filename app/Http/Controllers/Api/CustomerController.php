<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\DestroyCustomerRequest;
use App\Http\Requests\EditingAtCustomerRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Http\Requests\StoreAclRequest;
use App\Models\Customer;
use App\Models\PesananFinalHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerController extends Controller
{
    /**
     * @ClassName
     */
    public function index(Request $request)
    {
        $customer = new Customer();
        return response([
            'data' => $customer->get(),
            'attributes' => [
                'totalRows' => $customer->totalRows,
                'totalPages' => $customer->totalPages,
            ]
        ]);
    }

    public function default()
    {
        $customer = new Customer();
        return response([
            'status' => true,
            'data' => $customer->default(),
        ]);
    }

    public function show($id)
    {
        
        $customer = Customer::findAll($id);
        return response([
            'status' => true,
            'data' => $customer
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('customer')->getColumns();
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
    public function store(StoreCustomerRequest $request): JsonResponse
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
                'ownerid' => $request->ownerid,
                'hargaproduct' => $request->hargaproduct,
                'groupid' => $request->groupid,
                'status' => $request->status,
            ];
            $customer = (new Customer())->processStore($data);
            $customer->position = $this->getPosition($customer, $customer->getTable())->position;
            if ($request->limit == 0) {
                $customer->page = ceil($customer->position / (10));
            } else {
                $customer->page = ceil($customer->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $customer
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    /**
     * @ClassName 
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
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
                'ownerid' => $request->ownerid,
                'hargaproduct' => $request->hargaproduct,
                'groupid' => $request->groupid,
                'status' => $request->status,
            ];

            $customer = (new Customer())->processUpdate($customer, $data);
            $customer->position = $this->getPosition($customer, $customer->getTable())->position;
            if ($request->limit == 0) {
                $customer->page = ceil($customer->position / (10));
            } else {
                $customer->page = ceil($customer->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $customer
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyCustomerRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $customer = (new Customer())->processDestroy($id);
            $selected = $this->getPosition($customer, $customer->getTable(), true);
            $customer->position = $selected->position;
            $customer->id = $selected->id;
            if ($request->limit == 0) {
                $customer->page = ceil($customer->position / (10));
            } else {
                $customer->page = ceil($customer->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $customer
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
                $owner = $sheet->getCell($this->kolomexcel(6) . $row)->getValue();
                $hargaProduct = $sheet->getCell($this->kolomexcel(7) . $row)->getValue();
                $group = $sheet->getCell($this->kolomexcel(8) . $row)->getValue();
                $status = $sheet->getCell($this->kolomexcel(9) . $row)->getValue();

                $statusId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $status)
                    ->first()->id ?? 0;

                $ownerId =  DB::table('owner')
                    ->select('id')
                    ->where('nama', $owner)
                    ->first()->id ?? 0;

                $hargaProductId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $hargaProduct)
                    ->first()->id ?? 0;

                $groupId =  DB::table('groupcustomer')
                    ->select('id')
                    ->where('nama', $group)
                    ->first()->id ?? 0;

                $data = [
                    'nama' => $nama,
                    'nama2' => $nama2,
                    'telepon' => $telepon,
                    'alamat' => $alamat,
                    'keterangan' => $keterangan,
                    'ownerid' => $ownerId,
                    'hargaproduct' => $hargaProductId,
                    'groupid' => $groupId,
                    'status' => $statusId,
                    'modifiedby' => auth('api')->user()->name
                ];
                $customer = (new Customer())->processStore($data);
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

    public function cekValidasi(Request $request, $id){
        // dd($request->button);
        $customer = new Customer();
        $customerId = Customer::from(DB::raw("customer"))->where('id', $id)->first();
        
        $cekdata = $customer->cekValidasiAksi($customerId->id);

        if ($cekdata['kondisi'] == true) {
            $query = DB::table('error')
                ->select(
                    DB::raw("keterangan")
                )
                ->where('kodeerror', '=', $cekdata['kodeerror'])
                ->first();

            $data = [
                'error' => true,
                'message' => $query->keterangan.$request->button. ' KARENA SUDAH ADA DI PESANAN FINAL',
                'kodeerror' => $cekdata['kodeerror'],
                'statuspesan' => 'warning',
            ];
            return response($data);
        } else {
            $data = [
                'error' => false,
                'message' => '',
                'statuspesan' => 'success',
            ];
            return response($data);
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
    public function editingat(EditingAtCustomerRequest $request)
    {
       
        $customer = new Customer();
        return response([
            'data' => $customer->editingAt($request->id, $request->btn),
        ]);
    }
}
