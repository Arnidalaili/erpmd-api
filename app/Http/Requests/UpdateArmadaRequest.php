<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Parameter;
use App\Rules\CheckEditingAtArmadaValidation;
use Illuminate\Validation\Rule;

class UpdateArmadaRequest extends FormRequest
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
        $data = $parameter->getcombodata('JENIS ARMADA', 'JENIS ARMADA');
        $data = json_decode($data, true);
        foreach ($data as $item) {
            $jenisArmada[] = $item['id'];
        }

        $rules = [
            'id' => new CheckEditingAtArmadaValidation(),
            'nama' => ['required', 'string'],
            'jenisarmada' => ['required', Rule::in($jenisArmada)],
            'nopolisi' => ['required'],
            'namapemilik' => ['required', 'string'],
            'nostnk' => ['required', 'string'],
            'status' => ['required', Rule::in($status)]
        ];
        return $rules; 
    }

    public function attributes()
    {
        return [
            'nama' => 'Nama',
            'jenisarmada' => 'Jenis Armada',
            'nopolisi' => 'Nomor Polisi',
            'namapemilik' => 'Nama Pemilik',
            'nostnk' => 'Nomor STNK',
            'keterangan' => 'Keterangan',
            'status' => 'Status'
        ];
    }
}
