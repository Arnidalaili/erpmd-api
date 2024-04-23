<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CheckQtyReturValidation implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

       




        // foreach ($getPenjualanHeader as $penjualanHeader) {
        //     dd($penjualanHeader->id);
        //     $totalQty = $penjualanHeader->totalqty;

        //     dd($totalQtyretur, $totalQty);
        // }



        // foreach ($getPenjualan as $penjualanid) {
        //     // dd($penjualanid->penjualanid);
        //     $getDetailPenjualan = DB::table("penjualandetail")
        //         ->select(
        //             'penjualandetail.id',
        //             DB::raw('SUM(penjualandetail.qtyretur) as total_qty_retur')
        //             )
        //         ->leftJoin('penjualanheader', 'penjualanheader.id', 'penjualandetail.penjualanid')
        //         ->where('penjualanheader.id', '=',$penjualanid->penjualanid)
        //         ->groupby('penjualandetail.id')
        //         ->first();

        //     dump($getDetailPenjualan->total_qty_retur);
        // }
        // die;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
