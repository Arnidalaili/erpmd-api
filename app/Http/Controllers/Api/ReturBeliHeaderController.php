<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ReturBeliDetail;
use Illuminate\Support\Facades\DB;
use App\Models\ReturBeliHeader;

class ReturBeliHeaderController extends Controller
{
    /**
     * @ClassName 
     * ReturBeliHeaderController
     * @Detail ReturBeliDetailController
     */
    public function index()
    {
        $returBeliHeader = new ReturBeliHeader();
        return response([
            'data' => $returBeliHeader->get(),
            'attributes' => [
                'totalRows' => $returBeliHeader->totalRows,
                'totalPages' => $returBeliHeader->totalPages
            ]
        ]);
    }

    public function default()
    {
        $returBeliHeader = new ReturBeliHeader();
        return response([
            'status' => true,
            'data' => $returBeliHeader->default(),
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = ReturBeliHeader::findAll($id);
        $detail = ReturBeliDetail::getAll($id);

        // dd($data, $detail);
        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('returbeliheader')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }
        return response([
            'data' => $data
        ]);
    }

    public function editingat(Request $request)
    {
        $returBeliHeader = new ReturBeliHeader();
        return response([
            'data' => $returBeliHeader->editingAt($request->id, $request->btn),
        ]);
    }

    public function getPembelianDetail(Request $request) {
        $pembelianid = $request->pembelianid;

        // dd($pembelianid);

        $returBeliHeader = new ReturBeliHeader();
        return response([
            'data' => $returBeliHeader->getPembelianDetail($pembelianid),
            'pembelianid' => $pembelianid,
            'attributes' => [
                'totalRows' => $returBeliHeader->totalRows,
                'totalPages' => $returBeliHeader->totalPages
            ]
        ]);
    }

    public function getEditPembelianDetail(Request $request) 
    {
        $pembelianid = $request->pembelianid;
        $id = $request->id;

        // dd($id);

        $returBeliHeader = new ReturBeliHeader();
        return response([
            'data' => $returBeliHeader->getEditPembelianDetail($pembelianid, $id)
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(Request $request)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);
        // dd($details);

        // dd($details);
        DB::beginTransaction();
        try {

            $data = (new ReturBeliHeader())->processData($details);
            /* Store header */
            $returBeliHeader = (new ReturBeliHeader())->processStore($data);
            /* Set position and page */
            $returBeliHeader->position = $this->getPosition($returBeliHeader, $returBeliHeader->getTable())->position;

            if ($request->limit == 0) {
                $returBeliHeader->page = ceil($returBeliHeader->position / (10));
            } else {
                $returBeliHeader->page = ceil($returBeliHeader->position / ($request->limit ?? 10));
            }
            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $returBeliHeader
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(Request $request, ReturBeliHeader $returBeliHeader, $id)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();         
        try {
            $data = (new ReturBeliHeader())->processData($details);
            /* Store header */
            $returBeliHeader = ReturBeliHeader::findOrFail($id);
            $returBeliHeader = (new ReturBeliHeader())->processUpdate($returBeliHeader, $data);

            // dd($returBeliHeader);
            /* Set position and page */
            $returBeliHeader->position = $this->getPosition($returBeliHeader, $returBeliHeader->getTable())->position;
            if ($request->limit == 0) {
                $returBeliHeader->page = ceil($returBeliHeader->position / (10));
            } else {
                $returBeliHeader->page = ceil($returBeliHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $returBeliHeader
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
            $returBeliHeader = (new ReturBeliHeader())->processDestroy($id, "DELETE RETUR JUAL HEADER");
            $selected = $this->getPosition($returBeliHeader, $returBeliHeader->getTable(), true);
            $returBeliHeader->position = $selected->position;
            $returBeliHeader->id = $selected->id;
            if ($request->limit == 0) {
                $returBeliHeader->page = ceil($returBeliHeader->position / (10));
            } else {
                $returBeliHeader->page = ceil($returBeliHeader->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $returBeliHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function cekValidasiAksi($id)
    {
        $returBeliHeader = new ReturBeliHeader();
        $returbeliid = ReturBeliHeader::from(DB::raw("returbeliheader"))->where('id', $id)->first();
        // dd($returbeliid);
        $cekdata = $returBeliHeader->cekValidasiAksi($returbeliid->pembelianid);

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