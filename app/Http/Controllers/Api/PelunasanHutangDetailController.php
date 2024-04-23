<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PelunasanHutangDetail;
use Illuminate\Http\Request;

class PelunasanHutangDetailController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $pelunasanHutangDetail = new PelunasanHutangDetail();

        return response([
            'data' => $pelunasanHutangDetail->get(),
            'attributes' => [
                'totalRows' => $pelunasanHutangDetail->totalRows,
                'totalPages' => $pelunasanHutangDetail->totalPages
            ]
        ]);
    }
}
