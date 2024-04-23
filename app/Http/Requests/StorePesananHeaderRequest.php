<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Controllers\Api\ErrorController;
use App\Models\Parameter;
use App\Rules\CheckHargaProduct;
use Illuminate\Validation\Rule;
use PhpParser\Node\Expr\New_;

class StorePesananHeaderRequest extends FormRequest
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

        $mainValidator = validator(request()->only(['customernama', 'customerid', 'alamatpengiriman', 'tglpengiriman','detail']), [
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
            '*.productid' => ['required'],
            // '*.productnama' => ['required',New CheckHargaProduct()],
            '*.productnama' => ['required'],
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
        ], [
            '*.productnama.required' => 'product wajib diisi',
            '*.productid.required' => 'product id wajib diisi',
            '*.qty.required' => 'qty wajib diisi',
            '*.qty.min' => 'NILAI QTY  TIDAK BOLEH < DARI 1.',
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
            'tgl bukti' => 'tgl bukti',
            'customernama' => 'nama customer',
            'customerid' => 'customer',
            'alamatpengiriman' => 'alamat pengiriman',
            'tglpengiriman' => 'tglpengiriman',
            'satuanid' => 'satuan',
            'qty.*' => 'qty',
            'satuannama.*' => 'satuan',
            'productnama.*' => 'product',
        ];
    }
}
