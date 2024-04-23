<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditAllRequest extends FormRequest
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

        $mainValidator = validator(request()->only(['data']), [
            'data' => [function ($attribute, $value, $fail) {
                if (empty(json_decode($value, true))) {
                    $fail($attribute . ' wajib diisi');
                }
            }],
        ]);

        $mainValidator->validate();
      
        $validatedMainData = $mainValidator->validated();
     
        // dd($rules);
        return $validatedMainData;

    }
}
