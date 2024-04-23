<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Parameter;
use Illuminate\Validation\Rule;

class DestroySupplierRequest extends FormRequest
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
        $data = $parameter->getcombodata('TOP', 'TOP');
        $data = json_decode($data, true);
        foreach ($data as $item) {
            $top[] = $item['id'];
        }

        $rules = [
            'nama' => ['required', 'string'],
            'top' => ['required', Rule::in($top)],
            'status' => ['required', Rule::in($status)]
        ];
        return $rules; 
    }

    public function attributes()
    {
        return [
            'nama' => 'Nama',
            'telepon' => 'Telepon',
            'alamat' => 'Alamat',
            'keterangan' => 'Keterangan',
            'karyawanid' => 'Karyawan',
            'potongan' => 'Potongan',
            'top' => 'TOP',
            'status' => 'Status'
        ];
    }
}
