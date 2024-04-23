<?php

namespace App\Http\Requests;

use App\Http\Controllers\Api\ErrorController;
use App\Models\Parameter;
use Illuminate\Foundation\Http\FormRequest;

class GetIndexRangeRequest extends FormRequest
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
        $getBatas = $parameter->getBatasAwalTahun();
        $tglbatasawal = $getBatas->text;
        $tglbatasakhir = (date('Y') + 1) . '-01-01';
        $rules =  [
            'tgldariheader' => [
                'required', 'date_format:d-m-Y',
                'before:'.$tglbatasakhir,
                'after_or_equal:'.$tglbatasawal,
            ],
            'tglsampaiheader' => [
                'required', 'date_format:d-m-Y',
                'before:'.$tglbatasakhir,
                'after_or_equal:'.date('Y-m-d', strtotime($this->tgldariheader))
            ],
            
        ];

        return $rules;
    }

    public function attributes()
    {
        return [
            'tgldariheader' => 'tanggal dari',
            'tglsampaiheader' => 'tanggal sampai',
        ];
    }

    public function messages()
    {
        $controller = new ErrorController;

        return [
            'tglsampaiheader.after_or_equal' => ':attribute ' . $controller->geterror('NTLK')->keterangan.' '. $this->tgldariheader,
        ];
    }    
}
