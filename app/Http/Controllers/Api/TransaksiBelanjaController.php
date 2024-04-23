<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransaksiBelanjaEditAllRequest;
use Illuminate\Http\Request;
use App\Models\TransaksiBelanja;
use Illuminate\Support\Facades\DB;


class TransaksiBelanjaController extends Controller
{
    /**
     * @ClassName 
     * TransaksiBelanjaController
     */
    public function index()
    {
        $transaksiBelanja = new TransaksiBelanja();
        return response([
            'data' => $transaksiBelanja->get(),
            'attributes' => [
                'totalRows' => $transaksiBelanja->totalRows,
                'totalPages' => $transaksiBelanja->totalPages,
                'totalPanjar' => $transaksiBelanja->totalPanjar,
                'totalBiaya' => $transaksiBelanja->totalBiaya,
                'totalSisa' => $transaksiBelanja->totalSisa
            ]
        ]);
    }

    public function default()
    {
        $transaksiBelanja = new TransaksiBelanja();
        return response([
            'status' => true,
            'data' => $transaksiBelanja->default(),
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = TransaksiBelanja::findAll($id);
        return response([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('transaksibelanja')->getColumns();
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
                "pembelianid" => request()->pembelianid,
                "pembeliannobukti" => request()->pembeliannobukti,
                "nominal" => request()->nominal,
                "keterangan" => request()->keterangan,
                "status" => 1,
            ];
            $transaksiBelanja = (new TransaksiBelanja())->processStore($data);

            /* Set position and page */
            $transaksiBelanja->position = $this->getPosition($transaksiBelanja, $transaksiBelanja->getTable())->position;

            if ($request->limit == 0) {
                $transaksiBelanja->page = ceil($transaksiBelanja->position / (10));
            } else {
                $transaksiBelanja->page = ceil($transaksiBelanja->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $transaksiBelanja
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

     /**
     * @ClassName 
     */
    public function update(Request $request, TransaksiBelanja $transaksiBelanja, $id)
    {
        DB::beginTransaction();
        try {
            $data = [
                "perkiraanid" => request()->perkiraanid,
                "perkiraannama" => request()->perkiraannama,
                "tglbukti" => request()->tglbukti,
                "karyawanid" => request()->karyawanid,
                "karyawannama" => request()->karyawannama,
                "pembelianid" => request()->pembelianid,
                "pembeliannobukti" => request()->pembeliannobukti,
                "nominal" => request()->nominal,
                "keterangan" => request()->keterangan,
                "status" => 1,
            ];
            $transaksiBelanja = TransaksiBelanja::findOrFail($id);

            $transaksiBelanja = (new TransaksiBelanja())->processUpdate($transaksiBelanja, $data);
            /* Set position and page */
            $transaksiBelanja->position = $this->getPosition($transaksiBelanja, $transaksiBelanja->getTable())->position;
            if ($request->limit == 0) {
                $transaksiBelanja->page = ceil($transaksiBelanja->position / (10));
            } else {
                $transaksiBelanja->page = ceil($transaksiBelanja->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $transaksiBelanja
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
            $transaksiBelanja = (new TransaksiBelanja())->processDestroy($id);
            $selected = $this->getPosition($transaksiBelanja, $transaksiBelanja->getTable(), true);
            $transaksiBelanja->position = $selected->position;
            $transaksiBelanja->id = $selected->id;
            if ($request->limit == 0) {
                $transaksiBelanja->page = ceil($transaksiBelanja->position / (10));
            } else {
                $transaksiBelanja->page = ceil($transaksiBelanja->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $transaksiBelanja
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function cekvalidasiAksi($id)
    {
        $transaksiBelanja = new TransaksiBelanja();
        $transaksiid = TransaksiBelanja::from(DB::raw("transaksibelanja"))->where('id', $id)->first();
        $cekdata = $transaksiBelanja->cekValidasiAksi($transaksiid->pembelianid);

        if ($cekdata['kondisi'] == true) {
            $query = DB::table('error')
                ->select(
                    DB::raw("keterangan")
                )
                ->where('kodeerror', '=', $cekdata['kodeerror'])
                ->first();

            $data = [
                'error' => true,
                'message' => $query->keterangan,
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

    public function editall()
    {
        $transaksiBelanja = new TransaksiBelanja();
        $data = $transaksiBelanja->findEditAll();
        return response([
            'status' => true,
            'data' => $data,
            'attributes' => [
                'totalRows' => $transaksiBelanja->totalRows,
                'totalPages' => $transaksiBelanja->totalPages
            ]
        ]);
    }

    public function processeditall(StoreTransaksiBelanjaEditAllRequest $request){
        $allData = json_decode($request->data, true);

        $dataTransaksiBelanja = array_values($allData);


        DB::beginTransaction();
        try {

            $data = (new TransaksiBelanja())->processData($dataTransaksiBelanja);

            /* Store header */
            $transaksiBelanja = (new TransaksiBelanja())->processEditAll($data);
           
            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $transaksiBelanja
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function addrow(StoreTransaksiBelanjaEditAllRequest $request)
    {
        return true;
    }
}
