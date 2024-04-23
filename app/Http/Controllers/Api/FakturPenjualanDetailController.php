<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFakturPenjualanDetailRequest;
use App\Models\FakturPenjualanDetailModel;
use Illuminate\Http\Request;

class FakturPenjualanDetailController extends Controller
{

    /**
     * @ClassName 
     */
    public function index()
    {
        $fakturpenjualanDetail = new FakturPenjualanDetailModel();
    //    dd('test');
        return response([
            'data' => $fakturpenjualanDetail->get(),
            'attributes' => [
                'totalRows' => $fakturpenjualanDetail->totalRows,
                'totalPages' => $fakturpenjualanDetail->totalPages
            ]
        ]);
    }

    public function addRow(StoreFakturPenjualanDetailRequest $request){
        return true;
    }


    /**
     * @ClassName 
     */
    public function create()
    {
        //
    }


    /**
     * @ClassName 
     */
    public function store(Request $request)
    {
        //
    }


    /**
     * @ClassName 
     */
    public function show($id)
    {
        //
    }



    /**
     * @ClassName 
     */
    public function update(Request $request, $id)
    {
        //
    }


    /**
     * @ClassName 
     */
    public function destroy($id)
    {
        //
    }
}
