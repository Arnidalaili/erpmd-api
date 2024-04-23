<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHariLiburRequest;
use App\Http\Requests\UpdateHariLiburRequest;
use App\Http\Requests\DestroyHariLiburRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Models\HariLibur;
use App\Models\Parameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HariLiburController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $hariLibur = new HariLibur();
        return response([
            'data' => $hariLibur->get(),
            'attributes' => [
                'totalRows' => $hariLibur->totalRows,
                'totalPages' => $hariLibur->totalPages
            ]
        ]);
    }

    public function default()
    {
        $hariLibur = new HariLibur();
        return response([
            'status' => true,
            'data' => $hariLibur->default(),
        ]);
    }

    public function show($id)
    {
        $hariLibur = HariLibur::where('id', $id)->first();
        return response([
            'status' => true,
            'data' => $hariLibur
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(StoreHariLiburRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'tgl' => date('Y-m-d', strtotime($request->tgl)),
                'keterangan' => $request->keterangan ?? '',
                'status' => $request->status,
            ];
            
            $hariLibur = (new HariLibur())->processStore($data);
            $hariLibur->position = $this->getPosition($hariLibur, $hariLibur->getTable())->position;
            if ($request->limit==0) {
                $hariLibur->page = ceil($hariLibur->position / (10));
            } else {
                $hariLibur->page = ceil($hariLibur->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $hariLibur
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateHariLiburRequest $request, HariLibur $harilibur)
    {
        DB::beginTransaction();

        try {
            $data = [
                'tgl' => date('Y-m-d', strtotime($request->tgl)),
                'keterangan' => $request->keterangan ?? '',
                'status' => $request->status,
            ];

            $harilibur = (new harilibur())->processUpdate($harilibur, $data);
            $harilibur->position = $this->getPosition($harilibur, $harilibur->getTable())->position;
            if ($request->limit==0) {
                $harilibur->page = ceil($harilibur->position / (10));
            } else {
                $harilibur->page = ceil($harilibur->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $harilibur
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyHariLiburRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $harilibur = (new HariLibur())->processDestroy($id);
            $selected = $this->getPosition($harilibur, $harilibur->getTable(), true);
            $harilibur->position = $selected->position;
            $harilibur->id = $selected->id;
            if ($request->limit==0) {
                $harilibur->page = ceil($harilibur->position / (10));
            } else {
                $harilibur->page = ceil($harilibur->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $harilibur
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('harilibur')->getColumns();

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
    public function report(RangeExportReportRequest $request)
    {
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
            $row_range    = range(2, ($row_limit-1));
            $column_range = range('A', $column_limit);
            // $startcount = 4;
            $data = array();

            $a = 0;
            foreach ($row_range as $row) {
                $status = $sheet->getCell($this->kolomexcel(3) . $row)->getValue();


                $statusId =  DB::table('parameter')
                                    ->select('id')
                                    ->where('text', $status)
                                    ->first()->id ?? 0;
            
                $data = [
                    'tgl' => date('Y-m-d', strtotime($sheet->getCell($this->kolomexcel(1) . $row)->getValue())),
                    'keterangan' => $sheet->getCell($this->kolomexcel(2) . $row)->getValue(),
                    'status' => $statusId,
                    'modifiedby' => auth('api')->user()->name
                ];

              

                $hariLibur = (new HariLibur())->processStore($data);

              
            }

            
            
                return response([
                    'status' => true,
                    'keterangan' => 'harga berhasil di update',
                    'data' => $hariLibur,
                   
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

    /**
     * @ClassName 
     */
    public function export(RangeExportReportRequest $request)
    {
        if (request()->cekExport) {
            if (request()->offset == "-1" && request()->limit == '1') {
                return response([
                    'errors' => [
                        "export" => app(ErrorController::class)->geterror('DTA')->keterangan
                    ],
                    'status' => false,
                    'message' => "The given data was invalid."
                ], 422);
            } else {
                return response([
                    'status' => true,
                ]);
            }
        } else {
            header('Access-Control-Allow-Origin: *');
            $response = $this->index();
            $decodedResponse = json_decode($response->content(), true);
            $hariLiburs = $decodedResponse['data'];
            $judulLaporan = $hariLiburs[0]['judulLaporan'];

            $i = 0;
            foreach ($hariLiburs as $index => $params) {
                $status = $params['status'];
                $result = json_decode($status, true);
                $status = $result['MEMO'];
                $hariLiburs[$i]['status'] = $status;
                $i++;
            }
            
            $columns = [
                [
                    'label' => 'No',
                ],
                [
                    'label' => 'Tanggal',
                    'index' => 'tgl',
                ],
                [
                    'label' => 'Keterangan',
                    'index' => 'keterangan',
                ],
                [
                    'label' => 'Status',
                    'index' => 'status',
                ],
            ];
            
            $this->toExcel($judulLaporan, $hariLiburs, $columns);
        }
    }
}
