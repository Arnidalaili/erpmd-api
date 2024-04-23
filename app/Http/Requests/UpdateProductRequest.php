<?php

namespace App\Http\Requests;

use App\Models\Parameter;
use App\Rules\CheckEditingAtProductValidation;
use App\Rules\CheckProductExistInPesananFinal;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'id' => new CheckEditingAtProductValidation(),
            'nama' => ['required', 'string'],
            'suppliernama' => ['required'],
            'supplierid' => ['required'],
            'satuannama' => ['required'],
            'satuanid' => ['required'],
            'nama' => ['required', 'string',new CheckProductExistInPesananFinal()],
            'status' => ['required', Rule::in($status)],
        ];
        return $rules; 
    }

    public function attributes()
    {
        return [
            'nama' => 'Nama',
            'keterangan' => 'Keterangan',
            'status' => 'Status',
            'suppliernama' => 'supplier',
            'supplierid' => 'supplier',
            'satuannama' => 'satuan',
            'satuanid' => 'satuan'
        ];
    }
}
