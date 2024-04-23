<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalKacabEditingRequest;
use App\Http\Requests\EditingAtRequest;
use App\Http\Requests\StoreCombainPesananFinalRequest;
use App\Http\Requests\StorePesananFinalRequest;
use App\Http\Requests\UpdatePesananFinalHeaderRequest;
use App\Http\Requests\ValidationHapusPembelianRequest;
use App\Models\PembelianHeader;
use App\Models\PenjualanHeader;
use App\Models\PesananFinalDetail;
use App\Models\PesananFinalHeader;
use Illuminate\Http\Request;
use App\Events\NewNotification;
use App\Http\Requests\EditAllPembelianRequest;
use App\Http\Requests\EditALlPenjualanPesananFinalRequest;
use DateTime;
use Illuminate\Support\Facades\DB;

class PesananFinalHeaderController extends Controller
{
    /**
     * @ClassName 
     * PesananFinalHeaderController
     * @Detail PesananFinalDetailController
     */
    public function index()
    {
        $pesananFinalHeader = new PesananFinalHeader();
        return response([
            'data' => $pesananFinalHeader->get(),
            'attributes' => [
                'totalRows' => $pesananFinalHeader->totalRows,
                'totalPages' => $pesananFinalHeader->totalPages
            ]
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(StorePesananFinalRequest $request)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        DB::beginTransaction();
        try {

            $data = (new PesananFinalHeader())->processData($details);
            /* Store header */
            $pesananHeader = (new PesananFinalHeader())->processStore($data);
            /* Set position and page */
            $pesananHeader->position = $this->getPosition($pesananHeader, $pesananHeader->getTable())->position;

            if ($request->limit == 0) {
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

        $data = PesananFinalHeader::findAll($id);
        $detail = PesananFinalDetail::getAll($id);
        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }

    /**
     * @ClassName 
     */
    public function update(UpdatePesananFinalHeaderRequest $request, PesananFinalHeader $penjualanheader, $id)
    {
        $detailData = json_decode($request->detail, true);

        $details = array_values($detailData);
        DB::beginTransaction();
        try {
            /* Store header */
            $data = (new PesananFinalHeader())->processData($details);

            $pesananFinalHeader = PesananFinalHeader::findOrFail($id);
            $pesananFinalHeader = (new PesananFinalHeader())->processUpdate($pesananFinalHeader, $data);
            /* Set position and page */
            $pesananFinalHeader->position = $this->getPosition($pesananFinalHeader, $pesananFinalHeader->getTable())->position;
            if ($request->limit == 0) {
                $pesananFinalHeader->page = ceil($pesananFinalHeader->position / (10));
            } else {
                $pesananFinalHeader->page = ceil($pesananFinalHeader->position / ($request->limit ?? 10));
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pesananFinalHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function default()
    {
        $pesanan = new PesananFinalHeader();
        return response([
            'status' => true,
            'data' => $pesanan->default(),
        ]);
    }

    /**
     * @ClassName 
     */
    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $data = [
                "nobuktipesanan" => $request->nobuktipesanan,
            ];

            $pesananFinalHeader = (new PesananFinalHeader())->processDestroy($id, "DELETE PESANAN FINAL HEADER", $data);
            $selected = $this->getPosition($pesananFinalHeader, $pesananFinalHeader->getTable(), true);
            $pesananFinalHeader->position = $selected->position;
            $pesananFinalHeader->id = $selected->id;
            if ($request->limit == 0) {
                $pesananFinalHeader->page = ceil($pesananFinalHeader->position / (10));
            } else {
                $pesananFinalHeader->page = ceil($pesananFinalHeader->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'message' => 'Berhasil dihapus',
                'data' => $pesananFinalHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('pesananfinalheader')->getColumns();
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
    public function report($id)
    {
        $pesananFinalHeader = new PesananFinalHeader();
        return response([
            'pesan' => 'laporan success',
            'data' => $pesananFinalHeader->getReport($id),
            'detail' => (new PesananFinalDetail())->get(),
            'attributes' => [
                'totalRows' => $pesananFinalHeader->totalRows,
                'totalPages' => $pesananFinalHeader->totalPages
            ]
        ]);
    }

    /**
     * @ClassName
     */
    public function reportPembelian($id)
    {
    }

    /**
     * @ClassName
     */
    public function reportPembelianAll($id)
    {
    }

    /**
     * @ClassName
     */
    public function unApproval(Request $request)
    {
        $tgl = $request->tglpengiriman;
        return response([
            'message' => 'Berhasil Unapproval',
            'data' => $unApproval = (new PesananFinalHeader())->unApproval($tgl)
        ]);
    }

    /**
     * @ClassName
     */
    public function combain(StoreCombainPesananFinalRequest $request)
    {
        $pesananfinalid = $request->pesananfinalheaderid;
        $pesananFinalHeader = new PesananFinalHeader();
        $data = $pesananFinalHeader->processStoreCombain($pesananfinalid);

        DB::beginTransaction();
        try {
            $pesananHeader = (new PesananFinalHeader())->processStore($data);
            /* Set position and page */
            $pesananHeader->position = $this->getPosition($pesananHeader, $pesananHeader->getTable())->position;

            if ($request->limit == 0) {
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


    public function cekTglCetak(Request $request)
    {
        $data = [
            'tgldari' => $request->dari
        ];
        $pesananfinal = new PesananFinalHeader();
        $query = $pesananfinal->cekTglCetak($data);
        $tglCetak = false;
        foreach ($query as $result) {
            if ($result->tglcetak !== null) {
                $tglCetak = true;
                break;
            }
        }
        if ($tglCetak) {
            return response([
                'status' => false
            ]);
        } else {
            return response([
                'status' => true
            ]);
        }
    }

    public function updateTglCetak(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = [
                'tgldari' => $request->dari
            ];
            $pesananHeader = (new PesananFinalHeader())->updateTglCetak($data);
            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $pesananHeader
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function cekvalidasiAksiDelete($id, Request $request)
    {
        $pesananfinal = new PesananFinalHeader();
        $tglcetak = PesananFinalHeader::from(DB::raw("pesananfinalheader"))->where('id', $id)->first();
        $cekdata = $pesananfinal->cekValidasiAksiDelete($tglcetak->tglcetak, $id);

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

    public function cekvalidasiAksiEdit($id, Request $request)
    {
        $pesananfinal = new PesananFinalHeader();
        $tglcetak = PesananFinalHeader::from(DB::raw("pesananfinalheader"))->where('id', $id)->first();
        $cekdata = $pesananfinal->cekValidasiAksiEdit($tglcetak->tglcetak, $id);

        // dd($cekdata);
        if ($cekdata['kondisi'] == true) {
            $query = DB::table('error')
                ->select(
                    DB::raw("keterangan")
                )
                ->where('kodeerror', '=', $cekdata['kodeerror'])
                ->first();
            // dd($query);

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

    public function acos(Request $request)
    {
        $pesananFinalHeader = new PesananFinalHeader();
        $method = $request->method;
        $username = $request->username;
        $data = $pesananFinalHeader->getAcos($method, $username);
        if ($data == true) {
            return response([
                'data' => $data
            ]);
        } else {
            return response([
                'message' => 'Maaf, Anda Tidak memiliki hak akses'
            ]);
        }
    }

    public function cekProductPesanan(Request $request)
    {
        $data = [
            "id" => $request->id ?? '',
            "tglpengirimanbeli" => $request->tglpengirimanbeli ?? '',
        ];

        $pesananFinalHeader = new PesananFinalHeader();
        $data = $pesananFinalHeader->getProductPesanan($data);
        return $data;
    }

    public function editHargaJual(Request $request)
    {
        $productIds = array_values($request->productid);
        $hargajuals = array_values($request->hargajual);

        $data = [
            "productid" => $productIds,
            "hargajual" => $hargajuals,
        ];

        $pesananFinalHeader = new PesananFinalHeader();
        $data = $pesananFinalHeader->editHargaJual($data);
        return response([
            'message' => 'Harga Jual berhasil di edit'
        ]);
    }

    public function editHargaBeli(Request $request)
    {
        $productIds = array_values($request->productid);
        $hargaBelis = array_values($request->harga);

        $data = [
            "productid" => $productIds,
            "hargabeli" => $hargaBelis,
        ];

        $pesananFinalHeader = new PesananFinalHeader();
        $data = $pesananFinalHeader->editHargaBeli($data);
        return response([
            'message' => 'Harga Beli berhasil di edit'
        ]);
    }


    public function cekValidasiPembelian(ValidationHapusPembelianRequest $request)
    {
        $tglpengiriman = $request->tglpengiriman;
        $pembelian = new PesananFinalHeader();
        $cekdata = $pembelian->cekValidasiPembelian($tglpengiriman);

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

    public function cekValidasiPenjualan(Request $request)
    {
        // dd(isset(count($request->id)));
        if ($request->id) {
            for ($i = 0; $i < count($request->id); $i++) {
           
                $query = DB::table('pesananfinalheader')
                    ->where('id', $request->id[$i])
                    ->first();
    
                $nobuktipenjualan = $query->nobuktipenjualan;
    
                if ($nobuktipenjualan !=  '') {
                    $penjualanHeader = new PenjualanHeader();
                    $penjualan = PenjualanHeader::from(DB::raw("penjualanheader"))->whereIn('pesananfinalid', $request->id)->first();
                    $pesananfinal = new PesananFinalHeader();
                    $cekdata = $pesananfinal->cekValidasiPenjualan($penjualan->nobukti, $penjualan->id);
    
               
    
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
                } else {
                    $query = DB::table('error')
                        ->select(
                            DB::raw("keterangan")
                        )
                        ->where('kodeerror', '=', 'NBBP')
                        ->first();
    
                    $data = [
                        'error' => true,
                        'message' => $query->keterangan,
                        'kodeerror' => 'NBBP',
                        'statuspesan' => 'warning',
                    ];
                    return response($data);
                }
            }
        }else{
            $query = DB::table('error')
            ->select(
                DB::raw("keterangan")
            )
            ->where('kodeerror', '=', 'WP')
            ->first();

        $data = [
            'error' => true,
            'message' => 'TRANSAKSI '.$query->keterangan,
            'kodeerror' => 'WP',
            'statuspesan' => 'warning',
        ];
        return response($data);
        }
       
    }

    public function getAllPembelian()
    {
        $pesananFinalHeader = new PesananFinalHeader();
        // dd('test');
        $data = $pesananFinalHeader->findAllPembelian();
        return response([
            'status' => true,
            'data' => $data,
            'attributes' => [
                'totalRows' => $pesananFinalHeader->totalRows,
                'totalPages' => $pesananFinalHeader->totalPages
            ]
        ]);
    }

    public function getAllPenjualan()
    {
        $pesananFinalHeader = new PesananFinalHeader();
        $data = $pesananFinalHeader->findAllPenjualan();

        // dd($data);
        return response([
            'status' => true,
            'data' => $data,
            'attributes' => [
                'totalRows' => $pesananFinalHeader->totalRows,
                'totalPages' => $pesananFinalHeader->totalPages
            ]
        ]);
    }

    public function editAllPembelian(EditAllPembelianRequest $request)
    {
        $allData = json_decode($request->data, true);

        $dataPembelian = array_values($allData);

        // dd($dataPembelian);

        DB::beginTransaction();
        try {
            $pesananFinalHeader = (new PesananFinalHeader())->editAllPembelian($dataPembelian);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Berhasil di edit all',
                'data' => $pesananFinalHeader
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function editAllPenjualan(EditALlPenjualanPesananFinalRequest $request)
    {

        $allData = json_decode($request->data, true);
        $dataPenjualan = array_values($allData);

        // dd($allData);


        DB::beginTransaction();
        try {
            $pesananFinalHeader = (new PesananFinalHeader())->editAllPenjualan($dataPenjualan);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Berhasil di edit all',
                'data' => $pesananFinalHeader
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


    public function editingat(EditingAtRequest $request)
    {
        $pesananFinalHeader = PesananFinalHeader::find($request->id);
        $btn = $request->btn;

        if ($btn == 'EDIT') {
            $param = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'PESANAN FINAL HEADER BUKTI')->first();
            $memo = json_decode($param->memo, true);
            $waktu = $memo['BATASWAKTUEDIT'];

            $user = auth('api')->user()->name;
            $editingat = new DateTime(date('Y-m-d H:i:s', strtotime($pesananFinalHeader->editingat)));
            $diffNow = $editingat->diff(new DateTime(date('Y-m-d H:i:s')));
            
        }

        $pesananFinalHeader = new PesananFinalHeader();
        return response([
            'data' => $pesananFinalHeader->editingAt($request->id, $request->btn),
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
            ->where("acos.class", 'pesananfinalheader')
            ->where("acos.method", 'approvaleditingby')
            ->where('acl.role_id', $query->role_id)
            ->first();
        $edit = '';
        if ($cekAcl != '') {
            $status = true;
            $edit = (new PesananFinalHeader())->editingAt($request->id, 'EDIT');
        } else {
            $cekUserAcl = DB::table("acos")->from(DB::raw("acos"))
                ->select(DB::raw("acos.id,acos.class,acos.method"))
                ->join(DB::raw("useracl"), 'acos.id', 'useracl.aco_id')
                ->where("acos.class", 'pesananfinalheader')
                ->where("acos.method", 'approvaleditingby')
                ->where('useracl.user_id', $query->id)
                ->first();
            if ($cekUserAcl != '') {
                $status = true;

                $edit = (new PesananFinalHeader())->editingAt($request->id, 'EDIT');
            } else {
                $status = false;
            }
        }
        if ($status) {

            event(new NewNotification(json_encode([
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
}
