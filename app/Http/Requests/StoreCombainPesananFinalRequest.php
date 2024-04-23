<?php

namespace App\Http\Requests;

use App\Http\Controllers\Api\ErrorController;
use App\Rules\ValidationCustomerCombainPesananFinal;
use App\Rules\ValidationStatusPesananFinal;
use Illuminate\Foundation\Http\FormRequest;

class StoreCombainPesananFinalRequest extends FormRequest
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
            'pesananfinalheaderid' => 'required',
            'pesananfinalheaderid.*' => [ new ValidationStatusPesananFinal(),new ValidationCustomerCombainPesananFinal()],
        ];
    }

    public function messages()
    {
        return [
            'pesananfinalheaderid.required' => 'TRANSAKSI ' . app(ErrorController::class)->geterror('WP')->keterangan,
        ];
    }
}
