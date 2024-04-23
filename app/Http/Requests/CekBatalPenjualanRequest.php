<?php

namespace App\Http\Requests;

use App\Http\Controllers\Api\ErrorController;
use App\Models\PenjualanHeader;
use App\Models\PesananFinalHeader;
use App\Rules\ValidationNobuktiPenjualanBatalPenjualan;
use Illuminate\Foundation\Http\FormRequest;

class CekBatalPenjualanRequest extends FormRequest
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
            'pesananfinalheaderid' => ['required', new ValidationNobuktiPenjualanBatalPenjualan()],
        ];
    }

    public function messages()
    {
        return [
            'pesananfinalheaderid.required' => 'TRANSAKSI ' . app(ErrorController::class)->geterror('WP')->keterangan,
            // 'pesananfinalheaderid.date' => 'Tanggal pengiriman harus dalam format tanggal yang valid.',
        ];
    }
}
