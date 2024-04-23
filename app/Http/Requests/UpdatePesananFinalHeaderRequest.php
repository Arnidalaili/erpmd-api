<?php

namespace App\Http\Requests;

use App\Rules\CheckEditingAtPesananFinalHeaderValidation;
use App\Rules\CheckEditingAtValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePesananFinalHeaderRequest extends FormRequest
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
        // return $rules;
        $detailData = json_decode(request()->detail, true);

        $mainValidator = validator(request()->only(['id','customernama', 'customerid', 'alamatpengiriman', 'tglpengiriman']), [
            "customernama" => ['required'],
            "customerid" => ['required'],
            "alamatpengiriman" => ['required'],
            "tglpengiriman" => ['required'],
            "id" => new CheckEditingAtPesananFinalHeaderValidation()
        ]);

        $detailValidator = validator($detailData, [
            '*.productid' => 'required',
            '*.productnama' => 'required',
            '*.qtyjual' => [
                'required',
                'numeric',
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value <= 0) {
                        $fail('qty harus lebih besar dari 0');
                    } 
                },
            ],
            '*.satuannama' => 'required',
            '*.hargajual' => [
                'required',
                'numeric',
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value <= 0) {
                        $fail('harga jual harus lebih besar dari 0');
                    } 
                },
            ],
        ], [
            '*.productnama.required' => 'product wajib diisi',
            '*.productid.required' => 'product id wajib diisi',
            '*.qtyjual.required' => 'qty wajib diisi',
            '*.qtyjual.min' => 'qty lebih besar dari 0',
            '*.hargajual.required' => 'harga wajib diisi',
            '*.satuannama.required' => 'satuan wajib diisi',
        ]);

        // $validator = validator(request()->all(), $rules);
        $mainValidator->validate();
        $detailValidator->validate();

        $validatedMainData = $mainValidator->validated();
        $validatedDetailData = $detailValidator->validated();

        // dd($rules);
        return $validatedDetailData;
    }

    public function attributes()
    {
        return [
            'tglbukti' => 'tgl bukti',
            'customernama' => 'nama customer',
            'customerid' => 'customer',
            'alamatpengiriman' => 'alamat pengiriman',
            'tglpengiriman' => 'tgl pengiriman',
            'satuanid' => 'satuan',
            'status' => 'status',
            'qtyjual' => 'qty',
            'qtyjual.*' => 'qty',
            'satuannama' => 'satuan',
            'satuannama.*' => 'satuan',
            'productnama' => 'product',
            'productnama.*' => 'product',
        ];
    }
}
