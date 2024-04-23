<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PesananPembelianDetail extends Model
{
    use HasFactory;

    protected $table = 'pesananpembeliandetail';

    protected $casts = [
        'created_at' => 'date:d-m-Y H:i:s',
        'updated_at' => 'date:d-m-Y H:i:s'
    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function getAll($id, $detail)
    {
        $idDetail = $detail->pluck('id')->toArray();

        $query = DB::table('pesananpembeliandetail as a')
            ->select(
                "a.id",
                "a.pembeliandetailid",
                "a.pesananfinalid",
                "a.pesananfinaldetailid",
                "a.productid",
                "a.satuanid",
                "a.keterangan",
                "a.qty",
                "a.harga",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "a.created_at",
                "a.updated_at",
            )
            ->leftJoin(DB::raw("product"), 'a.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'a.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'a.modifiedby', 'modifier.id')
            ->whereIn('a.pembeliandetailid', $idDetail);

        $data = $query->get();

        return $data;
    }

    public function processStore(PembelianDetail $pembelianDetail, array $data): PesananPembelianDetail
    {
        // dd($data);
        $pesananDetail = new PesananPembelianDetail();
        $pesananDetail->pembeliandetailid = $data['pembeliandetailid'];
        $pesananDetail->pesananfinalid = $data['pesananfinalid'] ?? 0;
        $pesananDetail->pesananfinaldetailid = $data['pesananfinaldetailid'] ?? 0;
        $pesananDetail->productid = $data['productid'];
        $pesananDetail->satuanid = $data['satuanid'];
        $pesananDetail->keterangan = $data['keterangan'];
        $pesananDetail->qty = $data['qty'];
        $pesananDetail->harga = $data['harga'];
        $pesananDetail->modifiedby = $data['modifiedby'];
        $pesananDetail->save();

        // dd($pesananDetail);

        if (!$pesananDetail->save()) {
            throw new \Exception("Error storing Pesanan Pembelian Detail.");
        }

        // dd($pesananDetail);

        return $pesananDetail;
    }
}
