<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePelunasanHutangHeaderRequest;
use App\Models\Hutang;
use App\Models\PelunasanHutangDetail;
use App\Models\PelunasanHutangHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelunasanHutangHeaderController extends Controller
{
    /**
     * @ClassName 
     * PelunasanHutangHeaderController
     * @Detail PelunasanHutangDetailController
     */
    public function index()
    {
        $pelunasanHutangHeader = new PelunasanHutangHeader();
        return response([
            'data' => $pelunasanHutangHeader->get(),
            'attributes' => [
                'totalRows' => $pelunasanHutangHeader->totalRows,
                'totalPages' => $pelunasanHutangHeader->totalPages
            ]
        ]);
    }

    public function default()
    {
        $pelunasanHutangHeader = new PelunasanHutangHeader();
        return response([
            'status' => true,
            'data' => $pelunasanHutangHeader->default(),
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = PelunasanHutangHeader::findAll($id);
        $detail = PelunasanHutangDetail::getAll($id);
        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('pelunasanhutangheader')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }
        return response([
            'data' => $data
        ]);
    }

    public function getHutang(Request $request)
    {
        $supplierid = $request->supplierid;

        $hutang = new Hutang();
        return response([
            'data' => $hutang->getHutang($supplierid),
            'supplierid' => $supplierid,
            'attributes' => [
                'totalRows' => $hutang->totalRows,
                'totalPages' => $hutang->totalPages
            ]
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(StorePelunasanHutangHeaderRequest $request)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {
            $data = (new PelunasanHutangHeader())->processData($details);
            /* Store header */
            $pelunasanHutangHeader = (new pelunasanHutangHeader())->processStore($data);

            /* Set position and page */
            $pelunasanHutangHeader->position = $this->getPosition($pelunasanHutangHeader, $pelunasanHutangHeader->getTable())->position;

            if ($request->limit == 0) {
                $pelunasanHutangHeader->page = ceil($pelunasanHutangHeader->position / (10));
            } else {
                $pelunasanHutangHeader->page = ceil($pelunasanHutangHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pelunasanHutangHeader
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(Request $request, PelunasanHutangHeader $pelunasanHutangHeader, $id)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {
            /* Store header */
            $data = (new PelunasanHutangHeader())->processData($details);

            $pelunasanHutangHeader = PelunasanHutangHeader::findOrFail($id);
            $pelunasanHutangHeader = (new PelunasanHutangHeader())->processUpdate($pelunasanHutangHeader, $data);
            /* Set position and page */
            $pelunasanHutangHeader->position = $this->getPosition($pelunasanHutangHeader, $pelunasanHutangHeader->getTable())->position;
            if ($request->limit == 0) {
                $pelunasanHutangHeader->page = ceil($pelunasanHutangHeader->position / (10));
            } else {
                $pelunasanHutangHeader->page = ceil($pelunasanHutangHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pelunasanHutangHeader
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
            $pelunasanHutangHeader = (new PelunasanHutangHeader())->processDestroy($id, "DELETE PELUNASAN HUTANG HEADER");
            $selected = $this->getPosition($pelunasanHutangHeader, $pelunasanHutangHeader->getTable(), true);
            $pelunasanHutangHeader->position = $selected->position;
            $pelunasanHutangHeader->id = $selected->id;
            if ($request->limit == 0) {
                $pelunasanHutangHeader->page = ceil($pelunasanHutangHeader->position / (10));
            } else {
                $pelunasanHutangHeader->page = ceil($pelunasanHutangHeader->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $pelunasanHutangHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function getEditPelunasanHutangHeader(Request $request)
    {
        $supplierid = $request->supplierid;
        $id = $request->id;

        $pelunasanHutangHeader = new PelunasanHutangHeader();
        return response([
            'data' => $pelunasanHutangHeader->getEditPelunasanHutangHeader($supplierid, $id)
        ]);
    }
}
