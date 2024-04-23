<?php

namespace App\Models;

use CreateKartustokTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PenjualanDetail extends MyModel
{
    use HasFactory;

    protected $table = 'penjualandetail';

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
        // dd(request()->forInvoice);

        if (isset(request()->forInvoice) && request()->forInvoice) {
            // dd('test');
            $query = DB::table($this->table . ' as penjualandetail');

            $query->select(
                "penjualandetail.id",
                "penjualanheader.id as penjualanid",
                "penjualanheader.nobukti as penjualanheadernobukti",
                "penjualandetail.pesananfinaldetailid",
                "product.id as productid",
                "product.nama as productnama",
                "penjualandetail.keterangan as keterangandetail",
                "penjualandetail.qty",
                "penjualandetail.qtyreturjual",
                "penjualandetail.qtyreturbeli",
                "satuan.id as satuanid",
                "satuan.nama as satuannama",
                "penjualandetail.harga",
                DB::raw('((penjualandetail.qty - penjualandetail.qtyreturjual) * penjualandetail.harga) AS totalharga'),
                DB::raw('(penjualandetail.qty - penjualandetail.qtyreturjual) as qtyfinal'),
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "penjualandetail.created_at",
                "penjualandetail.updated_at",

            )
                ->leftJoin(DB::raw("penjualanheader"), 'penjualandetail.penjualanid', 'penjualanheader.id')
                ->leftJoin(DB::raw("product"), 'penjualandetail.productid', 'product.id')
                ->leftJoin(DB::raw("satuan"), 'penjualandetail.satuanid', 'satuan.id')
                ->leftJoin(DB::raw("user as modifier"), 'penjualandetail.modifiedby', 'modifier.id');

            $query->orderBy('product.nama','asc');

            $query->where("penjualandetail.penjualanid", "=", request()->penjualanid);

            return $query->get();
        } else {
            $query = DB::table($this->table . ' as penjualandetail');

            $query->select(
                "penjualandetail.id",
                "penjualanheader.id as penjualanid",
                "penjualanheader.nobukti as penjualanheadernobukti",
                "penjualandetail.pesananfinaldetailid",
                "product.id as productid",
                "product.nama as productnama",
                "penjualandetail.keterangan as keterangandetail",
                "penjualandetail.qty",
                "penjualandetail.qtyreturjual",
                "penjualandetail.qtyreturbeli",
                "satuan.id as satuanid",
                "satuan.nama as satuannama",
                "penjualandetail.harga",
                DB::raw('((penjualandetail.qty - penjualandetail.qtyreturjual) * penjualandetail.harga) AS totalharga'),
                DB::raw('(penjualandetail.qty - penjualandetail.qtyreturjual) as qtyfinal'),
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "penjualandetail.created_at",
                "penjualandetail.updated_at",

            )
                ->leftJoin(DB::raw("penjualanheader"), 'penjualandetail.penjualanid', 'penjualanheader.id')
                ->leftJoin(DB::raw("product"), 'penjualandetail.productid', 'product.id')
                ->leftJoin(DB::raw("satuan"), 'penjualandetail.satuanid', 'satuan.id')
                ->leftJoin(DB::raw("user as modifier"), 'penjualandetail.modifiedby', 'modifier.id');

            $query->where("penjualandetail.penjualanid", "=", request()->penjualanid);

            $this->totalRows = $query->count();
            $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
            $this->totalNominal = $query->sum(DB::raw('((penjualandetail.qty - penjualandetail.qtyreturjual) * penjualandetail.harga)'));
            $this->totalRetur = $query->sum(DB::raw('(penjualandetail.qty * penjualandetail.harga)'));

            $this->sort($query);
            $this->filter($query);
            $this->paginate($query);
            $data = $query->get();

            // dd($data);
            return $data;
        }
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('penjualanheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'productnama') {
            return $query->orderBy('product.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'satuannama') {
            return $query->orderBy('satuan.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'totalharga') {
            return $query->orderBy(DB::raw('(penjualandetail.qty * penjualandetail.harga)'), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'keterangandetail') {
            return $query->orderBy('penjualandetail.keterangan', $this->params['sortOrder']);
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
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->where('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'totalharga') {
                            $query->whereRaw('(penjualandetail.qty * penjualandetail.harga) LIKE ?', ["%$filters[data]%"]);
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->where('satuan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'keterangandetail') {
                            $query = $query->where('pe  njualandetail.keterangan', 'like', "%$filters[data]%");
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
                            $query->orWhereRaw('(penjualandetail.qty * penjualandetail.harga) LIKE ?', ["%$filters[data]%"]);
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->orWhereRaw('satuan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'keterangandetail') {
                            $query = $query->orWhereRaw('penjualandetail.keterangan', 'like', "%$filters[data]%");
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
        $query = DB::table('penjualandetail')
            ->select(
                "penjualandetail.id",
                "penjualanheader.id as penjualanid",
                "penjualanheader.nobukti as nobuktipesananfinal",
                "product.id as productid",
                "product.nama as productnama",
                "penjualandetail.pesananfinaldetailid",
                "penjualandetail.keterangan as keterangandetail",
                "penjualandetail.qty",
                "penjualandetail.qtyreturjual",
                "penjualandetail.qtyreturbeli",
                "penjualandetail.harga",
                DB::raw('(penjualandetail.qty * penjualandetail.harga) AS totalharga'),
                "satuan.nama as satuannama",
                "satuan.id as satuanid",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "penjualandetail.created_at",
                "penjualandetail.updated_at",
            )
            ->leftJoin(DB::raw("penjualanheader"), 'penjualandetail.penjualanid', 'penjualanheader.id')
            ->leftJoin(DB::raw("product"), 'penjualandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'penjualandetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'penjualandetail.modifiedby', 'modifier.id')
            ->where('penjualanid', '=', $id);

        $data = $query->get();
        // dd($data);
        return $data;
    }

    public function processStore(PenjualanHeader $penjualanHeader, array $data): PenjualanDetail
    {
        // dd($data);
        $penjualanDetail = new PenjualanDetail();
        $penjualanDetail->penjualanid = $data['penjualanid'];
        $penjualanDetail->productid = $data['productid'];
        $penjualanDetail->pesananfinaldetailid = $data['pesananfinaldetailid'];
        $penjualanDetail->keterangan = $data['keterangan'];
        $penjualanDetail->qty = $data['qty'];
        $penjualanDetail->qtyreturjual = $data['qtyreturjual'];
        $penjualanDetail->qtyreturbeli = $data['qtyreturbeli'];
        $penjualanDetail->satuanid = $data['satuanid'];
        $penjualanDetail->harga = $data['harga'];
        $penjualanDetail->modifiedby = $data['modifiedby'];
        $penjualanDetail->save();

        if (!$penjualanDetail->save()) {
            throw new \Exception("Error storing Pesanan Final Detail.");
        }

        // dd($penjualanDetail);

        return $penjualanDetail;
    }

    public function getEditAll()
    {
        $query = DB::table('penjualandetail')
            ->select(
                "penjualandetail.id",
                "penjualanheader.id as penjualanid",
                "penjualanheader.nobukti as nobuktipesananfinal",
                "product.id as productid",
                "product.nama as productnama",
                "penjualandetail.keterangan as keterangandetail",
                "penjualandetail.qty",
                "penjualandetail.qtyreturjual",
                "penjualandetail.qtyreturbeli",
                "penjualandetail.harga",
                DB::raw('(penjualandetail.qty * penjualandetail.harga) AS totalharga'),
                "satuan.nama as satuannama",
                "satuan.id as satuanid",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "penjualandetail.created_at",
                "penjualandetail.updated_at",
            )
            ->leftJoin(DB::raw("penjualanheader"), 'penjualandetail.penjualanid', 'penjualanheader.id')
            ->leftJoin(DB::raw("product"), 'penjualandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'penjualandetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'penjualandetail.modifiedby', 'modifier.id');
        $data = $query->get();
        return $data;
    }
}
