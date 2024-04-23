<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Http\Requests\DestroyBankRequest;
use App\Http\Requests\EditingAtBankRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Models\Bank;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class BankController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $bank = new Bank();
        return response([
            'data' => $bank->get(),
            'attributes' => [
                'totalRows' => $bank->totalRows,
                'totalPages' => $bank->totalPages
            ]
        ]);
    }

    public function default()
    {
        $bank = new Bank();
        return response([
            'status' => true,
            'data' => $bank->default(),
        ]);
    }

    public function show($id)
    {
        $bank = Bank::findAll($id);
        return response([
            'status' => true,
            'data' => $bank
        ]);
        
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('bank')->getColumns();
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
    public function store(StoreBankRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'tipebank' => $request->tipebank,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];
            
            $bank = (new Bank())->processStore($data);
            $bank->position = $this->getPosition($bank, $bank->getTable())->position;
            if ($request->limit==0) {
                $bank->page = ceil($bank->position / (10));
            } else {
                $bank->page = ceil($bank->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $bank
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateBankRequest $request, Bank $bank)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'tipebank' => $request->tipebank,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];

            $bank = (new Bank())->processUpdate($bank, $data);
            $bank->position = $this->getPosition($bank, $bank->getTable())->position;
            if ($request->limit==0) {
                $bank->page = ceil($bank->position / (10));
            } else {
                $bank->page = ceil($bank->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $bank
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyBankRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $bank = (new Bank())->processDestroy($id);
            $selected = $this->getPosition($bank, $bank->getTable(), true);
            $bank->position = $selected->position;
            $bank->id = $selected->id;
            if ($request->limit==0) {
                $bank->page = ceil($bank->position / (10));
            } else {
                $bank->page = ceil($bank->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $bank
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function editingat(EditingAtBankRequest $request)
    {
       
        $bank = new Bank();
        return response([
            'data' => $bank->editingAt($request->id, $request->btn),
        ]);
    }
}
