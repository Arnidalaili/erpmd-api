<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FakturPenjualanHeaderRequest;
use App\Http\Requests\StoreFakturPenjualanHeaderRequest;
use App\Models\FakturPenjualanDetailModel;
use App\Models\FakturPenjualanHeaderModel;
use Carbon\Carbon;
use Fakturpenjualanheader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FakturPenjualanHeaderController extends Controller
{
     /**
     * @ClassName 
     * FakturPenjualanHeaderController
     * @Detail FakturPenjualanDetailController
     */
    public function index()
    {
       
        $fakturPenjualanHeader = new FakturPenjualanHeaderModel();

        

        return response([
            'data' => $fakturPenjualanHeader->get(),
            'attributes' => [
                'totalRows' => $fakturPenjualanHeader->totalRows,
                'totalPages' => $fakturPenjualanHeader->totalPages
            ]
        ]);
    }

    
   

   
   /**
     * @ClassName 
     */
    public function store(StoreFakturPenjualanHeaderRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = [
                "invoicedate" => $request->invoicedate,
                "customer_id" => $request->customer_id,
                "nopo" => $request->nopo,
                "shipto" => $request->shipto,
                "rate" => $request->rate,
                "fob" => $request->fob,
                "terms" => $request->terms,
                "fiscalrate" => $request->fiscalrate,
                "shipdate" => $request->shipdate,
                "shipvia" => $request->shipvia,
                "receivableacoount" => $request->receivableacoount,
                "sales_id" => $request->sales_id,
                "item_id" =>$request->item_id,
                "description" => $request->description,
                "qty" => $request->qty,
                "hargasatuan" =>$request->hargasatuan,
                "amount" => $request->amount
               
            ];

          


            /* Store header */
            $fakturPenjualanHeader = (new FakturPenjualanHeaderModel())->processStore($data);

            /* Set position and page */
            $fakturPenjualanHeader->position = $this->getPosition($fakturPenjualanHeader, $fakturPenjualanHeader->getTable())->position;
            if ($request->limit==0) {
                $fakturPenjualanHeader->page = ceil($fakturPenjualanHeader->position / (10));
            } else {
                $fakturPenjualanHeader->page = ceil($fakturPenjualanHeader->position / ($request->limit ?? 10));
            }
           
            

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $fakturPenjualanHeader
            ], 201);    
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

   /**
     * @ClassName 
     */
    public function show($id)
    {

        $data = FakturPenjualanHeaderModel::findAll($id);
        $detail = FakturPenjualanDetailModel::getAll($id);


        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }

   
   
   /**
     * @ClassName 
     */
    public function update(Request $request, $id)
    {
       
        DB::beginTransaction();
        try {
            $data = [
                "invoicedate" => $request->invoicedate,
                "customer_id" => $request->customer_id,
                "nopo" => $request->nopo,
                "shipto" => $request->shipto,
                "rate" => $request->rate,
                "fob" => $request->fob,
                "terms" => $request->terms,
                "fiscalrate" => $request->fiscalrate,
                "shipdate" => $request->shipdate,
                "shipvia" => $request->shipvia,
                "receivableacoount" => $request->receivableacoount,
                "sales_id" => $request->sales_id,
                "item_id" =>$request->item_id,
                "description" => $request->description,
                "qty" => $request->qty,
                "hargasatuan" =>$request->hargasatuan,
                "amount" => $request->amount
               
            ];

          
            /* Store header */
            $fakturPenjualanHeader = FakturPenjualanHeaderModel::findOrFail($id);

          
            $fakturPenjualanHeader = (new FakturPenjualanHeaderModel())->processUpdate($fakturPenjualanHeader,$data);

            // dd($fakturPenjualanHeader);
            /* Set position and page */
            $fakturPenjualanHeader->position = $this->getPosition($fakturPenjualanHeader, $fakturPenjualanHeader->getTable())->position;
            if ($request->limit==0) {
                $fakturPenjualanHeader->page = ceil($fakturPenjualanHeader->position / (10));
            } else {
                $fakturPenjualanHeader->page = ceil($fakturPenjualanHeader->position / ($request->limit ?? 10));
            }
            $fakturPenjualanHeader->tgldariheader = date('Y-m-01', strtotime(request()->tglbukti));
            $fakturPenjualanHeader->tglsampaiheader = date('Y-m-t', strtotime(request()->tglbukti));
            
            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $fakturPenjualanHeader
            ]);    
        } catch (\Throwable $th) {
            DB::rollBack();
 
            throw $th;
        }
    }

    
   /**
     * @ClassName 
     */
    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $fakturPenjualanHeader = (new FakturPenjualanHeaderModel())->processDestroy($id, "DELETE FAKTUR PENJUALAN HEADER");
            $selected = $this->getPosition($fakturPenjualanHeader, $fakturPenjualanHeader->getTable(), true);
            $fakturPenjualanHeader->position = $selected->position;
            $fakturPenjualanHeader->id = $selected->id;
            if ($request->limit==0) {
                $fakturPenjualanHeader->page = ceil($fakturPenjualanHeader->position / (10));
            } else {
                $fakturPenjualanHeader->page = ceil($fakturPenjualanHeader->position / ($request->limit ?? 10));
            }
           
            
            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $fakturPenjualanHeader
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

                // Ambil data dari Sheet 1 (Header)
                $sheet1 = $spreadsheet->getSheetByName('Header');
                $row_limit1 = $sheet1->getHighestDataRow();
                $column_limit1 = $sheet1->getHighestDataColumn();
                $row_range1 = range(2, ($row_limit1-1));
                $column_range1 = range('A', $column_limit1);

                foreach ($row_range1 as $row) {
                    $customer = $sheet1->getCell($this->kolomexcel(3) . $row)->getValue();
                    $sales = $sheet1->getCell($this->kolomexcel(13) . $row)->getValue();

                    $customerId = DB::table('customers')
                        ->select('id')
                        ->where('name', $customer)
                        ->first()->id ?? 0;

                    $salesId = DB::table('customers')
                        ->select('id')
                        ->where('name', $sales)
                        ->first()->id ?? 0;

                    $headerData = [
                        'customer_id' => $customerId,
                        'nopo' =>  $sheet1->getCell($this->kolomexcel(4) . $row)->getValue(),
                        'noinvoice' => $sheet1->getCell($this->kolomexcel(1) . $row)->getValue(),
                        'invoicedate' =>   date('Y-m-d', strtotime($sheet1->getCell($this->kolomexcel(2) . $row)->getValue())),
                        'shipto' => $sheet1->getCell($this->kolomexcel(5) . $row)->getValue(),
                        'rate' => $sheet1->getCell($this->kolomexcel(6) . $row)->getValue(),
                        'fob' => $sheet1->getCell($this->kolomexcel(7) . $row)->getValue(),
                        'terms' => $sheet1->getCell($this->kolomexcel(8) . $row)->getValue(),
                        'fiscalrate' => $sheet1->getCell($this->kolomexcel(9) . $row)->getValue(),
                        'shipdate' =>  date('Y-m-d', strtotime($sheet1->getCell($this->kolomexcel(10) . $row)->getValue())),
                        'shipvia' => $sheet1->getCell($this->kolomexcel(11) . $row)->getValue(),
                        'receivableacoount' => $sheet1->getCell($this->kolomexcel(12) . $row)->getValue(),
                        'sales_id' => $salesId,
                        'modifiedby' => auth('api')->user()->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ];

                    // Memasukkan data ke dalam tabel header
                    $headerId = DB::table('fakturpenjualanheader')->insertGetId($headerData);

                    // Ambil data dari Sheet 2 (Detail)
                    $sheet2 = $spreadsheet->getSheetByName('Detail');
                    $row_limit2 = $sheet2->getHighestDataRow();
                    $column_limit2 = $sheet2->getHighestDataColumn();
                    $row_range2 = range(2, ($row_limit2-1));
                    $column_range2 = range('A', $column_limit2);

                    foreach ($row_range2 as $row) {
                        $noInvoice = $sheet2->getCell($this->kolomexcel(1) . $row)->getValue();
                        $item = $sheet2->getCell($this->kolomexcel(2) . $row)->getValue();

                        if ($noInvoice === $headerData['noinvoice']) {
                            $itemId = DB::table('customers')
                                ->select('id')
                                ->where('name', $item)
                                ->first()->id ?? 0;

                            $detailData = [
                                'fakturpenjualan_id' => $headerId,
                                'item_id' => $itemId,
                                'description' => $sheet2->getCell($this->kolomexcel(3) . $row)->getValue(),
                                'qty' => $sheet2->getCell($this->kolomexcel(4) . $row)->getValue(),
                                'hargasatuan' => $sheet2->getCell($this->kolomexcel(5) . $row)->getValue(),
                                'amount' => $sheet2->getCell($this->kolomexcel(6) . $row)->getValue(),
                                'modifiedby' => auth('api')->user()->id,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];

                            // Memasukkan data ke dalam tabel detail
                            DB::table('fakturpenjualandetail')->insert($detailData);
                        }
                    }
                }
            
                return response([
                    'status' => true,
                    'keterangan' => 'harga berhasil di update',
                    // 'data' => $hariLibur,
                   
                ]);
            
        } catch (\Throwable $th) {
          
            DB::rollBack();
            throw $th;
        }
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('fakturpenjualanheader')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
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

    /**
     * @ClassName 
     */
    public function report($id)
    {
        $fakturPenjualan = new FakturPenjualanHeaderModel();
        return response([
            'data' => $fakturPenjualan->getReport($id)
        ]);
    }

    /**
     * @ClassName 
     */
    public function export($id)
    {
    }

    /**
     * @ClassName 
     */
    public function pdf($id)
    {
    }
}
