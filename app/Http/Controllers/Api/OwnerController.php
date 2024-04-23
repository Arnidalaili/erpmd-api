<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerRequest;
use App\Http\Requests\UpdateOwnerRequest;
use App\Http\Requests\DestroyOwnerRequest;
use App\Http\Requests\EditingAtOwnerRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Models\Owner;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $owner = new Owner();

      
        return response([
            'data' => $owner->get(),
            'attributes' => [
                'totalRows' => $owner->totalRows,
                'totalPages' => $owner->totalPages
            ]
        ]);
    }

    public function default()
    {
        $owner = new Owner();
        return response([
            'status' => true,
            'data' => $owner->default(),
        ]);
    }

    public function show($id)
    {
        $owner = Owner::findAll($id);
        return response([
            'status' => true,
            'data' => $owner
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('owner')->getColumns();
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
    public function store(StoreOwnerRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'nama2' => $request->nama2,
                'telepon' => $request->telepon,
                'alamat' => $request->alamat,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];
            
            $owner = (new Owner())->processStore($data);
            $owner->position = $this->getPosition($owner, $owner->getTable())->position;
            if ($request->limit==0) {
                $owner->page = ceil($owner->position / (10));
            } else {
                $owner->page = ceil($owner->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $owner
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateOwnerRequest $request, Owner $owner)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'nama2' => $request->nama2,
                'telepon' => $request->telepon,
                'alamat' => $request->alamat,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];

            $owner = (new Owner())->processUpdate($owner, $data);
            $owner->position = $this->getPosition($owner, $owner->getTable())->position;
            if ($request->limit==0) {
                $owner->page = ceil($owner->position / (10));
            } else {
                $owner->page = ceil($owner->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $owner
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $owner = (new Owner())->processDestroy($id);
            $selected = $this->getPosition($owner, $owner->getTable(), true);
            $owner->position = $selected->position;
            $owner->id = $selected->id;
            if ($request->limit==0) {
                $owner->page = ceil($owner->position / (10));
            } else {
                $owner->page = ceil($owner->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $owner
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function editingat(EditingAtOwnerRequest $request)
    {
       
        $owner = new Owner();
        return response([
            'data' => $owner->editingAt($request->id, $request->btn),
        ]);
    }
}
