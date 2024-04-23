<?php

namespace App\Http\Requests;

use App\Rules\CekNoBuktiPembelian;
use App\Rules\CekValidationHapusPembelian;
use App\Rules\CheckTanggalPengiriman;
use App\Rules\CheckTanggalPengirimanBatalPembelian;
use Illuminate\Foundation\Http\FormRequest;

class ValidationHapusPembelianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'tglpengiriman' => [new CheckTanggalPengirimanBatalPembelian(), new CekNoBuktiPembelian()]
        ];
    }
}
