<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Parameter;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Api\ErrorController;
use App\Http\Controllers\Api\ParameterController;
use Illuminate\Support\Facades\DB;

class StoreUserRequest extends FormRequest
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
        $data = $parameter->getcombodata('STATUS AKSES', 'STATUS AKSES');
        $data = json_decode($data, true);
        foreach ($data as $item) {
            $statusAkses[] = $item['id'];
        }
        return [
         
            'user' => ['required', 'unique:user,user'],
            'name' => 'required|unique:user',
            'email' => 'required|unique:user|email:rfc,dns',
            'password' => 'required',
            // 'karyawan_id' => 'required',
            // 'cabang_id' => 'required',
            // 'dashboard' => 'required',
            // 'status' => ['required', 'int', 'exists:parameter,id'],
            'status' => ['required', Rule::in($status)],
            'statusakses' => ['required', Rule::in($statusAkses)],
        ];
        
    }

    public function attributes()
    {
        return [
            'user' => 'user',
            'name' => 'nama user',
            'email' => 'email',
            'password' => 'password',
            // 'karyawan_id' => 'karyawan',
            'dashboard' => 'dashboard',
            'status' => 'status',
            'statusakses' => 'status akses',
        ];
    }

    public function messages()
    {
        $controller = new ErrorController;

        return [
            'email.required' => ':attribute' . ' ' . $controller->geterror('EMAIL')->keterangan,
        ];
    }
}
