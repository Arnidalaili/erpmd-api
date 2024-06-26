<?php

namespace App\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CheckTanggalPengiriman implements Rule
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
        $getPesananFinal = DB::table("pesananfinalheader")
            ->select('tglpengiriman')
            ->where('id', '=', $value)
            ->first();
        // dd($getPesananFinal);

        $tanggalPengiriman = Carbon::parse($getPesananFinal->tglpengiriman);

        // dd($tanggalPengiriman);

        // Cek apakah tanggal pengiriman adalah hari ini atau besok
        return $tanggalPengiriman->isToday();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Tanggal pengiriman harus untuk hari ini';
    }
}
