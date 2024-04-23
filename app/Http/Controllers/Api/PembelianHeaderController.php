<?php

namespace App\Http\Controllers\Api;

use App\Events\PembelianHeaderNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalKacabEditingRequest;
use App\Http\Requests\CekGeneratePembelianRequest;
use App\Http\Requests\CekNobuktiPembelianRequest;
use App\Http\Requests\CreatePembelianRequest;
use App\Http\Requests\DestroyPembelianHeaderRequest;
use App\Http\Requests\EditingAtPembelianHeaderRequest;
use App\Http\Requests\StorePembelianHeaderRequest;
use App\Http\Requests\UpdatePembelianHeaderRequest;
use App\Http\Requests\ValidationHapusPembelianRequest;
use App\Models\PembelianDetail;
use App\Models\PembelianHeader;
use App\Models\PesananPembelianDetail;
use App\Models\TransaksiBelanja;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianHeaderController extends Controller
{
    /**
     * @ClassName 
     * PembelianHeaderController
     * @Detail PembelianDetailController
     */
    public function index()
    {
        $pembelianHeader = new PembelianHeader();
        return response([
            'data' => $pembelianHeader->get(),
            'attributes' => [
                'totalRows' => $pembelianHeader->totalRows,
                'totalPages' => $pembelianHeader->totalPages
            ]
        ]);
    }

    public function default()
    {
        $pembelianHeader = new PembelianHeader();
        return response([
            'status' => true,
            'data' => $pembelianHeader->default(),
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = PembelianHeader::findAll($id);
        $detail = PembelianDetail::getAll($id);
        $pesananDetail = PesananPembelianDetail::getAll($id, $detail);
        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail,
            'pesananDetail' => $pesananDetail
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('pembelianheader')->getColumns();
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
    public function createPembelian(Request $request)
    {
        DB::beginTransaction();
        try {
            $pembelianHeader = new PembelianHeader();
            $tglpengiriman = (new DateTime())->format('Y-m-d');
            $tglpengirimanrequest = date('Y-m-d', strtotime($request->tglpengirimanindex));

            if ($tglpengiriman != $tglpengirimanrequest) {
                return response([
                    'status' => false,
                    'message' => "tidak ada pembelian yang bisa di create."
                ]);
            }

            $query = DB::table('pesananfinalheader')
                ->select(
                    "pesananfinalheader.id",
                    "pesananfinalheader.nobuktipenjualan",
                    "pesananfinalheader.tglpengiriman"
                )
                ->whereDate('pesananfinalheader.tglpengiriman', $tglpengiriman)
                ->where('pesananfinalheader.status', 1)
                ->get();

         

            $nobuktipenjualan = false;
            foreach ($query as $result) {
                if ($result->nobuktipenjualan === "") {
                    $nobuktipenjualan = true;
                    break;
                }
            }

            $pembelian = DB::table('pesananfinaldetail')
                ->select('pesananfinaldetail.id', 'nobuktipembelian')
                ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
                ->whereDate('pesananfinalheader.tglpengiriman', $tglpengiriman)
                ->where('pesananfinalheader.status', 1)
                ->first();
            
            $pembelianNull = DB::table('pesananfinaldetail')
                ->select('pesananfinaldetail.id', 'nobuktipembelian')
                ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
                ->whereDate('pesananfinalheader.tglpengiriman', $tglpengiriman)
                ->where('pesananfinaldetail.nobuktipembelian', "")
                ->where('pesananfinalheader.status', 1)
                ->count();

            $pembelianTidakNull = DB::table('pesananfinaldetail')
                ->select('pesananfinaldetail.id', 'nobuktipembelian')
                ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
                ->whereDate('pesananfinalheader.tglpengiriman', $tglpengiriman)
                ->where('pesananfinalheader.status', 1)
                ->count();

            // dd($pembelianTidakNull == $pembelianNull);

            if ($pembelianTidakNull != $pembelianNull) {
                return response([
                    'status' => false,
                    'message' => 'hapus pembelian terlebih dahulu'
                ]);
            }

            if($pembelian->nobuktipembelian) {
                return response([
                    'status' => false,
                    'message' => 'Pembelian Sudah di Create'
                ]);
            } else if ($nobuktipenjualan) {
                return response([
                    'status' => false,
                    'message' => 'Create Penjualan Terlebih Dahulu'
                ]);
            } else {
                $data = $pembelianHeader->getfilterTglPengiriman($tglpengiriman);
                DB::commit();
                return response([
                    'status' => true,
                    'message' => 'Berhasil disimpan',
                    'data' => $data
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }


    /**
     * @ClassName 
     */
    public function store(StorePembelianHeaderRequest $request)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {
            $data = (new PembelianHeader())->processData($details);
            /* Store header */
            $pembelianHeader = (new PembelianHeader())->processStore($data);

            /* Set position and page */
            $pembelianHeader->position = $this->getPosition($pembelianHeader, $pembelianHeader->getTable())->position;
            // dd($pembelianHeader);

            if ($request->limit == 0) {
                $pembelianHeader->page = ceil($pembelianHeader->position / (10));
            } else {
                $pembelianHeader->page = ceil($pembelianHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pembelianHeader
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdatePembelianHeaderRequest $request, PembelianHeader $pembelianHeader, $id)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {
            $data = (new PembelianHeader())->processData($details);

            /* Store header */
            $pembelianHeader = PembelianHeader::findOrFail($id);
            $pembelianHeader = (new PembelianHeader())->processUpdate($pembelianHeader, $data);

          
            $hpp = (new PembelianHeader())->processUpdateHpp($pembelianHeader['pembelianHeader'], $pembelianHeader['resultRetur']);

            /* Set position and page */
            $pembelianHeader['pembelianHeader']->position = $this->getPosition($pembelianHeader['pembelianHeader'], $pembelianHeader['pembelianHeader']->getTable())->position;
            if ($request->limit == 0) {
                $pembelianHeader['pembelianHeader']->page = ceil($pembelianHeader['pembelianHeader']->position / (10));
            } else {
                $pembelianHeader['pembelianHeader']->page = ceil($pembelianHeader['pembelianHeader']->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pembelianHeader['pembelianHeader']
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyPembelianHeaderRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $pembelianHeader = (new PembelianHeader())->processDestroy($id, "DELETE PEMBELIAN HEADER");
            $selected = $this->getPosition($pembelianHeader, $pembelianHeader->getTable(), true);
            $pembelianHeader->position = $selected->position;
            $pembelianHeader->id = $selected->id;
            if ($request->limit == 0) {
                $pembelianHeader->page = ceil($pembelianHeader->position / (10));
            } else {
                $pembelianHeader->page = ceil($pembelianHeader->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $pembelianHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function cekValidasi($id)
    {
    
        $pembelianHeader = new PembelianHeader();
        $pembelian = PembelianHeader::from(DB::raw("pembelianheader"))->where('id', $id)->first();
       
        $cekdata = $pembelianHeader->cekValidasi($pembelian->nobukti, $pembelian->id);

        // dd($cekdata);

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

    public function cekvalidasiAksi($id)
    {
        $pembelianHeader = new PembelianHeader();
        $nobukti = PembelianHeader::from(DB::raw("pembelianheader"))->where('id', $id)->first();
        $cekdata = $pembelianHeader->cekValidasiAksi($nobukti->nobukti,$id);

        // dd($cekdata);
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

    public function hapusPembelian(ValidationHapusPembelianRequest $request)
    {
        DB::beginTransaction();
        try {

            $tglpengiriman = $request->tglpengiriman;
            $pembelianHeader = new PembelianHeader();
            // dd('test');
            $data = $pembelianHeader->processHapusPembelian($tglpengiriman);

            DB::commit();

            return response([
                'status' => true,
                'message' => 'Pembelian Berhasil dihapus',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function approvaleditingby()
    {
    }

    public function editingat(EditingAtPembelianHeaderRequest $request)
    {
        $pembelianHeader = PembelianHeader::find($request->id);
        $btn = $request->btn;

        if ($btn == 'EDIT') {
            $param = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'PEMBELIAN HEADER BUKTI')->first();
            $memo = json_decode($param->memo, true);
            $waktu = $memo['BATASWAKTUEDIT'];

            $user = auth('api')->user()->name;
            $editingat = new DateTime(date('Y-m-d H:i:s', strtotime($pembelianHeader->editingat)));
            $diffNow = $editingat->diff(new DateTime(date('Y-m-d H:i:s')));
        }

        $pembelianHeader = new PembelianHeader();
        return response([
            'data' => $pembelianHeader->editingAt($request->id, $request->btn),
        ]);
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
            ->where("acos.class", 'pembelianheader')
            ->where("acos.method", 'approvaleditingby')
            ->where('acl.role_id', $query->role_id)
            ->first();
        $edit = '';
        if ($cekAcl != '') {
            $status = true;
            $edit = (new PembelianHeader())->editingAt($request->id, 'EDIT');
        } else {
            $cekUserAcl = DB::table("acos")->from(DB::raw("acos"))
                ->select(DB::raw("acos.id,acos.class,acos.method"))
                ->join(DB::raw("useracl"), 'acos.id', 'useracl.aco_id')
                ->where("acos.class", 'pembelianheader')
                ->where("acos.method", 'approvaleditingby')
                ->where('useracl.user_id', $query->id)
                ->first();
            if ($cekUserAcl != '') {
                $status = true;

                $edit = (new PembelianHeader())->editingAt($request->id, 'EDIT');
            } else {
                $status = false;
            }
        }
        if ($status) {

            event(new PembelianHeaderNotification(json_encode([
                'message' => "FORM INI SUDAH TIDAK BISA DIEDIT. SEDANG DIEDIT OLEH " . $edit->editingby,
                'olduser' => $edit->oldeditingby,
                'user' => $edit->editingby,
                'id' => $request->id

            ])));

            $sent = true;
        }

        return response([
            'status' => $status,
            'data' => $edit,

        ]);
    }

    public function editall()
    {
        // dd('ghgh');
        $pembelianHeader = new PembelianHeader();
        $data = $pembelianHeader->findEditAll();

        $transaksiBelanja = new TransaksiBelanja();
        $databelanja = $transaksiBelanja->getTransaksiBelanja();

        return response([
            'status' => true,
            'data' => $data,
            'databelanja' => $databelanja ?? '',
            'totalCredit' => $pembelianHeader->totalCredit,
            'totalCash' => $pembelianHeader->totalCash,
            'totalPanjar' => $transaksiBelanja->totalPanjar,
            // 'totalBiayaParkir' => $transaksiBelanja->totalBiayaParkir,
            // 'totalBiayaMakan' => $transaksiBelanja->totalBiayaMakan,
            // 'totalBiayaBensin' => $transaksiBelanja->totalBiayaBensin,
            'totalBiaya' => $transaksiBelanja->totalBiaya,
            'totalSisa' => $transaksiBelanja->totalBiaya,
            'attributes' => [
                'totalRows' => $pembelianHeader->totalRows,
                'totalPages' => $pembelianHeader->totalPages
            ]
        ]);
    }

    public function processeditall(Request $request)
    {
        // dd($request->data);
        $data = json_decode($request->data, true);

        $allDataTransaksiBelanja = json_decode($data['transaksibelanja'], true);

        // dd($allDataTransaksiBelanja);
        
        DB::beginTransaction();
        try {
            if ($allDataTransaksiBelanja != []) {
                $dataTransaksiBelanja = array_values($allDataTransaksiBelanja);
        
                $dataFinal = (new TransaksiBelanja())->processData($dataTransaksiBelanja);

                // dd($dataFinal);
                $transaksiBelanja = (new TransaksiBelanja())->processEditAll($dataFinal);
            }

            $pembelianHeader = (new PembelianHeader())->processEditAll($data['pembelian']);


            // dd($pembelianHeader);
                // dd($pembelianHeader);
            $hpp = (new PembelianHeader())->processEditHpp($pembelianHeader['pembelianHeader'], $pembelianHeader['resultRetur']);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Berhasil di edit all',
                'data' => $pembelianHeader['pembelianHeader']
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function cekjumlahqtyretur(Request $request)
    {
        $pembelianHeader = new pembelianHeader();
        $getQty = $pembelianHeader->getSumQty();

        // dd($getQty);
        return response([
            'data' => $getQty
        ]);
    }

    public function cekStokProduct(Request $request)
    {
        // dd('dkhdf');
        $productid = $request->productid;
       
        $pembelianHeader = new PembelianHeader();
        return response([
            'data' => $pembelianHeader->cekStok($productid),
            'message' => 'Jumlah Stok',
        ]);
    }

    public function cekStokProductPembelian(Request $request)
    {
        $productid = $request->productid;
       
        $pembelianHeader = new PembelianHeader();
        return response([
            'data' => $pembelianHeader->cekStokJual($productid),
            'message' => 'Jumlah Stok',
        ]);
    }

    public function disableddelete($id)
    {
        $pembelianHeader = new PembelianHeader();

        $check = $pembelianHeader->disabledDelete($id);

        return response([
            'check' => $check
        ]);
    }

    public function disableddeleteeditall(Request $request)
    {
        $dataPembelian = json_decode($request->data, true);

        $penjualanHeader = new PembelianHeader();

        //  dd($dataPembelian);
        if ($dataPembelian != []) {
            $check = $penjualanHeader->disabledDeleteEditALl($dataPembelian);
        }

        // dd($check);

        return response([
            'check' => $check ?? ''
        ]);
    }
}
