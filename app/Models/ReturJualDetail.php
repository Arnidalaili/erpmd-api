<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReturJualDetail extends MyModel
{
    use HasFactory;

    protected $table = 'returjualdetail';

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

        $query = DB::table($this->table . ' as returjualdetail');

        $query->select(
            "returjualdetail.id",
            "returjualdetail.returjualid",
            "returjualheader.nobukti as returjualnobukti",
            "returjualdetail.productid",
            "product.nama as productnama",
            "returjualdetail.keterangan as keterangandetail",
            "returjualdetail.qty",
            "satuan.id as satuanid",
            "satuan.nama as satuannama",
            "returjualdetail.harga",
            DB::raw('(returjualdetail.qty * returjualdetail.harga) AS totalharga'),
            "modifier.id as modified_by_id",
            "modifier.name as modified_by",
            "returjualdetail.created_at",
            "returjualdetail.updated_at",

        )
            ->leftJoin(DB::raw("returjualheader"), 'returjualdetail.returjualid', 'returjualheader.id')
            ->leftJoin(DB::raw("product"), 'returjualdetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'returjualdetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'returjualdetail.modifiedby', 'modifier.id');


        $query->where("returjualdetail.returjualid", "=", request()->returjualid);

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->totalNominal = $query->sum(DB::raw('(returjualdetail.qty * returjualdetail.harga)'));

        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);
        $data = $query->get();

        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('returjualheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'productnama') {
            return $query->orderBy('product.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'satuannama') {
            return $query->orderBy('satuan.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'keterangandetail') {
            return $query->orderBy('returjualdetail.keterangan', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'totalharga') {
            return $query->orderBy(DB::raw('(returjualdetail.qty * returjualdetail.harga)'), $this->params['sortOrder']);
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
                            $query->whereRaw('(returjualdetail.qty * returjualdetail.harga) LIKE ?', ["%$filters[data]%"]);
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
                        } else if ($filters['field'] == 'totalharga') {
                            $query->orWhereRaw('(returjualdetail.qty * returjualdetail.harga) LIKE ?', ["%$filters[data]%"]);
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->orWhereRaw('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->orWhereRaw('satuan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'keterangandetail') {
                            $query = $query->orWhereRaw('returjualdetail.keterangan', 'like', "%$filters[data]%");
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
        $query = DB::table('returjualdetail')
            ->select(
                "returjualdetail.id",
                "returjualheader.nobukti as returjualnobukti",
                "returjualdetail.productid",
                "product.nama as productnama",
                "returjualdetail.keterangan as keterangandetail",
                "returjualdetail.qty",
                "returjualdetail.harga",
                DB::raw('(returjualdetail.qty * returjualdetail.harga) AS totalharga'),
                "returjualdetail.satuanid",
                "satuan.nama as satuannama",
                "returjualdetail.penjualandetailid",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "returjualdetail.created_at",
                "returjualdetail.updated_at",
            )
            ->leftJoin(DB::raw("returjualheader"), 'returjualdetail.returjualid', 'returjualheader.id')
            ->leftJoin(DB::raw("product"), 'returjualdetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'returjualdetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'returjualdetail.modifiedby', 'modifier.id')
            ->where('returjualid', '=', $id);

        $data = $query->get();
        // dd($data);
        return $data;
    }

    public function processStore(ReturJualHeader $returJualHeader, array $data): ReturJualDetail
    {
        // dd($data);
        //STORE RETUR JUAL DETAIL
        $returJualDetail = new ReturJualDetail();
        $returJualDetail->returjualid = $data['returjualid'];
        $returJualDetail->productid = $data['productid'];
        $returJualDetail->satuanid = $data['satuanid'];
        $returJualDetail->penjualandetailid = $data['penjualandetailid'];
        $returJualDetail->keterangan = $data['keterangan'] ?? "";
        $returJualDetail->qty = $data['qty'];
        $returJualDetail->harga = $data['harga'];
        $returJualDetail->modifiedby = $data['modifiedby'];
        $returJualDetail->save();

        if (!$returJualDetail->save()) {
            throw new \Exception("Error storing Retur Jual Detail.");
        }

        // dd($returJualDetail);

        $penjualanDetail = PenjualanDetail::where('id', $data['penjualandetailid'])->first();
        // dd($penjualanDetail->qtyretur + $returJualDetail->qty);

        // if ($penjualanDetail) {
        //     $penjualanDetail->update([
        //         'qtyretur' => $returJualDetail->qty,
        //     ]);

        //     // Log the update in LogTrail
        //     (new LogTrail())->processStore([
        //         'namatabel' => $penjualanDetail->getTable(),
        //         'postingdari' => 'EDIT ID RETUR JUAL DETAIL DI PENJUALAN DETAIL DARI ADD RETUR JUAL',
        //         'idtrans' => $penjualanDetail->id,
        //         'nobuktitrans' => $penjualanDetail->id,
        //         'aksi' => 'EDIT',
        //         'datajson' => $penjualanDetail->toArray(),
        //         'modifiedby' => auth('api')->user()->id,
        //     ]);
        // }

        // $pesananFinalDetail = PesananFinalDetail::where('id', $data['pesananfinaldetailid'])->first();

        // if($pesananFinalDetail) {
        //     $pesananFinalDetail->update([
        //         'qtyreturjual' => $returJualDetail->qty,
        //     ]);

        //     // Log the update in LogTrail
        //     (new LogTrail())->processStore([
        //         'namatabel' => $pesananFinalDetail->getTable(),
        //         'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI RETUR JUAL DETAIL',
        //         'idtrans' => $pesananFinalDetail->id,
        //         'nobuktitrans' => $pesananFinalDetail->id,
        //         'aksi' => 'EDIT',
        //         'datajson' => $pesananFinalDetail->toArray(),
        //         'modifiedby' => auth('api')->user()->id,
        //     ]);
        // }
        // dd($penjualanDetail);

        return $returJualDetail;
    }
}
