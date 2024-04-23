<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditALlPenjualanPesananFinalRequest extends FormRequest
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
        $dataPenjualan = json_decode(request()->data, true);

        // dd($dataPenjualan);
        $i=0;
        foreach ($dataPenjualan as $data) {
            if (empty($data)) {
                continue;
            }  
            // dd($data['details']);

            foreach ($data['details']['productnama[]'] as $index => $idheader) {
                $mainValidator = validator($dataPenjualan, [
                    $i.".details.productnama[].*" => ['required'],
                    $i.".details.satuannama[].*" => ['required'],
                    $i.".details.qty[].*" => [
                        'required',
                        'numeric',
                        'nullable',
                        function ($attribute, $value, $fail) {
                            if ($value <= 0) {
                                $fail('qty harus lebih besar dari 0');
                            } 
                        },
                    ],
                    $i.".details.harga[].*" => [
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
                    $i.'.details.productnama[].*.required' => 'product wajib diisi',
                    $i.'.details.satuannama[].*.required' => 'satuan wajib diisi',
                    $i.'.details.harga[].*.required' => 'harga wajib diisi',
                    $i.'.details.qty[].*.required' => 'qty wajib diisi',
                ]);
            }

            // $mainValidator = validator($data['details'], [
            //     "productnama[].*" => ['required']
            // ], [
            //     'productnama[].*.required' => 'product wajib diisi',
            // ]);

            

            // dump($mainValidator->validate());
            $mainValidator->validate();
            $validatedDetailData = $mainValidator->validated();
            $i++;
        }

        return $validatedDetailData;
    }
}
