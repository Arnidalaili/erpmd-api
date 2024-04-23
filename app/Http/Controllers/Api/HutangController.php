<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyHutangRequest;
use App\Http\Requests\StoreHutangRequest;
use App\Http\Requests\UpdateHutangRequest;
use App\Models\Hutang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HutangController extends Controller
{
    /**
     * @ClassName 
     * HutangController
     */
    public function index()
    {
        $hutang = new Hutang();
        return response([
            'data' => $hutang->get(),
            'attributes' => [
                'totalRows' => $hutang->totalRows,
                'totalPages' => $hutang->totalPages
            ]
        ]);
    }

    public function default()
    {
        $hutang = new Hutang();
        return response([
            'status' => true,
            'data' => $hutang->default(),
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = Hutang::findAll($id);
        return response([
            'status' => true,
            'data' => $data
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('hutang')->getColumns();
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
    public function store(StoreHutangRequest $request)
    {
        DB::beginTransaction();

        // dd('cvcvcv');
        try {
            $data = [
                'id' => $request->id,
                'tglbukti' => $request->tglbukti,
                'nobukti' => $request->nobukti,
                'pembelianid' => $request->pembelianid,
                'tglbuktipembelian' => $request->tglbuktipembelian,
                'tgljatuhtempo' => $request->tgljatuhtempo,
                'supplierid' => $request->supplierid,
                'keterangan' => $request->keterangan,
                'nominalhutang' => $request->nominalhutang,
                'nominalbayar' => $request->nominalbayar,
                'nominalsisa' => $request->nominalsisa,
                'status' => 1,
                'flag' => $request->flag,
                // 'tglcetak' => $request->tglcetak,
            ];

            $hutang = (new Hutang())->processStore($data);
            $hutang->position = $this->getPosition($hutang, $hutang->getTable())->position;
            if ($request->limit == 0) {
                $hutang->page = ceil($hutang->position / (10));
            } else {
                $hutang->page = ceil($hutang->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $hutang
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateHutangRequest $request, Hutang $hutang)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                "tglbukti" => $request->tglbukti,
                "nobukti" => $request->nobukti,
                'pembelianid' => $hutang->pembelianid,
                'tglbuktipembelian' => $request->tglbuktipembelian,
                'tgljatuhtempo' => $request->tgljatuhtempo,
                'supplierid' => $request->supplierid,
                'keterangan' => $request->keterangan,
                'nominalhutang' => $request->nominalhutang,
                'nominalbayar' => $request->nominalbayar,
                'nominalsisa' => $request->nominalsisa,
                'status' => 1,
                'flag' => $request->flag,
                // 'tglcetak' => $request->tglcetak,
            ];
            
            $hutang = (new Hutang())->processUpdate($hutang, $data);
            $hutang->position = $this->getPosition($hutang, $hutang->getTable())->position;
            if ($request->limit == 0) {
                $hutang->page = ceil($hutang->position / (10));
            } else {
                $hutang->page = ceil($hutang->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $hutang
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyHutangRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $hutang = (new Hutang())->processDestroy($id);
            $selected = $this->getPosition($hutang, $hutang->getTable(), true);
            $hutang->position = $selected->position;
            $hutang->id = $selected->id;
            if ($request->limit == 0) {
                $hutang->page = ceil($hutang->position / (10));
            } else {
                $hutang->page = ceil($hutang->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $hutang
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function cekvalidasiAksi($id)
    {
        $hutang = new Hutang();
        $pembelianid = Hutang::from(DB::raw("hutang"))->where('id', $id)->first();
        $cekdata = $hutang->cekValidasiAksi($pembelianid->pembelianid);

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
