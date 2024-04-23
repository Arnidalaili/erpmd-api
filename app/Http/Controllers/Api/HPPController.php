<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\HPP;
use Illuminate\Http\Request;

class HPPController extends Controller
{
    /**
     * @ClassName 
     * HPPController
     */
    public function index()
    {
        $hpp = new HPP();
        return response([
            'data' => $hpp->get(),
            'attributes' => [
                'totalRows' => $hpp->totalRows,
                'totalPages' => $hpp->totalPages
            ]
        ]);
    }
}
