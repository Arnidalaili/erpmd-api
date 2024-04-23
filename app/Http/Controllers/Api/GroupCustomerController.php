<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupCustomerRequest;
use App\Http\Requests\UpdateGroupCustomerRequest;
use App\Http\Requests\DestroyGroupCustomerRequest;
use App\Http\Requests\EditingAtGroupCustomerRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Models\GroupCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class GroupCustomerController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $groupCustomer = new GroupCustomer();
        return response([
            'data' => $groupCustomer->get(),
            'attributes' => [
                'totalRows' => $groupCustomer->totalRows,
                'totalPages' => $groupCustomer->totalPages
            ]
        ]);
    }

    public function default()
    {
        $groupCustomer = new GroupCustomer();
        return response([
            'status' => true,
            'data' => $groupCustomer->default(),
        ]);
    }

    public function show($id)
    {
        $groupCustomer = GroupCustomer::findAll($id);
        return response([
            'status' => true,
            'data' => $groupCustomer
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('groupcustomer')->getColumns();
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
    public function store(StoreGroupCustomerRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];
            
            $groupCustomer = (new GroupCustomer())->processStore($data);
            $groupCustomer->position = $this->getPosition($groupCustomer, $groupCustomer->getTable())->position;
            if ($request->limit==0) {
                $groupCustomer->page = ceil($groupCustomer->position / (10));
            } else {
                $groupCustomer->page = ceil($groupCustomer->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $groupCustomer
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateGroupCustomerRequest $request, GroupCustomer $groupCustomer)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];

            $groupCustomer = (new GroupCustomer())->processUpdate($groupCustomer, $data);
            $groupCustomer->position = $this->getPosition($groupCustomer, $groupCustomer->getTable())->position;
            if ($request->limit==0) {
                $groupCustomer->page = ceil($groupCustomer->position / (10));
            } else {
                $groupCustomer->page = ceil($groupCustomer->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $groupCustomer
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyGroupCustomerRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $groupCustomer = (new GroupCustomer())->processDestroy($id);
            $selected = $this->getPosition($groupCustomer, $groupCustomer->getTable(), true);
            $groupCustomer->position = $selected->position;
            $groupCustomer->id = $selected->id;
            if ($request->limit==0) {
                $groupCustomer->page = ceil($groupCustomer->position / (10));
            } else {
                $groupCustomer->page = ceil($groupCustomer->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $groupCustomer
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    
    public function editingat(EditingAtGroupCustomerRequest $request)
    {
       
        $groupCustomer = new GroupCustomer();
        return response([
            'data' => $groupCustomer->editingAt($request->id, $request->btn),
        ]);
    }
}
