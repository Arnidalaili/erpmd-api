<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePelunasanPiutangHeaderRequest;
use App\Models\PelunasanPiutangDetail;
use App\Models\PelunasanPiutangHeader;
use App\Models\Piutang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelunasanPiutangHeaderController extends Controller
{
    /**
     * @ClassName 
     * PesananFinalHeaderController
     * @Detail PelunasanPiutangDetailController
     */
    public function index()
    {
        $pesananFinalHeader = new PelunasanPiutangHeader();
        return response([
            'data' => $pesananFinalHeader->get(),
            'attributes' => [
                'totalRows' => $pesananFinalHeader->totalRows,
                'totalPages' => $pesananFinalHeader->totalPages
            ]
        ]);
    }


    /**
     * @ClassName 
     */
    public function store(StorePelunasanPiutangHeaderRequest $request)
    {
        // dd('test');
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {
            $data = (new PelunasanPiutangHeader())->processData($details);
            // dd($data);
            /* Store header */
            $pelunasanPiutangHeader = (new PelunasanPiutangHeader())->processStore($data);

            /* Set position and page */
            $pelunasanPiutangHeader->position = $this->getPosition($pelunasanPiutangHeader, $pelunasanPiutangHeader->getTable())->position;

            if ($request->limit == 0) {
                $pelunasanPiutangHeader->page = ceil($pelunasanPiutangHeader->position / (10));
            } else {
                $pelunasanPiutangHeader->page = ceil($pelunasanPiutangHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pelunasanPiutangHeader
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
        $data = PelunasanPiutangHeader::findAll($id);
        $detail = PelunasanPiutangDetail::getAll($id);

        // dd($data);
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
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {
            /* Store header */
            $data = (new PelunasanPiutangHeader())->processData($details);

            $pelunasanPiutangHeader = PelunasanPiutangHeader::findOrFail($id);
            $pelunasanPiutangHeader = (new PelunasanPiutangHeader())->processUpdate($pelunasanPiutangHeader, $data);
            /* Set position and page */
            $pelunasanPiutangHeader->position = $this->getPosition($pelunasanPiutangHeader, $pelunasanPiutangHeader->getTable())->position;
            if ($request->limit == 0) {
                $pelunasanPiutangHeader->page = ceil($pelunasanPiutangHeader->position / (10));
            } else {
                $pelunasanPiutangHeader->page = ceil($pelunasanPiutangHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pelunasanPiutangHeader
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
            $pelunasanPiutangHeader = (new PelunasanPiutangHeader())->processDestroy($id, "DELETE PELUNASAN PIUTANG HEADER");
            $selected = $this->getPosition($pelunasanPiutangHeader, $pelunasanPiutangHeader->getTable(), true);
            $pelunasanPiutangHeader->position = $selected->position;
            $pelunasanPiutangHeader->id = $selected->id;
            if ($request->limit == 0) {
                $pelunasanPiutangHeader->page = ceil($pelunasanPiutangHeader->position / (10));
            } else {
                $pelunasanPiutangHeader->page = ceil($pelunasanPiutangHeader->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $pelunasanPiutangHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('pelunasanpiutangheader')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function getPiutang(Request $request)
    {
        $customerid = $request->customerid;

        $piutang = new Piutang();
        return response([
            'data' => $piutang->getPiutang($customerid),
            'customerid' => $customerid,
            'attributes' => [
                'totalRows' => $piutang->totalRows,
                'totalPages' => $piutang->totalPages
            ]
        ]);
    }

    public function getEditPelunasanPiutangHeader(Request $request)
    {
        $customerid = $request->customerid;
        $id = $request->id;

        $pelunasanPiutangHeader = new PelunasanPiutangHeader();
        return response([
            'data' => $pelunasanPiutangHeader->getEditPelunasanPiutangHeader($customerid, $id)
        ]);
    }
}
