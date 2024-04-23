<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePenjualanDetailRequest extends FormRequest
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
            "productid" =>['required','array'],
            "productnama.*" =>['required'],
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|gt:0',
            'harga' => 'required|array',
            'harga.*' => 'required|numeric',
            'satuanid' => ['required', 'array'],
            'satuannama.*' => ['required'],
            // 'keterangandetail' => ['required', 'array'],
          
        
        ];

        return $rules;
    }
}
