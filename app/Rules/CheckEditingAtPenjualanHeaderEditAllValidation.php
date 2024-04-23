<?php

namespace App\Rules;

use App\Http\Controllers\Api\ErrorController;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CheckEditingAtPenjualanHeaderEditAllValidation implements Rule
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
        // dd(date('Y-m-d H:i:s', strtotime(request()->date)));
        $penjualanHeader = DB::table("penjualanheader")->from(DB::raw("penjualanheader"))->where('tglbukti', date('Y-m-d H:i:s', strtotime(request()->date)))->where('editingby','!=','')->first();

        // dd($penjualanHeader);

        $user = auth('api')->user()->name;
        
        $this->useredit = ($penjualanHeader->editingby == '') ? 'USER LAIN ' : $penjualanHeader->editingby;
        
        
        // cek user
        if ($penjualanHeader->editingby != $user) {
            return false;
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
        return app(ErrorController::class)->geterror('DSE')->keterangan.' oleh '.$this->useredit;
    }
}
