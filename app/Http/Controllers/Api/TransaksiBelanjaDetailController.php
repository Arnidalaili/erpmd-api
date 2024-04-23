<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TransaksiBelanjaDetail;

class TransaksiBelanjaDetailController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $transaksiBelanjaDetail = new TransaksiBelanjaDetail();

        return response([
            'data' => $transaksiBelanjaDetail->get(),
            'attributes' => [
                'totalRows' => $transaksiBelanjaDetail->totalRows,
                'totalPages' => $transaksiBelanjaDetail->totalPages,
                // 'totalNominal' => $transaksiBelanjaDetail->totalNominal
            ]
        ]);
    }
}
