<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CekPesanan;
use App\Models\PesananFinalDetail;
use App\Models\PesananFinalHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CekPesananController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        /**
         * @ClassName 
         * CekPesananController
         */
        $cekPesanan = new CekPesanan();
        return response([
            'data' => $cekPesanan->get(),
            'attributes' => [
                'totalRows' => $cekPesanan->totalRows,
                'totalPages' => $cekPesanan->totalPages
            ]
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(Request $request)
    {
        $detailData = json_decode($request->detail, true);
        $details = array_values($detailData);

        $filteredDetails = array_filter($details);
        // dd($details);

        DB::beginTransaction();
        try {
            /* Store header */
           
                $data = (new CekPesanan())->processData($filteredDetails);

                $cekPesanan = (new CekPesanan())->processUpdate($data);

                // dd($cekPesanan);

            DB::commit();
            return response()->json([
                'message' => 'Berhasil disimpan',
                'data' => $cekPesanan
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function findAllPenjualan(){
        $cekPesanan = new CekPesanan();

        // dd('masuk');
        $data = $cekPesanan->findPenjualan();

        // dd($data);

        return response([
            'status' => true,
            'data' => $data,
            'attributes' => [
                'totalRows' => $cekPesanan->totalRows,
                'totalPages' => $cekPesanan->totalPages
            ]
        ]);
    }

    public function findpesanandetail()
    {
        $cekPesanan = new CekPesanan();

        $data = $cekPesanan->findpesanandetail();

        return response([
            'status' => true,
            'data' => $data,
            'attributes' => [
                'totalRows' => $cekPesanan->totalRows,
                'totalPages' => $cekPesanan->totalPages
            ]
        ]);
    }

    public function getheader(){
        $pesananFinalHeader = new PesananFinalHeader();

        return response([
            'data' => $pesananFinalHeader->get(),
            'attributes' => [
                'totalRows' => $pesananFinalHeader->totalRows,
                'totalPages' => $pesananFinalHeader->totalPages
            ]
        ]);
    }
}
