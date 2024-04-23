<?php

namespace App\Rules;

use App\Http\Controllers\Api\ErrorController;
use App\Models\PenjualanHeader;
use Illuminate\Contracts\Validation\Rule;

class CheckEditingAtPenjualanHeaderValidation implements Rule
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
      
        $penjualanHeader = PenjualanHeader::find(request()->id);
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
        return app(ErrorController::class)->geterror('SMBE')->keterangan.' '.$this->useredit;
    }
}
