<?php

namespace App\Rules;

use App\Http\Controllers\Api\ErrorController;
use App\Models\PesananFinalHeader;
use Illuminate\Contracts\Validation\Rule;

class CheckEditingAtValidation implements Rule
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
        $pesananfinalheader = PesananFinalHeader::find(request()->id);
        $user = auth('api')->user()->name;
        
        // $param = DB::table("parameter")->from(DB::raw("parameter with (readuncommitted)"))->where('grp', 'PENERIMAAN GIRO BUKTI')->first();
        // $memo = json_decode($param->memo, true);
        // $waktu = $memo['BATASWAKTUEDIT'];

        // $editingat = new DateTime(date('Y-m-d H:i:s', strtotime($pesananfinalheader->editing_at)));
        // $updatedat = new DateTime($penerimaanGiro->updated_at->toDateTimeString());
        // $diff = $editingat->diff($updatedat);

        // cek user
        if ($pesananfinalheader->editingby != $user) {
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
        return app(ErrorController::class)->geterror('SMBE')->keterangan;
    }
}
