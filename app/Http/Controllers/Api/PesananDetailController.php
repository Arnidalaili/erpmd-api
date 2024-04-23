<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PesananDetail;
use Illuminate\Http\Request;

class PesananDetailController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $pesananDetail = new PesananDetail();
            return response([
                'data' => $pesananDetail->get(),
                'attributes' => [
                    'totalRows' => $pesananDetail->totalRows,
                    'totalPages' => $pesananDetail->totalPages,
                    
                ]
            ]);
    }
}
