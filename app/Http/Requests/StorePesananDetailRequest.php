<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Controllers\Api\ErrorController;
use App\Models\Parameter;
use Illuminate\Validation\Rule;

class StorePesananDetailRequest extends FormRequest
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
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|gt:0',
            'satuannama' => ['required', 'array'],
            'satuannama.*' => ['required'],
            'productnama' => ['required', 'array'],
            'productnama.*' => ['required'],
        ];
      
        return $rules;
    }

   
}
