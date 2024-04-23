<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransaksiArmadaEditAllRequest;
use App\Models\TransaksiArmada;
use Illuminate\Support\Facades\DB;

class TransaksiArmadaController extends Controller
{
    /**
     * @ClassName 
     * TransaksiArmadaController
     */
    public function index()
    {
        $transaksiArmada = new TransaksiArmada();
        return response([
            'data' => $transaksiArmada->get(),
            'attributes' => [
                'totalRows' => $transaksiArmada->totalRows,
                'totalPages' => $transaksiArmada->totalPages,
                'totalPanjar' => $transaksiArmada->totalPanjar,
                'totalBiaya' => $transaksiArmada->totalBiaya,
                'totalSisa' => $transaksiArmada->totalSisa
            ]
        ]);
    }

    public function default()
    {
        $transaksiArmada = new TransaksiArmada();
        return response([
            'status' => true,
            'data' => $transaksiArmada->default(),
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = TransaksiArmada::findAll($id);
        return response([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('transaksiarmada')->getColumns();
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
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = [
                "perkiraanid" => request()->perkiraanid,
                "perkiraannama" => request()->perkiraannama,
                "tglbukti" => request()->tglbukti,
                "karyawanid" => request()->karyawanid,
                "karyawannama" => request()->karyawannama,
                "armadaid" => request()->armadaid,
                "armadanama" => request()->armadanama,
                "nominal" => request()->nominal,
                "keterangan" => request()->keterangan,
                "status" => 1,
            ];

           
            $transaksiArmada = (new TransaksiArmada())->processStore($data);

           
            /* Set position and page */
            $transaksiArmada->position = $this->getPosition($transaksiArmada, $transaksiArmada->getTable())->position;

            if ($request->limit == 0) {
                $transaksiArmada->page = ceil($transaksiArmada->position / (10));
            } else {
                $transaksiArmada->page = ceil($transaksiArmada->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $transaksiArmada
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(Request $request, TransaksiArmada $transaksiArmada, $id)
    {
        DB::beginTransaction();
        try {
            $data = [
                "perkiraanid" => request()->perkiraanid,
                "perkiraannama" => request()->perkiraannama,
                "tglbukti" => request()->tglbukti,
                "karyawanid" => request()->karyawanid,
                "karyawannama" => request()->karyawannama,
                "armadaid" => request()->armadaid,
                "armadanama" => request()->armadanama,
                "nominal" => request()->nominal,
                "keterangan" => request()->keterangan,
                "status" => 1,
            ];
            $transaksiArmada = TransaksiArmada::findOrFail($id);

            $transaksiArmada = (new TransaksiArmada())->processUpdate($transaksiArmada, $data);
            /* Set position and page */
            $transaksiArmada->position = $this->getPosition($transaksiArmada, $transaksiArmada->getTable())->position;
            if ($request->limit == 0) {
                $transaksiArmada->page = ceil($transaksiArmada->position / (10));
            } else {
                $transaksiArmada->page = ceil($transaksiArmada->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $transaksiArmada
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
            $transaksiArmada = (new TransaksiArmada())->processDestroy($id);
            $selected = $this->getPosition($transaksiArmada, $transaksiArmada->getTable(), true);
            $transaksiArmada->position = $selected->position;
            $transaksiArmada->id = $selected->id;
            if ($request->limit == 0) {
                $transaksiArmada->page = ceil($transaksiArmada->position / (10));
            } else {
                $transaksiArmada->page = ceil($transaksiArmada->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $transaksiArmada
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function editall()
    {
        $transaksiArmada = new TransaksiArmada();
        $data = $transaksiArmada->findEditAll();
        return response([
            'status' => true,
            'data' => $data,
            'attributes' => [
                'totalRows' => $transaksiArmada->totalRows,
                'totalPages' => $transaksiArmada->totalPages
            ]
        ]);
    }

    public function processeditall(StoreTransaksiArmadaEditAllRequest $request){
        $allData = json_decode($request->data, true);

        $dataTransaksiArmada = array_values($allData);


        DB::beginTransaction();
        try {

            $data = (new TransaksiArmada())->processData($dataTransaksiArmada);
            /* Store header */
            $transaksiArmada = (new TransaksiArmada())->processEditAll($data);
           
            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $transaksiArmada
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function addrow(StoreTransaksiArmadaEditAllRequest $request)
    {
        return true;
    }
}
