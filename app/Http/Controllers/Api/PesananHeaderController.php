<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalKacabEditingRequest;
use App\Http\Requests\EditingAtPesananHeaderRequest;
use App\Http\Requests\StorePesananHeaderRequest;
use App\Http\Requests\UpdatePesananHeaderRequest;
use App\Models\PesananDetail;
use App\Models\PesananHeader;
use Illuminate\Http\Request;
use App\Events\NewNotification;
use Illuminate\Support\Facades\DB;

class PesananHeaderController extends Controller
{
    /**
     * @ClassName 
     * PesananHeaderController
     * @Detail PesananDetailController
     */
    public function index()
    {
        $pesananHeader = new PesananHeader();


        return response([
            'data' => $pesananHeader->get(),
            'attributes' => [
                'totalRows' => $pesananHeader->totalRows,
                'totalPages' => $pesananHeader->totalPages
            ]
        ]);
    }

   
    /**
     * @ClassName 
     */
    public function store(StorePesananHeaderRequest $request)
    {

        $details = json_decode($request->detail, true);


        DB::beginTransaction();
        try {
            
            $data = (new PesananHeader())->processData($details);
        
            /* Store header */
            $pesananHeader = (new PesananHeader())->processStore($data);
        
            /* Set position and page */
            $pesananHeader->position = $this->getPosition($pesananHeader, $pesananHeader->getTable())->position;

            if ($request->limit==0) {
                $pesananHeader->page = ceil($pesananHeader->position / (10));
            } else {
                $pesananHeader->page = ceil($pesananHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pesananHeader
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
      
        $data = PesananHeader::findAll($id);
        $detail = PesananDetail::getAll($id);


        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }

    

     /**
     * @ClassName 
     */
    public function update(UpdatePesananHeaderRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = [
                "tglbukti" => $request->tglbukti,
                "customerid" => $request->customerid,
                "alamatpengiriman" => $request->alamatpengiriman,
                "tglpengiriman" => $request->tglpengiriman,
                "keterangan" => $request->keterangan,
                "status" => $request->status,
                "productid" => $request->productid,
                "qty" => $request->qty,
                "keterangandetail" => $request->keterangandetail, 
               
            ];

             /* Store header */
             $pesananHeader = PesananHeader::findOrFail($id);


            /* Store header */
            $pesananHeader = (new PesananHeader())->processUpdate($pesananHeader, $data);

            /* Set position and page */
            $pesananHeader->position = $this->getPosition($pesananHeader, $pesananHeader->getTable())->position;
            if ($request->limit==0) {
                $pesananHeader->page = ceil($pesananHeader->position / (10));
            } else {
                $pesananHeader->page = ceil($pesananHeader->position / ($request->limit ?? 10));
            }
           
            

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pesananHeader
            ], 201);    
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy($id)
    {
        //
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('pesananheader')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function default()
    {

      
        $pesanan = new PesananHeader();
        return response([
            'status' => true,
            'data' => $pesanan->default(),
        ]);
    }

     /**
     * @ClassName 
     */
    public function report($id)
    {

        $pesananHeader = new PesananHeader();
        return response([
            'data' => $pesananHeader->getReport($id)
        ]);
    }

    public function editingat(EditingAtPesananHeaderRequest $request)
    {
        $pesananFinalHeader = new PesananHeader();
        return response([
            'data' => $pesananFinalHeader->editingAt($request->id, $request->btn),
        ]);
    }

    /**
     * @ClassName 
     */
    public function approvaleditingby()
    {
    }

    public function approvalKacab(ApprovalKacabEditingRequest $request)
    {
        $query = DB::table("user")->from(DB::raw("user"))
            ->select('userrole.role_id', DB::raw("user.id"))
            ->join(DB::raw("userrole"), DB::raw("user.id"), 'userrole.user_id')
            ->where('user.user', request()->username)->first();

        $cekAcl = DB::table("acos")->from(DB::raw("acos"))
            ->select(DB::raw("acos.id,acos.class,acos.method"))
            ->join(DB::raw("acl"), 'acos.id', 'acl.aco_id')
            ->where("acos.class", 'penerimaangiroheader')
            ->where("acos.method", 'approvaleditingby')
            ->where('acl.role_id', $query->role_id)
            ->first();
        $edit = '';
        if ($cekAcl != '') {
            $status = true;
            $edit = (new PesananHeader())->editingAt($request->id, 'EDIT');
        } else {
            $cekUserAcl = DB::table("acos")->from(DB::raw("acos"))
                ->select(DB::raw("acos.id,acos.class,acos.method"))
                ->join(DB::raw("useracl with (readuncommitted)"), 'acos.id', 'useracl.aco_id')
                ->where("acos.class", 'penerimaangiroheader')
                ->where("acos.method", 'approvaleditingby')
                ->where('useracl.user_id', $query->id)
                ->first();
            if ($cekUserAcl != '') {
                $status = true;

                $edit = (new PesananHeader())->editingAt($request->id, 'EDIT');
            } else {
                $status = false;
            }
        }
        if ($status) {


            event(new NewNotification(json_encode([
                'message' => "FORM INI SUDAH TIDAK BISA DIEDIT. SEDANG DIEDIT OLEH ".$edit->editing_by,
                'olduser' => $edit->oldeditingby,
                'user' => $edit->editing_by,
                'id' => $request->id

            ])));
        }

        return response([
            'status' => $status,
            'data' => $edit
        ]);
    }
}
