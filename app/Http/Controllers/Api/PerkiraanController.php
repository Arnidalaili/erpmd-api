<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyPerkiraanRequest;
use App\Http\Requests\EditingAtPerkiraanRequest;
use App\Http\Requests\StorePerkiraanRequest;
use App\Http\Requests\UpdatePerkiraanRequest;
use App\Models\Perkiraan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PerkiraanController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $perkiraan = new Perkiraan();
        return response([
            'data' => $perkiraan->get(),
            'attributes' => [
                'totalRows' => $perkiraan->totalRows,
                'totalPages' => $perkiraan->totalPages
            ]
        ]);
    }

    public function default()
    {
        $perkiraan = new Perkiraan();
        return response([
            'status' => true,
            'data' => $perkiraan->default(),
        ]);
    }

    public function show($id)
    {
        $perkiraan = Perkiraan::findAll($id);
        return response([
            'status' => true,
            'data' => $perkiraan
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('perkiraan')->getColumns();
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
    public function store(StorePerkiraanRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'seqno' => $request->seqno,
                'nama' => $request->nama,
                'operator' => $request->operator,
                'keterangan' => $request->keterangan,
                'groupperkiraan' => $request->groupperkiraan,
                'statusperkiraan' => $request->statusperkiraan,
                'status' => $request->status,
            ];

            $perkiraan = (new Perkiraan())->processStore($data);
            $perkiraan->position = $this->getPosition($perkiraan, $perkiraan->getTable())->position;
            if ($request->limit == 0) {
                $perkiraan->page = ceil($perkiraan->position / (10));
            } else {
                $perkiraan->page = ceil($perkiraan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $perkiraan
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdatePerkiraanRequest $request, Perkiraan $perkiraan)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'seqno' => $request->seqno,
                'nama' => $request->nama,
                'operator' => $request->operator,
                'keterangan' => $request->keterangan,
                'groupperkiraan' => $request->groupperkiraan,
                'statusperkiraan' => $request->statusperkiraan,
                'status' => $request->status,
            ];

            $perkiraan = (new Perkiraan())->processUpdate($perkiraan, $data);
            $perkiraan->position = $this->getPosition($perkiraan, $perkiraan->getTable())->position;
            if ($request->limit == 0) {
                $perkiraan->page = ceil($perkiraan->position / (10));
            } else {
                $perkiraan->page = ceil($perkiraan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $perkiraan
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyPerkiraanRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $perkiraan = (new Perkiraan())->processDestroy($id);
            $selected = $this->getPosition($perkiraan, $perkiraan->getTable(), true);
            $perkiraan->position = $selected->position;
            $perkiraan->id = $selected->id;
            if ($request->limit == 0) {
                $perkiraan->page = ceil($perkiraan->position / (10));
            } else {
                $perkiraan->page = ceil($perkiraan->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $perkiraan
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function editingat(EditingAtPerkiraanRequest $request)
    {
        $perkiraan = new Perkiraan();
        return response([
            'data' => $perkiraan->editingAt($request->id, $request->btn),
        ]);
    }

    public function cekValidasiAksi($id)
    {
        $perkiraan = new Perkiraan();
        $id = Perkiraan::from(DB::raw("perkiraan"))->where('id', $id)->first();
        $cekdata = $perkiraan->cekValidasiAksi($id->id);

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
