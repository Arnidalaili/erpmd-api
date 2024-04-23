<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Http\Controllers\Api\ErrorController;
use Illuminate\Support\Facades\DB;

class ValidationNoBuktiPembelianPesananFinal implements Rule
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
        // dd(request()->pembelianheaderid);
        for ($i = 0; $i < count(request()->pembelianheaderid); $i++) {
            $query = DB::table('pembeliandetail')
                ->where('id', request()->pembelianheaderid[$i])
                ->first();

            dd($query);

            
            $nobuktipenjualan = $query->nobuktipenjualan;

    
            if ($nobuktipenjualan) {
            
                return false;
            }
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
        return  app(ErrorController::class)->geterror('NBPJSA')->keterangan;
    }
}
