<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Http\Requests\ParameterRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Http\Requests\UpdateParameterRequest;

use App\Http\Requests\StoreLogTrailRequest;
use App\Http\Resources\Parameter as ResourcesParameter;
use App\Http\Resources\ParameterResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class ParameterController extends Controller
{
    /**
     * @ClassName
     */
    public function index(Request $request)
    {
        $parameter = new Parameter();

        // $page = $request->input('offset',1);

        return response([
            'data' => $parameter->get(),
            'attributes' => [
                'totalRows' => $parameter->totalRows,
                'totalPages' => $parameter->totalPages,
                // 'more' => $request->input('offset', 1) < $parameter->totalRows //for select2
            ],
           
        ]);
    }

    public function default()
    {
        $parameter = new Parameter();
        return response([
            'status' => true,
            'data' => $parameter->default()
        ]);
    }


    /**
     * @ClassName
     */
    public function store(ParameterRequest $request)
    {
        $data = [
            'id' => $request->id,
            "grp" => $request->grp,
            "subgrp" => $request->subgrp,
            "text" => $request->text,
            "kelompok" => $request->kelompok,
            "type" => $request->type,
            "grup" => $request->grup,
            "default" => $request->default,
            "key" => $request->key,
            "value" => $request->value,
        ];
        DB::beginTransaction();

        try {

            $parameter = (new Parameter())->processStore( $data);
            $parameter->position = $this->getPosition($parameter, $parameter->getTable())->position;
            if ($request->limit==0) {
                $parameter->page = ceil($parameter->position / (10));
            } else {
                $parameter->page = ceil($parameter->position / ($request->limit ?? 10));
            }
            
            if (isset($request->limit)) {
                $parameter->page = ceil($parameter->position / $request->limit);
            }

            DB::commit();

            return response([
                'message' => 'Berhasil disimpan',
                'data' => $parameter
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function show($id)
    {
        $parameter = new Parameter();


        return response([
            'status' => true,
            'data' => $parameter->findAll($id)
        ]);
    }

    /**
     * @ClassName
     */
    public function update(UpdateParameterRequest $request, $id)
    {

        $data = [
            'id' => $request->id,
            "grp" => $request->grp,
            "subgrp" => $request->subgrp,
            "text" => $request->text,
            "kelompok" => $request->kelompok,
            "type" => $request->type,
            "grup" => $request->grup,
            "default" => $request->default,
            "key" => $request->key,
            "value" => $request->value,
        ];
        DB::beginTransaction();

        try {
            $parameter = Parameter::lockForUpdate()->findOrFail($id);
            $parameter = (new Parameter())->processUpdate($parameter, $data);
            $parameter->position = $this->getPosition($parameter, $parameter->getTable())->position;
            if ($request->limit==0) {
                $parameter->page = ceil($parameter->position / (10));
            } else {
                $parameter->page = ceil($parameter->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response([
                'status' => true,
                'message' => 'Berhasil diubah',
                'data' => $parameter
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

        try{
            $parameter = (new parameter())->processDestroy($id);
            $selected = $this->getPosition($parameter, $parameter->getTable(), true);
            $parameter->position = $selected->position;
            $parameter->id = $selected->id;
            if ($request->limit==0) {
                $parameter->page = ceil($parameter->position / (10));
            } else {
                $parameter->page = ceil($parameter->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $parameter
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function getcoa(Request $request)
    {

        $parameter = new Parameter();
        return response([
            'data' => $parameter->getcoa($request->filter)
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('parameter')->getColumns();

        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function detail()
    {
        $query = Parameter::select('memo')->where('id', request()->id)->first();

        // dd($query);
        $array = [];
        if (request()->id != 0) {
            $memo = json_decode($query->memo);
            if ($memo != '') {
                $i = 0;
                foreach ($memo as $index => $value) {
                    $array[$i]['key'] = $index;
                    $array[$i]['value'] = $value;

                    $i++;
                }
            }
        }

        return response([
            'data' => $array
        ]);
    }

    public function getparameterid($grp, $subgrp, $text)
    {

        $querydata = Parameter::select('id as id', 'text')
            ->where('grp', '=',  $grp)
            ->where('subgrp', '=',  $subgrp)
            ->where('text', '=',  $text)
            ->orderBy('id');


        $data = $querydata->first();
        return $data;
    }

    public function getparamrequest(Request $request)
    {
        $querydata = Parameter::select('id as id', 'text')
            ->where('grp', '=',  $request->grp)
            ->where('subgrp', '=',  $request->subgrp)
            ->where('text', '=',  $request->text)
            ->orderBy('id');


        $data = $querydata->first();
        return $data;
    }

    public function getparamid($grp, $subgrp)
    {

        $querydata = Parameter::select('id as id', 'text')
            ->where('grp', '=',  $grp)
            ->where('subgrp', '=',  $subgrp)
            ->orderBy('id');


        $data = $querydata->first();
        return $data;
    }

    public function getparamfirst(Request $request)
    {

        $querydata = Parameter::select('id as id', 'text')
            ->where('grp', '=',  $request->grp)
            ->where('subgrp', '=', $request->subgrp)
            ->orderBy('id');


        $data = $querydata->first();
        return $data;
    }

    public function getParamByText(Request $request)
    {

        $querydata = Parameter::where('grp', '=',  $request->grp)
            ->where('text', '=',  $request->text)
            ->first();

        if($querydata != null){
            $data = $querydata;
        }else{
            $data = [];
        }

        return $data;
    }

    //  /**
    //  * @ClassName 
    //  */
    public function report()
    {
    }
    
    // /**
    //  * @ClassName
    //  */
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
            $parameters = $decodedResponse['data'];

            $judulLaporan = $parameters[0]['judulLaporan'];

            $i = 0;
            foreach ($parameters as $index => $params) {
                $memo = $params['memo'];
                $result = json_decode($memo, true);
                $memo = $result['MEMO'];
                $parameters[$i]['memo'] = $memo;
                $i++;
            }

            $columns = [
                [
                    'label' => 'No',
                ],
                [
                    'label' => 'Group',
                    'index' => 'grp',
                ],
                [
                    'label' => 'Subgroup',
                    'index' => 'subgrp',
                ],
                [
                    'label' => 'Text',
                    'index' => 'text',
                ],
                [
                    'label' => 'Type',
                    'index' => 'type',
                ],
                [
                    'label' => 'Memo',
                    'index' => 'memo',
                ],
            ];

            $this->toExcel($judulLaporan, $parameters, $columns);
        }
    }

    public function combo(Request $request)
    {
        // $parameters = Parameter::where('grp', '=', $request->grp)
        //     ->where('subgrp', '=', $request->subgrp)
        //     ->get();

        // return response([
        //     'data' => $parameters
        // ]);

        $parameter = new Parameter();


        return response([
            'data' => $parameter->combo(),
            
        ]);
    }

    public function combolist(Request $request)
    {
        $params = [
            'status' => $request->status ?? '',
            'grp' => $request->grp ?? '',
            'subgrp' => $request->subgrp ?? '',
        ];
        
        $temp = 'temp_' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        
        if ($params['status'] == 'entry') {
            $query = Parameter::select('id', 'text as keterangan')
                ->where('grp', "=", $params['grp'])
                ->where('subgrp', "=", $params['subgrp']);
        } else {
            DB::statement("
                CREATE TEMPORARY TABLE $temp
                (
                    id INT(11) DEFAULT NULL,
                    parameter VARCHAR(50) DEFAULT NULL,
                    param VARCHAR(50) DEFAULT NULL
                )
            ");
            DB::statement("
                INSERT INTO $temp (id, parameter, param)
                VALUES ('0', 'ALL', '')
            ");
            $query = "
                INSERT INTO $temp (id, parameter, param)
                SELECT id, text, text
                FROM parameter
                WHERE grp = '" . $params['grp'] . "'
                AND subgrp = '" . $params['subgrp'] . "'
            ";
            DB::statement($query);
        }
        $queryGetData = "SELECT * FROM $temp";
        $data = DB::select($queryGetData);
        return response([
            'data' => $data
        ]);
    }

    public function select2(Request $request)
    {
        $page = $request->input('page', 1);
        $per_page = 20;

        $search = $request->input('search');

        $query = DB::table('parameter');

        if ($search) {
            $query->where('grp', 'LIKE', "%$search%");
        }

        $total_count = $query->count();
        $total_pages = ceil($total_count / $per_page);

        $data = $query->select('id', DB::raw('grp AS text'))
            ->offset(($page - 1) * $per_page)
            ->limit($per_page)
            ->get();

        $response = array(
            'results' => $data,
            'pagination' => array(
                'more' => $page < $total_pages,
                'total_pages' => $total_pages
            )
        );

        return response()->json($response);
    }
}
