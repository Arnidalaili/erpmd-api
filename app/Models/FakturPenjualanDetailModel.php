<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FakturPenjualanDetailModel extends MyModel
{
    use HasFactory;

    protected $table = 'fakturpenjualandetail';

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

        $query = DB::table($this->table . ' as fakturpenjualandetail');

        if (isset(request()->forReport) && request()->forReport) {
            $query->select(
                "fakturpenjualandetail.id",
                "fakturpenjualanheader.id as fakturpenjualan_id",
                "fakturpenjualanheader.noinvoice as fakturpenjualanheader_nobukti",
                "customers.id as item_id",
                "customers.name as item_name",
                "fakturpenjualandetail.description",
                "fakturpenjualandetail.qty",
                "fakturpenjualandetail.hargasatuan",
                "fakturpenjualandetail.amount",
            )
                ->leftJoin(DB::raw("fakturpenjualanheader"), 'fakturpenjualandetail.fakturpenjualan_id', 'fakturpenjualanheader.id')
                ->leftJoin(DB::raw("customers"), 'fakturpenjualandetail.item_id', 'customers.id')
                ->leftJoin(DB::raw("user as modifier"), 'fakturpenjualandetail.modifiedby', 'modifier.id');

            $query->where($this->table . ".fakturpenjualan_id", "=", request()->fakturpenjualan_id);
        } else {
            $query->select(
                "fakturpenjualandetail.id",
                "fakturpenjualanheader.id as fakturpenjualan_id",
                "fakturpenjualanheader.noinvoice as fakturpenjualanheader_nobukti",
                "customers.id as item_id",
                "customers.name as item_name",
                "fakturpenjualandetail.description",
                "fakturpenjualandetail.qty",
                "fakturpenjualandetail.hargasatuan",
                "fakturpenjualandetail.amount",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "fakturpenjualandetail.created_at",
                "fakturpenjualandetail.updated_at",

            )
                ->leftJoin(DB::raw("fakturpenjualanheader"), 'fakturpenjualandetail.fakturpenjualan_id', 'fakturpenjualanheader.id')
                ->leftJoin(DB::raw("customers"), 'fakturpenjualandetail.item_id', 'customers.id')
                ->leftJoin(DB::raw("user as modifier"), 'fakturpenjualandetail.modifiedby', 'modifier.id');

            $query->where($this->table . ".fakturpenjualan_id", "=", request()->fakturpenjualan_id);

            $this->totalRows = $query->count();
            $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

            $this->sort($query);
            $this->filter($query);
            $this->paginate($query);
        }
        $data = $query->get();
        return $data;
    }

    public function getAll($id)
    {


        $query = DB::table('fakturpenjualandetail')
            ->select(
                "fakturpenjualandetail.id",
                "fakturpenjualanheader.id as fakturpenjualan_id",
                "fakturpenjualanheader.noinvoice as fakturpenjualanheader_nobukti",
                "customers.id as item_id",
                "customers.name as item_name",
                "fakturpenjualandetail.description",
                "fakturpenjualandetail.qty",
                "fakturpenjualandetail.hargasatuan",
                "fakturpenjualandetail.amount",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "fakturpenjualandetail.created_at",
                "fakturpenjualandetail.updated_at",

            )
            ->leftJoin(DB::raw("fakturpenjualanheader"), 'fakturpenjualandetail.fakturpenjualan_id', 'fakturpenjualanheader.id')
            ->leftJoin(DB::raw("customers"), 'fakturpenjualandetail.item_id', 'customers.id')
            ->leftJoin(DB::raw("user as modifier"), 'fakturpenjualandetail.modifiedby', 'modifier.id')
            ->where('fakturpenjualan_id', '=', $id);


        $data = $query->get();

        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('fakturpenjualanheader.noinvoice', $this->params['sortOrder']);
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

    public function processStore(FakturPenjualanHeaderModel $fakturPenjualanHeader, array $data): FakturPenjualanDetailModel
    {
        $fakturpenjualandetail = new FakturPenjualanDetailModel();
        $fakturpenjualandetail->fakturpenjualan_id = $data['fakturpenjualan_id'];
        $fakturpenjualandetail->item_id = $data['item_id'];
        $fakturpenjualandetail->description = $data['description'];
        $fakturpenjualandetail->qty = $data['qty'];
        $fakturpenjualandetail->hargasatuan = $data['hargasatuan'];
        $fakturpenjualandetail->amount = $data['amount'];


        $fakturpenjualandetail->save();

        if (!$fakturpenjualandetail->save()) {
            throw new \Exception("Error storing Faktur Penjualan Detail.");
        }

        return $fakturpenjualandetail;
    }

    public function paginate($query)
    {
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }
}
