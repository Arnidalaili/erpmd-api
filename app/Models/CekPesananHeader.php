<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class CekPesananHeader extends MyModel
{
    use HasFactory;

    protected $table = 'pesananfinalheader';

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
        $tglpengiriman = date('Y-m-d', strtotime('+1 day'));
        $this->setRequestParameters();
        $query = DB::table($this->table . ' as pesananfinalheader')
            ->select(
                "pesananfinalheader.id",
                "customer.id as customerid",
                "customer.nama as customernama",
                "pesananfinalheader.nobukti",
                "pesananfinalheader.tglbukti",
                "pesananfinalheader.nobuktipenjualan",
                "pesananfinalheader.tglbuktipesanan",
                "pesananfinalheader.tglpengiriman",
                "pesananfinalheader.alamatpengiriman",
                "pesananfinalheader.keterangan",
                "pesananheader.id as pesananheaderid",
                "pesananheader.nobukti as nobuktipesanan",
                "pesananheader.tglbukti as tglbuktipesanan",
                "pesananheader.alamatpengiriman as alamatpengirimanpesanan",
                "pesananheader.tglpengiriman as tglpengirimanpesanan",
                "cekpesanan.id as cekpesanan",
                "cekpesanan.text as cekpesanannama",
                "cekpesanan.memo as cekpesananmemo",
            )
            ->leftJoin(DB::raw("parameter as cekpesanan"), 'pesananfinalheader.cekpesanan', 'cekpesanan.id')
            ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananfinalheader.status', 'parameter.id')
            ->leftJoin(DB::raw("pesananheader"), 'pesananfinalheader.pesananid', 'pesananheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananfinalheader.modifiedby', 'modifier.id')
            ->where('pesananfinalheader.tglpengiriman', $tglpengiriman);

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);
        $this->filter($query);

        if (!request()->ceklist) {
            $this->paginate($query);
        }
        $data = $query->get();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pesananfinalheader.nobukti', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'customernama') {
                            $query = $query->where('customer.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'nobuktipesanan') {
                            $query = $query->where('pesananheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglpengiriman' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            // $query = $query->where($this->table . '.' . $filters['field'], 'like', "%$filters[data]%");
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'customernama') {
                            $query = $query->orWhere('customer.nama', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglpengiriman' || $filters['field'] == 'tglbukti') {
                            $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
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

    public function findAll($id)
    {
        $query = DB::table('pesananfinalheader')
            ->select(
                "pesananfinalheader.id",
                "customer.id as customerid",
                "customer.nama as customernama",
                "customer.nama2 as customernama2",
                "pesananfinalheader.nobukti",
                "pesananfinalheader.tglbukti",
                "pesananfinalheader.nobuktipenjualan",
                "pesananfinalheader.tglbuktipesanan",
                "pesananfinalheader.tglpengiriman",
                "pesananfinalheader.alamatpengiriman",
                "pesananfinalheader.keterangan",
                "pesananheader.id as pesananheaderid",
                "pesananheader.nobukti as nobuktipesanan",
                "pesananheader.tglbukti as tglbuktipesanan",
                "pesananheader.alamatpengiriman as alamatpengirimanpesanan",
                "pesananheader.tglpengiriman as tglpengirimanpesanan",
                "cekpesanan.id as cekpesanan",
                "cekpesanan.text as cekpesanannama",
                "cekpesanan.memo as cekpesananmemo",
            )
            ->leftJoin(DB::raw("parameter as cekpesanan"), 'pesananfinalheader.cekpesanan', 'cekpesanan.id')
            ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananfinalheader.status', 'parameter.id')
            ->leftJoin(DB::raw("pesananheader"), 'pesananfinalheader.pesananid', 'pesananheader.id')
            ->where('pesananfinalheader.id', $id);
        $data = $query->first();
        return $data;
    }
}
