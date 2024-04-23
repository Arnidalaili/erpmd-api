<?php

namespace App\Http\Requests;

use App\Http\Controllers\Api\ErrorController;
use App\Rules\ValidationNoBuktiPembelianPesananFinal;
use Illuminate\Foundation\Http\FormRequest;

class CekGeneratePembelianRequest extends FormRequest
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
            'pembelianheaderId' => [new ValidationNoBuktiPembelianPesananFinal()]
        ];
    }

    
    public function messages()
    {
        return [
            'pembelianheaderId.required' => 'TRANSAKSI ' . app(ErrorController::class)->geterror('WP')->keterangan,
        ];
    }
}
