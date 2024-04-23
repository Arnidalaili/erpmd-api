<?php

namespace App\Rules;

use App\Http\Controllers\Api\ErrorController;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ValidationCustomerCombainPesananFinal implements Rule
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
        $prevcustomer = '';
        $prevtglpengiriman = '';
        $prevnobuktipenjualan = '';
        for ($i = 0; $i < count(request()->pesananfinalheaderid); $i++) {
            $query = DB::table('pesananfinalheader')
                ->where('id', request()->pesananfinalheaderid[$i])
                ->first();
            
            $customer = $query->customerid;
            $tglpengiriman = $query->tglpengiriman;
            $nobuktipenjualan = $query->nobuktipenjualan;

            
            if ($prevcustomer != '' && $prevcustomer != $customer || $prevtglpengiriman != '' && $prevtglpengiriman != $tglpengiriman || $nobuktipenjualan != '' && $prevnobuktipenjualan != $nobuktipenjualan) {
                return false;
            }
            $prevcustomer = $customer;
            $prevtglpengiriman = $tglpengiriman;
            $prevnobuktipenjualan = $nobuktipenjualan;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return  app(ErrorController::class)->geterror('CTPHS')->keterangan;
    }
}
