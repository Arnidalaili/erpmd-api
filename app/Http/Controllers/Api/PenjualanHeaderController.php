<?php

namespace App\Http\Controllers\Api;

use App\Events\NewNotification;
use App\Events\PenjualanHeaderNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalKacabEditingRequest;
use App\Http\Requests\CekBatalPenjualanRequest;
use App\Http\Requests\CekGeneratePenjualanRequest;
use App\Http\Requests\CheckJumlahQtyReturRequest;
use App\Http\Requests\EditingAtPenjualanHeaderEditallRequest;
use App\Http\Requests\EditingAtPenjualanHeaderRequest;
use App\Http\Requests\GetIndexRangeRequest;
use App\Http\Requests\StorePenjualanHeaderRequest;
use App\Http\Requests\UpdatePenjualanHeaderEditAllRequest;
use App\Http\Requests\UpdatePenjualanHeaderRequest;
use App\Models\PenjualanDetail;
use App\Models\PenjualanHeader;
use App\Models\PesananFinalHeader;
use App\Models\ReturJualHeader;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanHeaderController extends Controller
{
    /**
     * @ClassName 
     * PenjualanHeaderController
     * @Detail PenjualanDetailController
     */
    public function index(Request $request)
    {
        $penjualanHeader = new PenjualanHeader();
        return response([
            'data' => $penjualanHeader->get(),
            'attributes' => [
                'totalRows' => $penjualanHeader->totalRows,
                'totalPages' => $penjualanHeader->totalPages
            ]
        ]);
    }


    /**
     * @ClassName 
     */
    public function store(StorePenjualanHeaderRequest $request)
    {
        // dd($request->detail);
        $detailData = json_decode($request->detail, true);

        // dd($detailData);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {
            $data = (new PenjualanHeader())->processData($details);

            // dd($data);
            /* Store header */
            $penjualanHeader = (new PenjualanHeader())->processStore($data);
            /* Set position and page */
            $penjualanHeader->position = $this->getPosition($penjualanHeader, $penjualanHeader->getTable())->position;

            if ($request->limit == 0) {
                $penjualanHeader->page = ceil($penjualanHeader->position / (10));
            } else {
                $penjualanHeader->page = ceil($penjualanHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $penjualanHeader
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function generatepenjualan(CekGeneratePenjualanRequest $request)
    {
        // dd('masuk');
        $penjualanheaderId = $request->pesananfinalheaderid;
        $penjualanHeader = new PenjualanHeader();

        DB::beginTransaction();
        try {

            $filter = $penjualanHeader->getfilterTglPengiriman();
            if ($filter) {
                // dd($filter);
                return response([
                    'status' => true,
                    'message' => 'Hapus pembelian terlebih dahulu'
                ]);
            } else {
                $data = $penjualanHeader->approval($penjualanheaderId);
                DB::commit();
                return response()->json([
                    'status' => false,
                    'message' => 'Berhasil disimpan',
                    'data' => $data
                ], 201);
            }
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function batalpenjualan(CekBatalPenjualanRequest $request)
    {
        
        $penjualanheaderId = $request->pesananfinalheaderid;
        $penjualanHeader = new PenjualanHeader();

        DB::beginTransaction();
        try {

            $cekPembelian = $penjualanHeader->cekPembelian($penjualanheaderId);

            $cekPembelianCleaned = array_filter($cekPembelian);

            if (!empty($cekPembelianCleaned)) {
                return response([
                    'status' => true,
                    'message' => 'tidak bisa membatalkan penjualan karena sudah ada di pembelian'
                ]);
            } else {

                $data = $penjualanHeader->prosessHapusPenjualan($penjualanheaderId);

                DB::commit();
                return response()->json([
                    'status' => false,
                    'message' => 'Berhasil disimpan',
                    'data' => $data
                ], 201);
            }
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
        $data = PenjualanHeader::findAll($id);
        $detail = PenjualanDetail::getAll($id);
        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }

    /**
     * @ClassName 
     */
    public function update(UpdatePenjualanHeaderRequest $request, PenjualanHeader $penjualanheader)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {
            $data = (new PenjualanHeader())->processData($details);
            /* Store header */
            $penjualanHeader = $penjualanheader;
            $penjualanHeader = (new PenjualanHeader())->processUpdate($penjualanHeader, $data);
            if ($penjualanheader->pesananfinalid != 0) {
                $checkNobuktiPembelian = DB::table("penjualanheader")
                    ->select(
                        "pesananfinaldetail.id",
                        "pesananfinaldetail.nobuktipembelian",
                    )
                    ->join('pesananfinalheader', 'pesananfinalheader.id', 'penjualanheader.pesananfinalid')
                    ->join('pesananfinaldetail', 'pesananfinalheader.id', 'pesananfinaldetail.pesananfinalid')
                    ->where("penjualanheader.id", $penjualanheader->id)
                    ->first();

                if ($checkNobuktiPembelian->nobuktipembelian != '') {
                    $hpp = (new PenjualanHeader())->processUpdateHpp($penjualanHeader['penjualanHeader'], $penjualanHeader['resultRetur']);
                }
            } else {
                $hpp = (new PenjualanHeader())->processUpdateHpp($penjualanHeader['penjualanHeader'], $penjualanHeader['resultRetur']);
            }

            /* Set position and page */
            $penjualanHeader['penjualanHeader']->position = $this->getPosition($penjualanHeader['penjualanHeader'], $penjualanHeader['penjualanHeader']->getTable())->position;
            if ($request->limit == 0) {
                $penjualanHeader['penjualanHeader']->page = ceil($penjualanHeader['penjualanHeader']->position / (10));
            } else {
                $penjualanHeader['penjualanHeader']->page = ceil($penjualanHeader['penjualanHeader']->position / ($request->limit ?? 10));
            }

            // dd($penjualanHeader['penjualanHeader']);

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $penjualanHeader['penjualanHeader']
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
            $penjualanHeader = (new PenjualanHeader())->processDestroy($id, "DELETE PENJUALAN HEADER");
            $selected = $this->getPosition($penjualanHeader, $penjualanHeader->getTable(), true);
            $penjualanHeader->position = $selected->position;
            $penjualanHeader->id = $selected->id;
            if ($request->limit == 0) {
                $penjualanHeader->page = ceil($penjualanHeader->position / (10));
            } else {
                $penjualanHeader->page = ceil($penjualanHeader->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $penjualanHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('penjualanheader')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function default()
    {
        $pesanan = new PenjualanHeader();
        return response([
            'status' => true,
            'data' => $pesanan->default(),
        ]);
    }

    public function cekValidasi(Request $request, $id)
    {
        $penjualanHeader = new PenjualanHeader();
        $penjualan = PenjualanHeader::from(DB::raw("penjualanheader"))->where('id', $id)->first();
        // dd($penjualan);
        $cekdata = $penjualanHeader->cekValidasi($penjualan->nobukti, $penjualan->id, $penjualan->pesananfinalid);

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
        // dd('test');
        $penjualanHeaders = new PenjualanHeader();
        $penjualanHeader = PenjualanHeader::from(DB::raw("penjualanheader"))->where('id', $id)->first();
        // dd($penjualanHeader);
        $cekdata = $penjualanHeaders->cekValidasiAksi($penjualanHeader->pesananfinalid, $id);

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

    // public function editingat(EditingAtPenjualanHeaderRequest $request)
    // {
    //     // dd('mausk');
    //     $PenjualanHeader = PenjualanHeader::find($request->id);

    //     $btn = $request->btn;



    //     // $today = date('Y-m-d', strtotime($request->date));

    //     // $penjualanEditAll = DB::table("penjualanheader")->from(DB::raw("penjualanheader"))->where('tglbukti', $today)->first();

    //     // $idPenjualanToday = $penjualanEditAll->id;


    //     if ($btn == 'EDIT') {
    //         $param = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'PENJUALAN HEADER BUKTI')->first();
    //         $memo = json_decode($param->memo, true);
    //         $waktu = $memo['BATASWAKTUEDIT'];

    //         $user = auth('api')->user()->name;
    //         $editingat = new DateTime(date('Y-m-d H:i:s', strtotime($PenjualanHeader->editingat)));

    //         $diffNow = $editingat->diff(new DateTime(date('Y-m-d H:i:s')));
    //     }

    //     $PenjualanHeader = new PenjualanHeader();
    //     return response([
    //         'data' => $PenjualanHeader->editingAt($request->id, $request->btn),
    //     ]);
    // }

    public function editingat(EditingAtPenjualanHeaderRequest $request)
    {
        $pesananFinalHeader = new PenjualanHeader();
        return response([
            'data' => $pesananFinalHeader->editingAt($request->id, $request->btn),
        ]);
    }

    public function editallEditingat(EditingAtPenjualanHeaderEditallRequest $request)
    {

        $today = date('Y-m-d', strtotime($request->date));
        // $btn = $request->btn;

        // $penjualanEditAll = DB::table("penjualanheader")->from(DB::raw("penjualanheader"))->where('tglbukti', $today)->get();


        // // $idPenjualanToday = $penjualanEditAll->id;

        // if ($btn == 'EDIT ALL') {
        //     foreach ($penjualanEditAll as $penjualan) {

        //         $param = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'PENJUALAN HEADER BUKTI')->first();
        //         $memo = json_decode($param->memo, true);
        //         $waktu = $memo['BATASWAKTUEDIT'];

        //         $user = auth('api')->user()->name;
        //         $editingat = new DateTime(date('Y-m-d H:i:s', strtotime($penjualan->id)));


        //         $diffNow = $editingat->diff(new DateTime(date('Y-m-d H:i:s')));


        //     }
        // }

        $PenjualanHeader = new PenjualanHeader();
        return response([
            'data' => $PenjualanHeader->editingateditall($request->btn, $today),
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
            ->where("acos.class", 'penjualanheader')
            ->where("acos.method", 'approvaleditingby')
            ->where('acl.role_id', $query->role_id)
            ->first();
        $edit = '';
        if ($cekAcl != '') {
            $status = true;
            $edit = (new PenjualanHeader())->editingAt($request->id, 'EDIT');
        } else {
            $cekUserAcl = DB::table("acos")->from(DB::raw("acos"))
                ->select(DB::raw("acos.id,acos.class,acos.method"))
                ->join(DB::raw("useracl"), 'acos.id', 'useracl.aco_id')
                ->where("acos.class", 'penjualanheader')
                ->where("acos.method", 'approvaleditingby')
                ->where('useracl.user_id', $query->id)
                ->first();
            if ($cekUserAcl != '') {
                $status = true;

                $edit = (new PenjualanHeader())->editingAt($request->id, 'EDIT');
            } else {
                $status = false;
            }
        }
        if ($status) {

            event(new PenjualanHeaderNotification(json_encode([
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

    public function approvalkacabeditall(ApprovalKacabEditingRequest $request)
    {
        $today = date('Y-m-d', strtotime($request->date));
        $query = DB::table("user")->from(DB::raw("user"))
            ->select('userrole.role_id', DB::raw("user.id"))
            ->join(DB::raw("userrole"), DB::raw("user.id"), 'userrole.user_id')
            ->where('user.user', request()->username)->first();

        $cekAcl = DB::table("acos")->from(DB::raw("acos"))
            ->select(DB::raw("acos.id,acos.class,acos.method"))
            ->join(DB::raw("acl"), 'acos.id', 'acl.aco_id')
            ->where("acos.class", 'penjualanheader')
            ->where("acos.method", 'approvaleditingby')
            ->where('acl.role_id', $query->role_id)
            ->first();
        $edit = '';
        if ($cekAcl != '') {
            $status = true;

            $edit = (new PenjualanHeader())->editingateditall('EDIT ALL', $today);
        } else {
            $cekUserAcl = DB::table("acos")->from(DB::raw("acos"))
                ->select(DB::raw("acos.id,acos.class,acos.method"))
                ->join(DB::raw("useracl"), 'acos.id', 'useracl.aco_id')
                ->where("acos.class", 'penjualanheader')
                ->where("acos.method", 'approvaleditingby')
                ->where('useracl.user_id', $query->id)
                ->first();
            if ($cekUserAcl != '') {
                $status = true;

                $edit = (new PenjualanHeader())->editingateditall('EDIT ALL', $today);
            } else {
                $status = false;
            }
        }
        if ($status) {
            event(new PenjualanHeaderNotification(json_encode([
                'message' => "FORM INI SUDAH TIDAK BISA DIEDIT. SEDANG DIEDIT OLEH " . $edit['editingby'],
                'olduser' => $edit['oldeditingby'],
                'user' => $edit['editingby'],

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
        $penjualanHeader = new PenjualanHeader();
        $data = $penjualanHeader->findEditAll();


        return response([
            'status' => true,
            'data' => $data,
            'attributes' => [
                'totalRows' => $penjualanHeader->totalRows,
                'totalPages' => $penjualanHeader->totalPages,
                'limit' => 10
            ]
        ]);
    }

    public function processeditall(Request $request)
    {
        $allData = json_decode($request->data, true);

        $dataPenjualan = array_values($allData);

        DB::beginTransaction();
        try {
            $penjualanHeader = (new PenjualanHeader())->processEditAll($dataPenjualan);
            // dd($penjualanHeader);
            $kosong = false;
            $allBool = [];
            $pesananfinalid = false;

            $i = 0;
            foreach ($penjualanHeader['penjualanHeader'] as $penjualan) {
                // dump($penjualan['pesananfinalid']);
                if ($penjualan['pesananfinalid'] != 0) {
                    $checkNobuktiPembelian = DB::table("penjualanheader")
                        ->select(
                            "pesananfinaldetail.id",
                            "pesananfinaldetail.nobuktipembelian",
                        )
                        ->join('pesananfinalheader', 'pesananfinalheader.id', 'penjualanheader.pesananfinalid')
                        ->join('pesananfinaldetail', 'pesananfinalheader.id', 'pesananfinaldetail.pesananfinalid')
                        ->first();
                    // dump($checkNobuktiPembelian);
                    if ($checkNobuktiPembelian->nobuktipembelian == '') {
                        $kosong = true;
                    }
                } else {
                    $kosong = false;
                }
                // dd($kosong,$penjualanHeader['penjualanHeader']);

                // $allBool[$i]['bolean'] = $kosong;
                // $allBool[$i]['penjualan'] = $penjualan;

                if ($kosong) {
                   $allBool[0]['bolean']= true;
                   $allBool[0]['penjualan'][]= $penjualan;
                }else{
                   $allBool[1]['bolean']= false;
                   $allBool[1]['penjualan'][]= $penjualan;
                }

                $i++;
            }
           $allBool = array_values($allBool);

        //    dd($allBool);

            foreach ($allBool as $boll) {
                // dump($boll['bolean']);
                if (!$boll['bolean']) {
                    $hpp = (new PenjualanHeader())->processEditHpp($boll['penjualan'], $penjualanHeader['resultRetur']);
                }
            }
          
            // $hpp = (new PenjualanHeader())->processUpdateHpp($penjualanHeader['penjualanHeader'], $penjualanHeader['resultRetur']);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Berhasil di edit all',
                'data' => $penjualanHeader['penjualanHeader']
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function checkusereditall(CheckJumlahQtyReturRequest $request)
    {
        // dd('masuk');
        // $today = date('Y-m-d', strtotime($request->date));
        // $edit = (new PenjualanHeader())->editingateditall('EDIT ALL', $today);

        // event(new PenjualanHeaderNotification(json_encode([
        //     'message' => "FORM INI SUDAH TIDAK BISA DIEDIT. SEDANG DIEDIT OLEH " . $edit['editingby'],
        //     'olduser' => $edit['oldeditingby'],
        //     'user' => $edit['editingby'],

        // ])));

        return true;
    }

    public function cekjumlahqtyretur(Request $request)
    {
        $penjualanHeader = new PenjualanHeader();
        $getQty = $penjualanHeader->getSumQty();

        return response([
            'data' => $getQty
        ]);
    }

    public function disabledqtyretur($id)
    {
        $penjualanHeader = new PenjualanHeader();

        $check = $penjualanHeader->disabledQtyRetur($id);

        return response([
            'check' => $check
        ]);
    }

    public function disabledqtyretureditall(Request $request)
    {
        $dataPenjualan = json_decode($request->data, true);

        $penjualanHeader = new PenjualanHeader();

        //  dd($dataPenjualan);
        if ($dataPenjualan != []) {
            $check = $penjualanHeader->disabledQtyReturEditALl($dataPenjualan);
        }

        // dd($check);

        return response([
            'check' => $check ?? ''
        ]);
    }

    public function cekvalidasieditall()
    {
    }

    /**
     * @ClassName 
     */
    public function invoice($id)
    {
        $invoiceheader = new PenjualanHeader();
        return response([
            'data' => $invoiceheader->getInvoice($id)
        ]);
    }

    /**
     * @ClassName 
     */
    public function reportProfit()
    {
        // dd('test');
        $reportProfitHeader = new PenjualanHeader();
        $reportProfit = $reportProfitHeader->getReportProfit();

        // dd($reportProfit);
        $response = [
            'dari' => $reportProfit['dari'],
            'sampai' => $reportProfit['sampai'],
            'data' => $reportProfit['data']
        ];

        return response()->json($response);
    }

    public function reportProfitDetail()
    {
      
        $reportProfitDetail = new PenjualanHeader();
        $reportProfit = $reportProfitDetail->getReportProfitDetail();

        // dd($reportProfit);
        $response = [
            'dari' => $reportProfit['dari'],
            'sampai' => $reportProfit['sampai'],
            'data' => $reportProfit['data']
        ];

        return response()->json($response);
    }

    public function cekMaxQty(Request $request)
    {
        $penjualanHeader = new PenjualanHeader();
        $setQty = $penjualanHeader->cekMaxQty();

        return response([
            'data' => $setQty
        ]);
    }
}
