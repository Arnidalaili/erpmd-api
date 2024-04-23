<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class PelunasanHutangDetail extends MyModel
{
    use HasFactory;

    protected $table = 'pelunasanhutangdetail';

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

        $query = DB::table($this->table . ' as pelunasanhutangdetail');
        $query->select(
            "pelunasanhutangheader.supplierid",
            "pelunasanhutangdetail.id",
            "pelunasanhutangheader.id as pelunasanhutangid",
            "pelunasanhutangdetail.hutangid",
            "hutang.nobukti as nobuktihutang",
            "pelunasanhutangdetail.tglbuktihutang",
            "pelunasanhutangdetail.nominalhutang",
            "pelunasanhutangdetail.nominalbayar",
            "pelunasanhutangdetail.sisa",
            "pelunasanhutangdetail.keterangan",
            "pelunasanhutangdetail.nominalpotongan",
            "pelunasanhutangdetail.keteranganpotongan",
            "pelunasanhutangdetail.nominalnotadebet",
            "modifier.id as modified_by_id",
            "modifier.name as modified_by",
            "pelunasanhutangdetail.created_at",
            "pelunasanhutangdetail.updated_at",
        )
            ->leftJoin(DB::raw("pelunasanhutangheader"), 'pelunasanhutangdetail.pelunasanhutangid', 'pelunasanhutangheader.id')
            ->leftJoin(DB::raw("hutang"), 'pelunasanhutangdetail.hutangid', 'hutang.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanhutangdetail.modifiedby', 'modifier.id');

        $query->where("pelunasanhutangdetail.pelunasanhutangid", "=", request()->pelunasanhutangid);

        $supplierId = $query->get()->isNotEmpty() ? $query->get()->first()->supplierid : null;
        $hutang = new Hutang();
        $dataHutang =  $hutang->getHutang($supplierId);

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);
        $this->filter($query);

        // dd('test');
        $data = $query->get();
        return $data;
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

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pelunasanhutangheader.nobukti', $this->params['sortOrder']);
        } else {

            return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
        }
    }

    public function paginate($query)
    {
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }

    public function getAll($id)
    {
        $query = DB::table('pelunasanhutangdetail')
            ->select(
                "pelunasanhutangdetail.id",
                "pelunasanhutangheader.id as pelunasanhutangid",
                "pelunasanhutangdetail.hutangid",
                "hutang.nobukti as nobuktihutang",
                "pelunasanhutangdetail.tglbuktihutang",
                "pelunasanhutangdetail.nominalhutang",
                "pelunasanhutangdetail.nominalbayar",
                "pelunasanhutangdetail.sisa",
                "pelunasanhutangdetail.keterangan",
                "pelunasanhutangdetail.nominalpotongan",
                "pelunasanhutangdetail.keteranganpotongan",
                "pelunasanhutangdetail.nominalnotadebet",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "pelunasanhutangdetail.created_at",
                "pelunasanhutangdetail.updated_at",
            )
            ->leftJoin(DB::raw("pelunasanhutangheader"), 'pelunasanhutangdetail.pelunasanhutangid', 'pelunasanhutangheader.id')
            ->leftJoin(DB::raw("hutang"), 'pelunasanhutangdetail.hutangid', 'hutang.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanhutangdetail.modifiedby', 'modifier.id')
            ->where('pelunasanhutangid', '=', $id);

        $data = $query->get();
        return $data;
    }

    public function processStore(PelunasanHutangHeader $pelunasanHutangHeader, array $data): PelunasanHutangDetail
    {
        $pelunasanHutangDetail = new PelunasanHutangDetail();
        $pelunasanHutangDetail->pelunasanhutangid = $data['pelunasanhutangid'];
        $pelunasanHutangDetail->hutangid = $data['hutangid'];
        $pelunasanHutangDetail->tglbuktihutang = $data['tglbuktihutang'];
        $pelunasanHutangDetail->nominalhutang = $data['nominalhutang'];
        $pelunasanHutangDetail->nominalbayar = $data['nominalbayar'];
        $pelunasanHutangDetail->sisa = $data['nominalsisa'];
        $pelunasanHutangDetail->keterangan = $data['keterangandetail'];
        $pelunasanHutangDetail->nominalpotongan = $data['nominalpotongan'];
        $pelunasanHutangDetail->keteranganpotongan = $data['keteranganpotongan'];
        $pelunasanHutangDetail->nominalnotadebet = $data['nominalnotadebet'];
        $pelunasanHutangDetail->modifiedby = $data['modifiedby'];
        $pelunasanHutangDetail->save();

        if (!$pelunasanHutangDetail->save()) {
            throw new \Exception("Error storing Pelunasan Hutang Detail.");
        }

        // dd($pelunasanHutangDetail);

        return $pelunasanHutangDetail;
    }
}
