<?php

namespace App\Rules;

use App\Http\Controllers\Api\ErrorController;
use DateTime;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CekValidationHapusPembelian implements Rule
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
        $tglpengiriman = request()->tglpengiriman;
        $tglpengiriman = DateTime::createFromFormat('d-m-Y', $tglpengiriman)->format('Y-m-d');
        $query = DB::table('pesananfinalheader as a')
            ->select(
                "a.nobukti",
                "b.nobuktipembelian"
            )
            ->leftJoin(DB::raw("pesananfinaldetail as b"), 'a.id', 'b.pesananfinalid')
            ->where("a.tglpengiriman", $tglpengiriman)
            ->where('b.nobuktipembelian', '')
            ->where("a.status", 1)
            ->first();
       
            if($query != '')
            {
                return true;
            } else {
                return false;
            }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return app(ErrorController::class)->geterror('PTBH')->keterangan;
    }
}
