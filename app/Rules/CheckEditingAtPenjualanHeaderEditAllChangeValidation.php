<?php

namespace App\Rules;

use App\Http\Controllers\Api\ErrorController;
use DateTime;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CheckEditingAtPenjualanHeaderEditAllChangeValidation implements Rule
{
    public $useredit;
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
      
        $penjualanHeader = DB::table("penjualanheader")->from(DB::raw("penjualanheader"))->where('tglbukti', date('Y-m-d H:i:s', strtotime(request()->date)))->where('editingby','!=','')->first();

        // dd($penjualanHeader);

        $user = auth('api')->user()->name;
        
        if ($penjualanHeader) {
            $param = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'PENJUALAN HEADER BUKTI')->first();
            $memo = json_decode($param->memo, true);
            $waktu = $memo['BATASWAKTUEDIT'];

            $editingat = new DateTime(date('Y-m-d H:i:s', strtotime($penjualanHeader->editingat)));
            $diffNow = $editingat->diff(new DateTime(date('Y-m-d H:i:s')));

            if ($diffNow->i > $waktu) {
                return true;
            }

            $this->useredit = ($penjualanHeader->editingby == '') ? 'USER LAIN ' : $penjualanHeader->editingby;

            // cek user
            if ($penjualanHeader->editingby != $user) {
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
        return 'data di tgl '.request()->date.' sedang di edit oleh '.$this->useredit;
    }
}
