<?php

namespace App\Http\Requests;

use App\Models\Parameter;
use App\Rules\CheckEditingAtKaryawanValidation;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKaryawanRequest extends FormRequest
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
            'id' => new CheckEditingAtKaryawanValidation(),
            'nama' => ['required', 'string'],
            'armadaid' => ['required'],
            'armadanama' => ['required'],
            'status' => ['required', Rule::in($status)]
        ];
        return $rules;
    }

    public function attributes()
    {
        return [
            'nama' => 'Nama',
            'nama2' => 'Nama 2',
            'telepon' => 'Telepon',
            'alamat' => 'Alamat',
            'keterangan' => 'Keterangan',
            'armadaid' => 'Armada',
            'armadanama' => 'Armada',
            'status' => 'Status'
        ];
    }
}