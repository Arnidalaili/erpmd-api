<?php

namespace App\Http\Requests;

use App\Models\Parameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DestroyAlatBayarRequest extends FormRequest
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
        $parameter = new Parameter();
        $data = $parameter->getcombodata('STATUS', 'STATUS');
        $data = json_decode($data, true);
        foreach ($data as $item) {
            $status[] = $item['id'];
        }
        $rules = [
            'nama' => ['required', 'string'],
            'status' => ['required', Rule::in($status)]
        ];
        return $rules; 
    }

    public function attributes()
    {
        return [
            'nama' => 'Nama',
            'bankid' => 'Bank',
            'keterangan' => 'Keterangan',
            'status' => 'Status'
        ];
    }
}
