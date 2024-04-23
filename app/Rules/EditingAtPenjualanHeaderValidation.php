<?php

namespace App\Rules;

use App\Http\Controllers\Api\ErrorController;
use App\Models\PenjualanHeader;
use DateTime;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Exception;

class EditingAtPenjualanHeaderValidation implements Rule
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
        // $penjualanHeader = PenjualanHeader::find(request()->id);
        // $btn = request()->btn;

        // if ($btn == 'EDIT') {
        //     $param = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'PENJUALAN HEADER BUKTI')->first();
        //     $memo = json_decode($param->memo, true);
        //     $waktu = $memo['BATASWAKTUEDIT'];


        //     $this->editingby = $penjualanHeader->editingby;
        //     $user = auth('api')->user()->name;
        //     $editingat = new DateTime(date('Y-m-d H:i:s', strtotime($penjualanHeader->editingat)));
        //     $diffNow = $editingat->diff(new DateTime(date('Y-m-d H:i:s')));
        //     // cek user
        //     if ($penjualanHeader->editingby != '' && $penjualanHeader->editingby != $user) {
        //         // CEK EDIT ALL TRUE ATAU TIDAK, KALAU TRUE RETURN FALSE
        //         if ($penjualanHeader->editall == 'TRUE') {
        //             throw new Exception('data ini sedang di edit melalui edit all oleh '.$this->editingby);
        //         }

        //         // check apakah waktu sebelumnya sudah melewati batas edit
        //         if ($diffNow->i > $waktu) {
        //             return true;
        //         }
        //         return false;
        //     }
        // }

        // return true;
        $penjualanHeader = PenjualanHeader::find(request()->id);
        $btn = request()->btn;
        if ($btn == 'EDIT') {
            $param = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'PENJUALAN HEADER BUKTI')->first();
            $memo = json_decode($param->memo, true);
            $waktu = $memo['BATASWAKTUEDIT'];
            $this->editingby = $penjualanHeader->editingby;
            $user = auth('api')->user()->name;
            $editingat = new DateTime(date('Y-m-d H:i:s', strtotime($penjualanHeader->editingat)));
            $diffNow = $editingat->diff(new DateTime(date('Y-m-d H:i:s')));
            // cek user
            if ($penjualanHeader->editingby != '' && $penjualanHeader->editingby != $user) {
                // check apakah waktu sebelumnya sudah melewati batas edit
                if ($diffNow->i > $waktu) {
                    return true;
                }
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
        return app(ErrorController::class)->geterror('SDE')->keterangan . ' oleh ' . $this->editingby;
    }
}
