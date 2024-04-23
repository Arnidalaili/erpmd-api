<?php

namespace App\Http\Requests;

use App\Models\Parameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePembelianHeaderRequest extends FormRequest
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

        $detailData = array_values($detailData);

        $parameter = new Parameter();
        $data = $parameter->getcombodata('STATUS', 'STATUS');
        $data = json_decode($data, true);
        foreach ($data as $item) {
            $status[] = $item['id'];
        }

        $mainValidator = validator(request()->only(['supplierid', 'suppliernama', 'karyawanid', 'karyawannama', 'tglterima', 'status', 'detail']), [
            'supplierid' => ['required'],
            'suppliernama' => ['required'],
            'karyawanid' => ['required'],
            'karyawannama' => ['required'],
            'tglterima' => ['required'],
            'detail' => [function ($attribute, $value, $fail) {
                // dd($value);
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
            '*.satuanid' => 'required',
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
            '*.satuanid.required' => 'satuanid wajib diisi',
            '*.satuannama.required' => 'satuan wajib diisi',
            '*.qty.required' => 'qty wajib diisi',
            '*.qty.min' => 'qty lebih besar dari 0',
            '*.harga.required' => 'harga wajib diisi',
            
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
            'alamatpengiriman' => 'alamat pengiriman',
            'tglpengiriman' => 'tgl pengiriman',
            'satuanid' => 'satuan',
            'qty' => 'qty',
            'qty.*' => 'qty',
            'satuannama' => 'satuan',
            'satuannama.*' => 'satuan',
            'productnama' => 'product',
            'productnama.*' => 'product',
        ];
    }
}
