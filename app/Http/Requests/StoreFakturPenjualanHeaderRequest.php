<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Controllers\Api\ErrorController;
use App\Models\Parameter;
use Illuminate\Validation\Rule;

class StoreFakturPenjualanHeaderRequest extends FormRequest
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
       

        $rules =  [
                "invoicedate" => ['required'],
                // "customer_name" => ['required'],
                "nopo" => ['required'],
                "shipdate" => ['required'],
                "shipvia" => ['required'],
                "receivableacoount" => ['required'],
                "sales_name" => ['required'],
              
            
        ];

        $relatedRequests = [
            StoreFakturPenjualanDetailRequest::class
        ];

        foreach ($relatedRequests as $relatedRequest) {
            $rules = array_merge(
                $rules,
                (new $relatedRequest)->rules()
            );
        }

       

        return $rules;
    }

    public function attributes()
    {
        return [
            'kodebank' => 'kode bank',
            'namabank' => 'nama bank',
            'statusaktif' => 'status aktif',
            'coa' => 'kode perkiraan',
            'tipe' => 'tipe',
            'formatpenerimaan' => 'format penerimaan',
            'formatpengeluaran' => 'format pengeluaran',
        ];
    }

    // public function messages()
    // {
    //     $controller = new ErrorController;

    //     return [
    //         'kodebank.required' => ':attribute' . ' ' . $controller->geterror('WI')->keterangan,
    //         'namabank.required' => ':attribute' . ' ' . $controller->geterror('WI')->keterangan,
    //         'statusaktif.required' => ':attribute' . ' ' . $controller->geterror('WI')->keterangan,
    //         'coa.required' => ':attribute' . ' ' . $controller->geterror('WI')->keterangan,
    //         'tipe.required' => ':attribute' . ' ' . $controller->geterror('WI')->keterangan,
    //         'formatpenerimaan.required' => ':attribute' . ' ' . $controller->geterror('WI')->keterangan,
    //         'formatpengeluaran.required' => ':attribute' . ' ' . $controller->geterror('WI')->keterangan,
    //     ];
    // }
}
