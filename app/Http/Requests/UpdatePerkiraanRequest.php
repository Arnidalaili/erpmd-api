<?php

namespace App\Http\Requests;

use App\Models\Parameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerkiraanRequest extends FormRequest
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
        $data = $parameter->getcombodata('OPERATOR', 'OPERATOR');
        $data = json_decode($data, true);
        foreach ($data as $item) {
            $operator[] = $item['id'];
        }

        $rules = [
            'nama' => ['required', 'string'],
            'operator' => ['required', Rule::in($operator)],
            'status' => ['required', Rule::in($status)]
        ];
        return $rules;
    }

    public function attributes()
    {
        return [
            'nama' => 'Nama',
            'operator' => 'Operator',
            'keterangan' => 'Keterangan',
            'status' => 'Status'
        ];
    }
}
