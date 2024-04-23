<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PesananDetail extends MyModel
{
    use HasFactory;

    protected $table = 'pesanandetail';

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
        $query = DB::table($this->table . ' as pesanandetail');

        $query->select(
            "pesanandetail.id",
            "pesanandetail.pesananid as pesananid",
            "pesananheader.nobukti as pesananheadernobukti",
            "product.id as productid",
            "product.nama as productnama",
            "pesanandetail.keterangan as keterangandetail",
            "pesanandetail.qty",
            "satuan.nama as satuannama",
            "satuan.id as satuanid",
            "modifier.id as modified_by_id",
            "modifier.name as modified_by",
            "pesanandetail.created_at",
            "pesanandetail.updated_at",

        )
            ->leftJoin(DB::raw("pesananheader"), 'pesanandetail.pesananid', 'pesananheader.id')
            ->leftJoin(DB::raw("product"), 'pesanandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'pesanandetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesanandetail.modifiedby', 'modifier.id');

        $query->where("pesanandetail.pesananid", "=", request()->pesananid);
        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

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
        $query = DB::table('pesanandetail')
            ->select(
                "pesanandetail.id",
                "pesananheader.id as pesananid",
                "pesananheader.nobukti as pesananheadernobukti",
                "product.id as productid",
                "product.nama as productnama",
                "product.nama",
                "satuan.nama as satuannama",
                "satuan.id",
                "pesanandetail.keterangan",
                "pesanandetail.qty",

                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "pesanandetail.created_at",
                "pesanandetail.updated_at",
            )
            ->leftJoin(DB::raw("pesananheader"), 'pesanandetail.pesananid', 'pesananheader.id')
            ->leftJoin(DB::raw("product"), 'pesanandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'pesanandetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesanandetail.modifiedby', 'modifier.id')
            ->where('pesananid', '=', $id);

        $data = $query->get();
        return $data;
    }


    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pesananheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'productnama') {
            return $query->orderBy('product.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'keterangandetail') {
            return $query->orderBy('pesanandetail.keterangan', $this->params['sortOrder']);
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
                        } else if ($filters['field'] == 'keterangandetail') {
                            $query = $query->where('pesanandetail.keterangan', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->where('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->where('satuan.nama', 'like', "%$filters[data]%");
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
                            $query = $query->orWhere('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'keterangandetail') {
                            $query = $query->orWhere('pesanandetail.keterangan', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->orWhere('satuan.nama', 'like', "%$filters[data]%");
                        }else {
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



    public function processStore(PesananHeader $pesananHeader, array $data): PesananDetail
    {
        $pesananDetail = new PesananDetail();
        $pesananDetail->pesananid = $data['pesananid'];
        $pesananDetail->productid = $data['productid'];
        $pesananDetail->qty = $data['qty'];
        $pesananDetail->satuanid = $data['satuanid'];
        $pesananDetail->keterangan = $data['keterangan'];

        $pesananDetail->save();

        if (!$pesananDetail->save()) {
            throw new \Exception("Error storing Pesanan Detail.");
        }

        return $pesananDetail;
    }

    public function paginate($query)
    {
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }
}
