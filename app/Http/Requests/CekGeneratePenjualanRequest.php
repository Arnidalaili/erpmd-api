<?php

namespace App\Http\Requests;

use App\Http\Controllers\Api\ErrorController;
use App\Models\PenjualanHeader;
use App\Rules\CheckTanggalPengiriman;
use App\Rules\ValidationCombinePesanan;
use App\Rules\ValidationNoBuktiPenjualanPesananFinal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CekGeneratePenjualanRequest extends FormRequest
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
        $penjualanHeader = new PenjualanHeader();
        $pesananfinalheaderids = request()->pesananfinalheaderid; 

        $empty = 0;
        $cekPesanandetail = 0;
        $bukti = [];
        $namaProduk = [];
        $query = DB::table('pesananfinalheader')
            ->select('*')
            ->where('pesananfinalheader.id', request()->pesananfinalheaderid)
            ->where('pesananfinalheader.status', 1)
            ->get();

        // dd($query);
        foreach ($pesananfinalheaderids as $pesananfinalheaderid) {
        // foreach ($query as $data) {
            $queryDetail = DB::table('pesananfinaldetail')
                ->select(
                    "pesananfinaldetail.hargajual",
                    "pesananfinaldetail.hargabeli",
                    "pesananfinalheader.nobukti",
                    "product.nama as namaproduct",
                )
                ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', '=', 'pesananfinalheader.id')
                ->leftJoin('product', 'pesananfinaldetail.productid', '=', 'product.id') 
                ->where('pesananfinaldetail.pesananfinalid', $pesananfinalheaderid)
                ->where('pesananfinaldetail.hargajual', 0)
                ->get();

            if ($queryDetail) {
                foreach ($queryDetail as $data) {
                    if ($data->hargajual == 0) {
                        $empty++;
                        $bukti[] = $data->nobukti;
                        $namaProduk[] = $data->namaproduct;
                    }
                }
              
            }

        }
           
        // }

        // dd($cekPesanandetail);

        return [
            'pesananfinalheaderid' => ['required', new ValidationNoBuktiPenjualanPesananFinal(), new ValidationCombinePesanan(), function ($attribute, $value, $fail) use ($empty, $bukti,$namaProduk) {
                if ($empty > 0) {
                    $fail("PRODUCT ". implode(', ', $namaProduk) . " dengan invoice " . implode(', ', $bukti) . " memiliki harga jual 0. harga nya harus diubah menjadi > 0");
                }
            }],
        ];
    }

    public function messages()
    {
        return [
            'pesananfinalheaderid.required' => 'TRANSAKSI ' . app(ErrorController::class)->geterror('WP')->keterangan,
        ];
    }
}
