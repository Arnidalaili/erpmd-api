<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Parameter;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Api\ErrorController;
use App\Http\Controllers\Api\ParameterController;
use App\Rules\UniqueTglHariLibur ;

class StoreHariLiburRequest extends FormRequest
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
        $tglbatasakhir=(date('Y')+1).'-01-01';
        $tglbatasawal=(date('Y')-1).'-01-01';

        $rules = [
            'keterangan' => ['required', 'unique:harilibur'],
            'tgl' => [
                'required', 'date_format:d-m-Y', 
                'after_or_equal:' . $tglbatasawal, 
                'before:' . $tglbatasakhir,
                new UniqueTglHariLibur()
            ],
            'status' => ['required', Rule::in($status)]
        ];
        

        return $rules; 
    }

    public function attributes()
    {
        return [
            'tgl' => 'Tanggal',
            'keterangan' => 'Keterangan',
            'status' => 'Status'
        ];
    }

    public function messages()
    {
        $controller = new ErrorController;
        $tglbatasakhir=(date('Y')+1).'-01-01';
        $tglbatasawal=(date('Y')-1).'-01-01';
        return [
            'tgl.after_or_equal' => ':attribute ' . $controller->geterror('NTLK')->keterangan.' '. date('d-m-Y', strtotime($tglbatasawal)). ' dan '. $controller->geterror('NTLB')->keterangan.' '. date('d-m-Y', strtotime($tglbatasakhir)),            
            'tgl.before' => ':attribute ' . $controller->geterror('NTLK')->keterangan.' '. date('d-m-Y', strtotime($tglbatasawal)). ' dan '. $controller->geterror('NTLB')->keterangan.' '. date('d-m-Y', strtotime($tglbatasakhir)),            
            
        ];
    }
}
