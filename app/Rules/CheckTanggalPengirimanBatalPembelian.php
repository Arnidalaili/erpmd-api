<?php

namespace App\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CheckTanggalPengirimanBatalPembelian implements Rule
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
      
     
        $tanggalPengiriman = Carbon::parse($value);

        // Cek apakah tanggal pengiriman adalah hari ini atau besok
        return $tanggalPengiriman->isToday() || $tanggalPengiriman->isTomorrow();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Tanggal pengiriman harus untuk hari ini atau esok hari';
    }
}
