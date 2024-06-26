<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreErrorRequest;
use App\Http\Requests\UpdateErrorRequest;
use App\Http\Requests\StoreLogTrailRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\LogTrailController;
use App\Http\Requests\RangeExportReportRequest;
use App\Models\Error;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;

class ErrorController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $error = new Error();

        return response([
            'data' => $error->get(),
            'attributes' => [
                'totalRows' => $error->totalRows,
                'totalPages' => $error->totalPages
            ]
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(StoreErrorRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = [
                'kodeerror' => $request->kodeerror,
                'keterangan' => $request->keterangan
            ];
            $error = (new Error())->processStore($data);
            $error->position = $this->getPosition($error, $error->getTable())->position;
            if ($request->limit == 0) {
                $error->page = ceil($error->position / (10));
            } else {
                $error->page = ceil($error->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $error
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response($th->getMessage());
        }
    }

    public function show(Error $error)
    {
        return response([
            'status' => true,
            'data' => $error
        ]);
    }

    /**
     * @ClassName 
     */
    public function update(UpdateErrorRequest $request, Error $error)
    {
        DB::beginTransaction();
        try {
            $data = [
                'kodeerror' => $request->kodeerror,
                'keterangan' => $request->keterangan
            ];
            $error = (new Error())->processUpdate($error, $data);
            $error->position = $this->getPosition($error, $error->getTable())->position;
            if ($request->limit == 0) {
                $error->page = ceil($error->position / (10));
            } else {
                $error->page = ceil($error->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response([
                'status' => true,
                'message' => 'Berhasil diubah',
                'data' => $error
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response($th->getMessage());
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $error = (new Error())->processDestroy($id);
            $selected = $this->getPosition($error, $error->getTable(), true);
            $error->position = $selected->position;
            $error->id = $selected->id;
            if ($request->limit == 0) {
                $error->page = ceil($error->position / (10));
            } else {
                $error->page = ceil($error->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $error
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function report()
    {
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
            $response = $this->index();
            $decodedResponse = json_decode($response->content(), true);
            $errors = $decodedResponse['data'];

            $judulLaporan = $errors[0]['judulLaporan'];

            $columns = [
                [
                    'label' => 'No',
                ],
                [
                    'label' => 'Kode Error',
                    'index' => 'kodeerror',
                ],
                [
                    'label' => 'Keterangan',
                    'index' => 'keterangan',
                ],
            ];

            $this->toExcel($judulLaporan, $errors, $columns);
        }
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('error')->getColumns();

        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function geterror($kodeerror)
    {
        
        $data = DB::table('error')->from(
            DB::raw("error")
        )
            ->select('keterangan')
            ->where('kodeerror', '=', $kodeerror)
            ->first();
        if (!$data) {
            $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
            DB::statement("
                CREATE TEMPORARY TABLE $temp
                (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    keterangan VARCHAR(50)
                )
            ");
            DB::statement("
                INSERT INTO $temp (keterangan)
                VALUES ('kode error belum terdaftar')
            ");
            $data = DB::table($temp)
                ->select('keterangan')
                ->first();
            
        }
        
        return $data;
    }

    public function errorUrl(Request $request)
    {
        return $this->geterror($request->all());
    }
}
