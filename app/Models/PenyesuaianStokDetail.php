<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PenyesuaianStokDetail extends MyModel
{
    use HasFactory;

    protected $table = 'penyesuaianstokdetail';

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
        $query = DB::table($this->table . ' as penyesuaianstokdetail');

        $query->select(
            "penyesuaianstokdetail.id",
            "penyesuaianstokdetail.penyesuaianstokid as penyesuaianstokid",
            "penyesuaianstokheader.nobukti as penyesuaianstokheadernobukti",
            "product.id as productid",
            "product.nama as productnama",
            "penyesuaianstokdetail.qty",
            "penyesuaianstokdetail.harga",
            "penyesuaianstokdetail.total",
            "modifier.id as modified_by_id",
            "modifier.name as modified_by",
            "penyesuaianstokdetail.created_at",
            "penyesuaianstokdetail.updated_at",

        )
            ->leftJoin(DB::raw("penyesuaianstokheader"), 'penyesuaianstokdetail.penyesuaianstokid', 'penyesuaianstokheader.id')
            ->leftJoin(DB::raw("product"), 'penyesuaianstokdetail.productid', 'product.id')
            ->leftJoin(DB::raw("user as modifier"), 'penyesuaianstokdetail.modifiedby', 'modifier.id');

        $query->where("penyesuaianstokdetail.penyesuaianstokid", "=", request()->penyesuaianstokid);
        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->totalHarga = $query->sum(DB::raw('(penyesuaianstokdetail.qty * penyesuaianstokdetail.harga)'));

        if (request()->sortIndex != '') {
            $this->sort($query);
        }


        $this->filter($query);

        if (!request()->forReport) {
            $this->paginate($query);
        }
        $data = $query->get();


        return $data;
    }

    public function getAll($id)
    {
        $query = DB::table('penyesuaianstokdetail')
            ->select(
                "penyesuaianstokdetail.id",
                "penyesuaianstokdetail.penyesuaianstokid as penyesuaianstokid",
                "penyesuaianstokheader.nobukti as penyesuaianstokheadernobukti",
                "product.id as productid",
                "product.nama as productnama",
                "penyesuaianstokdetail.qty",
                "penyesuaianstokdetail.harga",
                "penyesuaianstokdetail.total",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "penyesuaianstokdetail.created_at",
                "penyesuaianstokdetail.updated_at",
            )
            ->leftJoin(DB::raw("penyesuaianstokheader"), 'penyesuaianstokdetail.penyesuaianstokid', 'penyesuaianstokheader.id')
            ->leftJoin(DB::raw("product"), 'penyesuaianstokdetail.productid', 'product.id')
            ->leftJoin(DB::raw("user as modifier"), 'penyesuaianstokdetail.modifiedby', 'modifier.id')
            ->where('penyesuaianstokid', '=', $id);

        $data = $query->get();
        return $data;
    }


    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('penyesuaianstokdetail.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'productnama') {
            return $query->orderBy('product.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'satuannama') {
            return $query->orderBy('satuan.nama', $this->params['sortOrder']);
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



    public function processStore(PenyesuaianStokHeader $penyesuaianStokHeader, array $data): PenyesuaianStokDetail
    {
        $penyesuaianStokDetail = new PenyesuaianStokDetail();
        $penyesuaianStokDetail->penyesuaianstokid = $data['penyesuaianstokid'];
        $penyesuaianStokDetail->productid = $data['productid'];
        $penyesuaianStokDetail->qty = $data['qty'];
        $penyesuaianStokDetail->harga = $data['harga'];
        $penyesuaianStokDetail->total = $data['total'];
        $penyesuaianStokDetail->modifiedby = $data['modifiedby'];

        $penyesuaianStokDetail->save();

        if (!$penyesuaianStokDetail->save()) {
            throw new \Exception("Error storing penyesuaian stok Detail.");
        }

        return $penyesuaianStokDetail;
    }

    public function paginate($query)
    {
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }
}
