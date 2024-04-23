<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlatBayarRequest;
use App\Http\Requests\UpdateAlatBayarRequest;
use App\Http\Requests\DestroyAlatBayarRequest;
use App\Http\Requests\EditingAtAlatBayarRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Http\Requests\StoreAclRequest;
use App\Models\AlatBayar;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class AlatBayarController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $alatBayar = new AlatBayar();
        return response([
            'data' => $alatBayar->get(),
            'attributes' => [
                'totalRows' => $alatBayar->totalRows,
                'totalPages' => $alatBayar->totalPages
            ]
        ]);
    }

    public function default()
    {
        $alatBayar = new AlatBayar();
        return response([
            'status' => true,
            'data' => $alatBayar->default(),
        ]);
    }

    public function show($id)
    {
        $alatBayar = AlatBayar::findAll($id);
        return response([
            'status' => true,
            'data' => $alatBayar
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('alatbayar')->getColumns();
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
    public function store(StoreAlatBayarRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'bankid' => $request->bankid,
                'status' => $request->status,
            ];
            
            $alatBayar = (new AlatBayar())->processStore($data);
            $alatBayar->position = $this->getPosition($alatBayar, $alatBayar->getTable())->position;
            if ($request->limit==0) {
                $alatBayar->page = ceil($alatBayar->position / (10));
            } else {
                $alatBayar->page = ceil($alatBayar->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $alatBayar
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateAlatBayarRequest $request, AlatBayar $alatBayar)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'bankid' => $request->bankid,
                'status' => $request->status,
            ];

            $alatBayar = (new AlatBayar())->processUpdate($alatBayar, $data);
            $alatBayar->position = $this->getPosition($alatBayar, $alatBayar->getTable())->position;
            if ($request->limit==0) {
                $alatBayar->page = ceil($alatBayar->position / (10));
            } else {
                $alatBayar->page = ceil($alatBayar->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $alatBayar
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyAlatBayarRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $alatBayar = (new AlatBayar())->processDestroy($id);
            $selected = $this->getPosition($alatBayar, $alatBayar->getTable(), true);
            $alatBayar->position = $selected->position;
            $alatBayar->id = $selected->id;
            if ($request->limit==0) {
                $alatBayar->page = ceil($alatBayar->position / (10));
            } else {
                $alatBayar->page = ceil($alatBayar->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $alatBayar
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function editingat(EditingAtAlatBayarRequest $request)
    {
       
        $alatBayar = new AlatBayar();
        return response([
            'data' => $alatBayar->editingAt($request->id, $request->btn),
        ]);
    }
}
