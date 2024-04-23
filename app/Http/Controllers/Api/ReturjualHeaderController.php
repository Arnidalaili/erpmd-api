<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisabledLookupRequest;
use App\Http\Requests\EditingAtRequest;
use App\Models\ReturJualDetail;
use App\Models\ReturJualHeader;
use Illuminate\Support\Facades\DB;

class ReturjualHeaderController extends Controller
{
    /**
     * @ClassName 
     * ReturjualHeaderController
     * @Detail ReturjualDetailController
     */
    public function index()
    {
        $returJualHeader = new ReturJualHeader();
        return response([
            'data' => $returJualHeader->get(),
            'attributes' => [
                'totalRows' => $returJualHeader->totalRows,
                'totalPages' => $returJualHeader->totalPages
            ]
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = ReturJualHeader::findAll($id);
        $detail = ReturJualDetail::getAll($id);
        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('returjualheader')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function default()
    {
        $returJualHeader = new ReturJualHeader();
        return response([
            'status' => true,
            'data' => $returJualHeader->default(),
        ]);
    }

    public function getPenjualanDetail(Request $request) {
        $penjualanid = $request->penjualanid;
        // dd($penjualanid);

        $returJualHeader = new ReturJualHeader();
        return response([
            'data' => $returJualHeader->getPenjualanDetail($penjualanid),
            'penjualanid' => $penjualanid,
            'attributes' => [
                'totalRows' => $returJualHeader->totalRows,
                'totalPages' => $returJualHeader->totalPages
            ]
        ]);
    }

    public function getEditPenjualanDetail(Request $request) 
    {
        $penjualanid = $request->penjualanid;
        $penjualandetailid = $request->penjualandetailid;
        $id = $request->id;

        // dd($penjualandetailid);

        $returJualHeader = new ReturJualHeader();
        return response([
            'data' => $returJualHeader->getEditPenjualanDetail($penjualanid, $id, $penjualandetailid)
        ]);
    }

    public function editingat(Request $request)
    {
        $returJualHeader = new ReturJualHeader();
        return response([
            'data' => $returJualHeader->editingAt($request->id, $request->btn),
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(Request $request)
    {
        // dd('masuk');
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);
        // dd($details);

        // dd($details);
        DB::beginTransaction();
        try {

            $data = (new ReturJualHeader())->processData($details);
            // dd($data);
            /* Store header */
            $returJualHeader = (new ReturJualHeader())->processStore($data);
            /* Set position and page */
            $returJualHeader->position = $this->getPosition($returJualHeader, $returJualHeader->getTable())->position;

            if ($request->limit == 0) {
                $returJualHeader->page = ceil($returJualHeader->position / (10));
            } else {
                $returJualHeader->page = ceil($returJualHeader->position / ($request->limit ?? 10));
            }
            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $returJualHeader
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(Request $request, ReturJualHeader $returJualHeader, $id)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        // dd($details);

        DB::beginTransaction();         
        try {
            $data = (new ReturJualHeader())->processData($details);
            // dd($data);
            /* Store header */
            $returJualHeader = ReturJualHeader::findOrFail($id);
            $returJualHeader = (new ReturJualHeader())->processUpdate($returJualHeader, $data);

            /* Set position and page */
            $returJualHeader->position = $this->getPosition($returJualHeader, $returJualHeader->getTable())->position;
            if ($request->limit == 0) {
                $returJualHeader->page = ceil($returJualHeader->position / (10));
            } else {
                $returJualHeader->page = ceil($returJualHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $returJualHeader
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
            $returJualHeader = (new ReturJualHeader())->processDestroy($id, "DELETE RETUR JUAL HEADER");
            $selected = $this->getPosition($returJualHeader, $returJualHeader->getTable(), true);
            $returJualHeader->position = $selected->position;
            $returJualHeader->id = $selected->id;
            if ($request->limit == 0) {
                $returJualHeader->page = ceil($returJualHeader->position / (10));
            } else {
                $returJualHeader->page = ceil($returJualHeader->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $returJualHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function cekValidasiAksi($id)
    {
        $returJualHeader = new ReturJualHeader();
        $returjualid = ReturJualHeader::from(DB::raw("returjualheader"))->where('id', $id)->first();
        $cekdata = $returJualHeader->cekValidasiAksi($returjualid->penjualanid);

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
