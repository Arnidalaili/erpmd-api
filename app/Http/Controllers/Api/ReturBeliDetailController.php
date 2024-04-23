<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ReturBeliDetail;

class ReturBeliDetailController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $returBeliDetail = new ReturBeliDetail();

        return response([
            'data' => $returBeliDetail->get(),
            'attributes' => [
                'totalRows' => $returBeliDetail->totalRows,
                'totalPages' => $returBeliDetail->totalPages,
                'totalNominal' => $returBeliDetail->totalNominal,
            ]
        ]);
    }
}
