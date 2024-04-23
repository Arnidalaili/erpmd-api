<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Parameter;
use Illuminate\Validation\Rule;

class StoreBankRequest extends FormRequest
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
        $data = $parameter->getcombodata('TIPE BANK', 'TIPE BANK');
        $data = json_decode($data, true);
        foreach ($data as $item) {
            $tipeBank[] = $item['id'];
        }

        $rules = [
            'nama' => ['required', 'string'],
            'tipebank' => ['required', Rule::in($tipeBank)],
            'status' => ['required', Rule::in($status)]
        ];
        return $rules; 
    }

    public function attributes()
    {
        return [
            'nama' => 'Nama',
            'tipebank' => 'Tipe Bank',
            'keterangan' => 'Keterangan',
            'status' => 'Status'
        ];
    }
}
