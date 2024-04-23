<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePenyesuaianStokHeaderRequest extends FormRequest
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

        $mainValidator = validator(request()->only(['detail']), [
            'detail' => [function ($attribute, $value, $fail) {
                if (empty(json_decode($value, true))) {
                    $fail($attribute . ' wajib diisi');
                }
            }],
        ]);

        $detailValidator = validator($detailData, [
            '*.productid' => 'required',
            '*.productnama' => 'required',
            '*.qty' => ['required', 'numeric'],
            '*.harga' => 'required',
            // '*.total' => 'required',
        ], [
            '*.productnama.required' => 'product wajib diisi',
            '*.productid.required' => 'product id wajib diisi',
            '*.qty.required' => 'qty wajib diisi',
            '*.qty.min' => 'qty lebih besar dari 0',
            '*.harga.required' => 'harga wajib diisi',
            // '*.total.required' => 'total wajib diisi',
        ]);

        // $validator = validator(request()->all(), $rules);
        $mainValidator->validate();
        $detailValidator->validate();

        // $validatedMainData = $mainValidator->validated();
        $validatedDetailData = $detailValidator->validated();

        // dd($rules);
        return $validatedDetailData;
    }
}
