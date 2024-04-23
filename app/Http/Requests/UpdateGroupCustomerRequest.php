<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Parameter;
use App\Rules\CheckEditingAtGroupCustomerValidation;
use Illuminate\Validation\Rule;

class UpdateGroupCustomerRequest extends FormRequest
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
            'id' => new CheckEditingAtGroupCustomerValidation(),
            'nama' => ['required', 'string'],
            'status' => ['required', Rule::in($status)]
        ];
        return $rules; 
    }

    public function attributes()
    {
        return [
            'nama' => 'Nama',
            'keterangan' => 'Keterangan',
            'status' => 'Status'
        ];
    }
}
