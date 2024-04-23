<?php

namespace App\Rules;

use App\Http\Controllers\Api\ErrorController;
use App\Models\PesananFinalHeader;
use Illuminate\Contracts\Validation\Rule;

class CheckEditingAtPesananFinalHeaderValidation implements Rule
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
        $pesananFinalHeader = PesananFinalHeader::find(request()->id);
        $user = auth('api')->user()->name;
        $this->useredit = ($pesananFinalHeader->editingby == '') ? 'USER LAIN ' : $pesananFinalHeader->editingby;
        
      
        // cek user
        if ($pesananFinalHeader->editingby != $user) {
            return false;
        }

        // check apakah updatedat lebih besar dari editingat
        // if ($diff->i > $waktu) {
        //     return false;
        // }
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
