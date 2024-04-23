<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenyesuaianStokHeaderRequest;
use App\Models\PenyesuaianStokDetail;
use App\Models\PenyesuaianStokHeader;
use Illuminate\Support\Facades\DB;

class PenyesuaianStokHeaderController extends Controller
{
    /**
     * @ClassName 
     * PenyesuaianStokHeaderController
     * @Detail PenyesuaianStokDetailController
     */
    public function index()
    {
        $penyesuaianStokHeader = new PenyesuaianStokHeader();
        return response([
            'data' => $penyesuaianStokHeader->get(),
            'attributes' => [
                'totalRows' => $penyesuaianStokHeader->totalRows,
                'totalPages' => $penyesuaianStokHeader->totalPages
            ]
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = PenyesuaianStokHeader::findAll($id);
        $detail = PenyesuaianStokDetail::getAll($id);
        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('penyesuaianstokheader')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function default()
    {
        $penyesuaianStokHeader = new PenyesuaianStokHeader();
        return response([
            'status' => true,
            'data' => $penyesuaianStokHeader->default(),
        ]);
    }

    public function editingat(Request $request)
    {
        $penyesuaianStokHeader = new PenyesuaianStokHeader();
        return response([
            'data' => $penyesuaianStokHeader->editingAt($request->id, $request->btn),
        ]);
    }


    /**
     * @ClassName 
     */
    public function store(StorePenyesuaianStokHeaderRequest $request)
    {
        $details = json_decode($request->detail, true);

        DB::beginTransaction();
        try {

            $data = (new PenyesuaianStokHeader())->processData($details);

            $penyesuaianStokHeader = (new PenyesuaianStokHeader())->processStore($data);

            /* Set position and page */
            $penyesuaianStokHeader->position = $this->getPosition($penyesuaianStokHeader, $penyesuaianStokHeader->getTable())->position;


            if ($request->limit == 0) {
                $penyesuaianStokHeader->page = ceil($penyesuaianStokHeader->position / (10));
            } else {
                $penyesuaianStokHeader->page = ceil($penyesuaianStokHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $penyesuaianStokHeader
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
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
            $data = (new PenyesuaianStokHeader())->processData($details);

            $penyesuaianStokHeader = PenyesuaianStokHeader::findOrFail($id);
            $penyesuaianStokHeader = (new PenyesuaianStokHeader())->processUpdate($penyesuaianStokHeader, $data);
            /* Set position and page */
            $penyesuaianStokHeader->position = $this->getPosition($penyesuaianStokHeader, $penyesuaianStokHeader->getTable())->position;
            if ($request->limit == 0) {
                $penyesuaianStokHeader->page = ceil($penyesuaianStokHeader->position / (10));
            } else {
                $penyesuaianStokHeader->page = ceil($penyesuaianStokHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $penyesuaianStokHeader
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
            $penyesuaianStokHeader = (new PenyesuaianStokHeader())->processDestroy($id, "DELETE PENYESUAIAN STOK HEADER");
            $selected = $this->getPosition($penyesuaianStokHeader, $penyesuaianStokHeader->getTable(), true);
            $penyesuaianStokHeader->position = $selected->position;
            $penyesuaianStokHeader->id = $selected->id;
            if ($request->limit == 0) {
                $penyesuaianStokHeader->page = ceil($penyesuaianStokHeader->position / (10));
            } else {
                $penyesuaianStokHeader->page = ceil($penyesuaianStokHeader->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $penyesuaianStokHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }
}
