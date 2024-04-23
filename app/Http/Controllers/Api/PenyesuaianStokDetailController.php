<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PenyesuaianStokDetail;

class PenyesuaianStokDetailController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $penyesuaianStokDetail = new PenyesuaianStokDetail();

        return response([
            'data' => $penyesuaianStokDetail->get(),
            'attributes' => [
                'totalRows' => $penyesuaianStokDetail->totalRows,
                'totalPages' => $penyesuaianStokDetail->totalPages,
                'totalHarga' => $penyesuaianStokDetail->totalHarga
            ]
        ]);
    }
}
