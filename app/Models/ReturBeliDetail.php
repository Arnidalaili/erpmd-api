<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReturBeliDetail extends MyModel
{
    use HasFactory;

    protected $table = 'returbelidetail';

    protected $casts = [
        'created_at' => 'date:d-m-Y H:i:s',
        'updated_at' => 'date:d-m-Y H:i:s'
    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function get()
    {
        $this->setRequestParameters();

        $query = DB::table($this->table . ' as returbelidetail');
        $query->select(
            "returbelidetail.id",
            "returbelidetail.returbeliid",
            "returbeliheader.nobukti as returbelinobukti",
            "returbelidetail.productid",
            "product.nama as productnama",
            "returbelidetail.satuanid",
            "satuan.nama as satuannama",
            "returbelidetail.keterangan as keterangandetail",
            "returbelidetail.qty",
            "returbelidetail.harga",
            DB::raw('(returbelidetail.qty * returbelidetail.harga) AS totalharga'),
            "modifier.id as modified_by_id",
            "modifier.name as modified_by",
            "returbelidetail.created_at",
            "returbelidetail.updated_at",
        )
            ->leftJoin(DB::raw("returbeliheader"), 'returbelidetail.returbeliid', 'returbeliheader.id')
            ->leftJoin(DB::raw("product"), 'returbelidetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'returbelidetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'returbelidetail.modifiedby', 'modifier.id');

        $query->where("returbelidetail.returbeliid", "=", request()->returbeliid);

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->totalNominal = $query->sum(DB::raw('(returbelidetail.qty * returbelidetail.harga)'));
        $this->sort($query);
        $this->filter($query);

        $data = $query->get();
        // dd($data);
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('returbeliheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'productnama') {
            return $query->orderBy('product.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'satuannama') {
            return $query->orderBy('satuan.nama', $this->params['sortOrder']);
        }else if ($this->params['sortIndex'] == 'keterangandetail') {
            return $query->orderBy('returbelidetail.keterangan', $this->params['sortOrder']);
        } else {

            return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
        }
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'type') {
                            $query = $query->where('B.grp', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("format(" . $this->table . "." . $filters['field'] . ", 'dd-MM-yyyy HH:mm:ss') LIKE '%$filters[data]%'");
                        } else if ($filters['field'] == 'totalharga') {
                            $query->whereRaw('(returbelidetail.qty * returbelidetail.harga) LIKE ?', ["%$filters[data]%"]);
                        } else {
                            // $query = $query->where($this->table . '.' . $filters['field'], 'like', "%$filters[data]%");
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'type') {
                            $query = $query->orWhere('B.grp', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->orWhereRaw("format(" . $this->table . "." . $filters['field'] . ", 'dd-MM-yyyy HH:mm:ss') LIKE '%$filters[data]%'");
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->orWhereRaw('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'totalharga') {
                            $query->orWhereRaw('(returbelidetail.qty * returbelidetail.harga) LIKE ?', ["%$filters[data]%"]);
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->orWhereRaw('satuan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'keterangandetail') {
                            $query = $query->orWhereRaw('returbelidetail.keterangan', 'like', "%$filters[data]%");
                        } else {
                            // $query = $query->orWhere($this->table . '.' . $filters['field'], 'LIKE', "%$filters[data]%");
                            $query = $query->OrwhereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                default:

                    break;
            }

            $this->totalRows = $query->count();
            $this->totalPages = $this->params['limit'] > 0 ? ceil($this->totalRows / $this->params['limit']) : 1;
        }

        return $query;
    }

    public function paginate($query)
    {
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }

    public function getAll($id)
    {
        $query = DB::table('returbelidetail')
            ->select(
                "returbelidetail.id",
                "returbelidetail.returbeliid",
                "returbeliheader.nobukti as returbelinobukti",
                "returbelidetail.productid",
                "product.nama as productnama",
                "returbelidetail.keterangan as keterangandetail",
                "returbelidetail.qty",
                "returbelidetail.harga",
                DB::raw('(returbelidetail.qty * returbelidetail.harga) AS totalharga'),
                "returbelidetail.satuanid",
                "satuan.nama as satuannama",
                "returbelidetail.pembeliandetailid",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "returbelidetail.created_at",
                "returbelidetail.updated_at",
            )
            ->leftJoin(DB::raw("returbeliheader"), 'returbelidetail.returbeliid', 'returbeliheader.id')
            ->leftJoin(DB::raw("product"), 'returbelidetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'returbelidetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'returbelidetail.modifiedby', 'modifier.id')
            ->where('returbeliid', '=', $id);

        $data = $query->get();
        return $data;
    }

    public function processStore(ReturBeliHeader $returBeliHeader, array $data): ReturBeliDetail
    {
        // dd($data);
        //STORE RETUR JUAL DETAIL
        $returBeliDetail = new ReturBeliDetail();
        $returBeliDetail->returbeliid = $data['returbeliid'];
        $returBeliDetail->productid = $data['productid'];
        $returBeliDetail->satuanid = $data['satuanid'];
        $returBeliDetail->pembeliandetailid = $data['pembeliandetailid'];
        $returBeliDetail->returjualdetailid = $data['returjualdetailid'];
        $returBeliDetail->keterangan = $data['keterangan'] ?? "";
        $returBeliDetail->qty = $data['qty'];
        $returBeliDetail->harga = $data['harga'];
        $returBeliDetail->modifiedby = $data['modifiedby'];
        $returBeliDetail->save();

        if (!$returBeliDetail->save()) {
            throw new \Exception("Error storing Retur Jual Detail.");
        }

        // $pembelianDetail = PembelianDetail::where('id', $data['pembeliandetailid'])->first();

        // if ($pembelianDetail) {
        //     $pembelianDetail->update([
        //         'qtyretur' => $pembelianDetail->qtyretur + $returBeliDetail->qty,
        //     ]);

        //     // Log the update in LogTrail
        //     (new LogTrail())->processStore([
        //         'namatabel' => $pembelianDetail->getTable(),
        //         'postingdari' => 'EDIT ID RETUR JUAL DETAIL DI PEMBELIAN DETAIL DARI ADD RETUR BELI',
        //         'idtrans' => $pembelianDetail->id,
        //         'nobuktitrans' => $pembelianDetail->id,
        //         'aksi' => 'EDIT',
        //         'datajson' => $pembelianDetail->toArray(),
        //         'modifiedby' => auth('api')->user()->id,
        //     ]);
        // }

        // $pesananFinalDetail = PesananFinalDetail::where('id', $data['pesananfinaldetailid'])->first();

        // // dd($pesananFinalDetail);
        // if($pesananFinalDetail) {
        //     $pesananFinalDetail->update([
        //         'qtyreturbeli' => $pesananFinalDetail->qtyretur + $returBeliDetail->qty,
        //     ]);

        //     // Log the update in LogTrail
        //     (new LogTrail())->processStore([
        //         'namatabel' => $pesananFinalDetail->getTable(),
        //         'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI RETUR BELI DETAIL',
        //         'idtrans' => $pesananFinalDetail->id,
        //         'nobuktitrans' => $pesananFinalDetail->id,
        //         'aksi' => 'EDIT',
        //         'datajson' => $pesananFinalDetail->toArray(),
        //         'modifiedby' => auth('api')->user()->id,
        //     ]);
        // }

        // dd($returBeliDetail);

        return $returBeliDetail;
    }
}
