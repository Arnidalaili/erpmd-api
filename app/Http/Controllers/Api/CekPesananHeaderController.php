<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CekPesananDetail;
use App\Models\CekPesananHeader;

class CekPesananHeaderController extends Controller
{
    /**
     * @ClassName 
     * CekPesananHeaderController
     * @Detail CekPesananDetailController
     */
    public function index()
    {
        $cekPesananHeader = new CekPesananHeader();
        return response([
            'data' => $cekPesananHeader->get(),
            'attributes' => [
                'totalRows' => $cekPesananHeader->totalRows,
                'totalPages' => $cekPesananHeader->totalPages
            ]
        ]);
    }

    /**
     * @ClassName 
     */
    public function show($id)
    {
        $data = CekPesananHeader::findAll($id);
        $detail = CekPesananDetail::getAll($id);
        return response([
            'status' => true,
            'data' => $data,
            'detail' => $detail
        ]);
    }
}
