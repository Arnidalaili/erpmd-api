<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PenjualanDetail;
use Illuminate\Http\Request;

class PenjualanDetailController extends Controller
{
     /**
     * @ClassName 
     */
    public function index()
    {
        $penjualanDetail = new PenjualanDetail();

        return response([
            'data' => $penjualanDetail->get(),
            'attributes' => [
                'totalRows' => $penjualanDetail->totalRows,
                'totalPages' => $penjualanDetail->totalPages,
                'totalNominal' => $penjualanDetail->totalNominal,
                'totalRetur' => $penjualanDetail->totalRetur
            ]
        ]);
    }

    public function reportPembelian(Request $request)
    {
        $pesananfinal = new PenjualanDetail();
        return response([
            'data' => $pesananfinal->getReportPembelian()
        ]);
    }

    
}
