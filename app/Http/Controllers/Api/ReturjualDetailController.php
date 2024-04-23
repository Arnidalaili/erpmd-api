<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ReturJualDetail;

class ReturjualDetailController extends Controller
{
     /**
     * @ClassName 
     */
    public function index()
    {
        $returJualDetail = new ReturJualDetail();

        return response([
            'data' => $returJualDetail->get(),
            'attributes' => [
                'totalRows' => $returJualDetail->totalRows,
                'totalPages' => $returJualDetail->totalPages,
                'totalNominal' => $returJualDetail->totalNominal
            ]
        ]);
    }
}
