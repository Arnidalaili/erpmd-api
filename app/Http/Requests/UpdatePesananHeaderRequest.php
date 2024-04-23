<?php

namespace App\Http\Requests;

use App\Rules\CheckEditingAtPesananHeaderValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePesananHeaderRequest extends FormRequest
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
            'id' => new CheckEditingAtPesananHeaderValidation(),
            "tglbukti" => ['required'],
            "customernama" => ['required'],
            "customerid" => ['required'],
            "alamatpengiriman" => ['required'],
            "tglpengiriman" => ['required'],
            // "keterangan" => ['required'], 
        
        ];

        $relatedRequests = [
            UpdatePesananDetailRequest::class
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
            'tglbukti' => 'tgl bukti',
            'customernama' => 'nama customer',
            'customerid' => 'customer',
            'alamatpengiriman' => 'alamat pengiriman',
            'tglpengiriman' => 'tgl pengiriman',
            'satuanid' => 'satuan',
            'status' => 'status',
            'qty' => 'qty',
            'qty.*' => 'qty',
            'satuannama' => 'satuan',
            'satuannama.*' => 'satuan',
            'productnama' => 'product',
            'productnama.*' => 'product',
        ];
    }
}
