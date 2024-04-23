<?php

namespace App\Http\Requests;

use App\Models\PembelianHeader;
use DateTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CreatePembelianRequest extends FormRequest
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
        $pembelianHeader = new PembelianHeader();
        $tglpengiriman = (new DateTime())->format('Y-m-d');
        $empty = 0;
        $bukti = [];
        $query = DB::table('pesananfinalheader')
            ->select('*')
            ->whereDate('pesananfinalheader.tglpengiriman', $tglpengiriman)
            ->where('pesananfinalheader.status', 1)
            ->get();

        foreach ($query as $data) {
            $queryDetail = DB::table('pesananfinaldetail')
                ->select(
                    "pesananfinaldetail.hargajual",
                    "pesananfinaldetail.hargabeli",
                    "pesananfinalheader.nobukti"
                )
                ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', '=', 'pesananfinalheader.id')
                ->where('pesananfinaldetail.pesananfinalid', $data->id)
                ->where('pesananfinaldetail.hargabeli', 0)
                ->first();

            if ($queryDetail) {
                
                if ($queryDetail->hargabeli == 0) {
                   $empty++;
                   $bukti[] = $queryDetail->nobukti;
                }
            }
        }
    //  die;  
     return [
        'tglpengiriman' => function ($attribute, $value, $fail)use($empty,$bukti) {
            if ($empty > 0) {
                $fail("NO BUKTI ". implode(', ', $bukti)." harga beli produknya masih ada yang 0. tidak bisa melanjutkan create pembelian");
            } 
        }
    ];

       
    }
}
