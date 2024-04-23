<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransaksiBelanjaEditAllRequest extends FormRequest
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
        $detailData = json_decode(request()->data, true);

        $detailValidator = validator($detailData, [
            '*.tglbukti' => ['required'],
            '*.perkiraanid' => ['required'],
            '*.perkiraannama' => ['required'],
            '*.karyawanid' => ['required'],
            '*.karyawannama' => ['required'],
            '*.nominal' => ['required'],
        ], [
            '*.tglbukti.required' => 'product wajib diisi',
            '*.perkiraanid.required' => 'perkiraan id  wajib diisi',
            '*.perkiraannama.required' => 'perkiraan  wajib diisi',
            '*.karyawanid.required' => 'karyawan id wajib diisi',
            '*.karyawannama.required' => 'karyawan wajib diisi',
            '*.nominal.required' => 'nominal wajib diisi',
            '*.keterangan.required' => 'keterangan wajib diisi',
        ]);

        $detailValidator->validate();

        $validatedDetailData = $detailValidator->validated();

        return $validatedDetailData; 
    }
}
