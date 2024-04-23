<?php

namespace App\Http\Requests;

use App\Models\Parameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenjualanHeaderRequest extends FormRequest
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
        $detailData = json_decode(request()->detail, true);

        $mainValidator = validator(request()->only(['customernama', 'customerid', 'alamatpengiriman', 'tglpengiriman', 'detail']), [
            "customernama" => ['required'],
            "customerid" => ['required'],
            "alamatpengiriman" => ['required'],
            "tglpengiriman" => ['required'],
            'detail' => [function ($attribute, $value, $fail) {
                if (empty(json_decode($value, true))) {
                    $fail($attribute . ' wajib diisi');
                }
            }],
        ]);

        $detailValidator = validator($detailData, [
            '*.productid' => 'required',
            '*.productnama' => 'required',
            '*.qty' => [
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
            '*.harga' => [
                'required',
                'numeric',
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value <= 0) {
                        $fail('harga harus lebih besar dari 0');
                    } 
                },
            ],
        ], [
            '*.productnama.required' => 'product wajib diisi',
            '*.productid.required' => 'product id wajib diisi',
            '*.qty.required' => 'qty wajib diisi',
            '*.qty.min' => 'qty lebih besar dari 0',
            '*.harga.required' => 'harga wajib diisi',
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
            'qty' => 'qty',
            'qty.*' => 'qty',
            'satuannama' => 'satuan',
            'satuannama.*' => 'satuan',
            'productnama' => 'product',
            'productnama.*' => 'product',
        ];
    }
}
