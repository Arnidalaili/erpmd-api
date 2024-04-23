<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PembelianDetail;

class PembelianDetailController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $pembelianDetail = new PembelianDetail();

        return response([
            'data' => $pembelianDetail->get(),
            'attributes' => [
                'totalRows' => $pembelianDetail->totalRows,
                'totalPages' => $pembelianDetail->totalPages,
                'totalNominal' => $pembelianDetail->totalNominal
            ]
        ]);
    }
}
