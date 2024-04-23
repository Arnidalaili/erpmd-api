<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePiutangRequest;
use App\Models\Piutang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiutangController extends Controller
{
    /**
     * @ClassName 
     * HutangController
     */
    public function index()
    {
        $piutang = new Piutang();
        return response([
            'data' => $piutang->get(),
            'attributes' => [
                'totalRows' => $piutang->totalRows,
                'totalPages' => $piutang->totalPages
            ]
        ]);
    }

    public function default()
    {
        $piutang = new Piutang();
        return response([
            'status' => true,
            'data' => $piutang->default(),
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
                'tglbukti' => $request->tglbukti,
                'nobukti' => $request->nobukti,
                'penjualanid' => $request->penjualanid,
                'tglbuktipenjualan' => $request->tglbuktipenjualan,
                'tgljatuhtempo' => $request->tgljatuhtempo,
                'customerid' => $request->customerid,
                'keterangan' => $request->keterangan,
                'nominalpiutang' => $request->nominalpiutang,
                'nominalbayar' => $request->nominalbayar,
                'nominalsisa' => $request->nominalsisa,
                'status' => 1,
                // 'tglcetak' => $request->tglcetak,
            ];

            $piutang = (new Piutang())->processStore($data);
            $piutang->position = $this->getPosition($piutang, $piutang->getTable())->position;
            if ($request->limit == 0) {
                $piutang->page = ceil($piutang->position / (10));
            } else {
                $piutang->page = ceil($piutang->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $piutang
            ]);
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
    
        $data = Piutang::findAll($id);
        return response([
            'status' => true,
            'data' => $data
        ]);
    }


    /**
     * @ClassName 
     */
    public function update(Request $request, Piutang $piutang)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                "tglbukti" => $request->tglbukti,
                "nobukti" => $request->nobukti,
                'penjualanid' => $request->penjualanid,
                'tglbuktipenjualan' => $request->tglbuktipenjualan,
                'tgljatuhtempo' => $request->tgljatuhtempo,
                'customerid' => $request->customerid,
                'keterangan' => $request->keterangan,
                'nominalpiutang' => $request->nominalpiutang,
                'nominalbayar' => $request->nominalbayar,
                'nominalsisa' => $request->nominalsisa,
                'status' => 1,
                // 'tglcetak' => $request->tglcetak,
            ];
            // dd($hutang);

            $piutang = (new Piutang())->processUpdate($piutang, $data);
            $piutang->position = $this->getPosition($piutang, $piutang->getTable())->position;
            if ($request->limit == 0) {
                $piutang->page = ceil($piutang->position / (10));
            } else {
                $piutang->page = ceil($piutang->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $piutang
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
            $piutang = (new Piutang())->processDestroy($id);
            $selected = $this->getPosition($piutang, $piutang->getTable(), true);
            $piutang->position = $selected->position;
            $piutang->id = $selected->id;
            if ($request->limit == 0) {
                $piutang->page = ceil($piutang->position / (10));
            } else {
                $piutang->page = ceil($piutang->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $piutang
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('piutang')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function cekvalidasiAksi($id)
    {
        $piutang = new Piutang();
        $customerid = Piutang::from(DB::raw("piutang"))->where('id', $id)->first();
        $cekdata = $piutang->cekValidasiAksi($customerid->penjualanid);

        // dd($cekdata);
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
}
