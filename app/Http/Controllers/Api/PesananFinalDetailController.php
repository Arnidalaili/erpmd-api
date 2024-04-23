<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PesananFinalDetail;
use Illuminate\Http\Request;

class PesananFinalDetailController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $pesananFinalDetail = new PesananFinalDetail();

        return response([
            'data' => $pesananFinalDetail->get(),
            'attributes' => [
                'totalRows' => $pesananFinalDetail->totalRows,
                'totalPages' => $pesananFinalDetail->totalPages,
                'totalNominalJual' => $pesananFinalDetail->totalNominalJual,
                'totalNominalBeli' => $pesananFinalDetail->totalNominalBeli
            ]
        ]);
    }

    public function reportPembelian(Request $request)
    {
        $pesananfinal = new PesananFinalDetail();
        return response([
            'pesan' => 'laporan success',
            'data' => $pesananfinal->getReportPembelian()
        ]);
    }
}
