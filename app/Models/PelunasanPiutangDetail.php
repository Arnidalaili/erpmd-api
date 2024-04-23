<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PelunasanPiutangDetail extends MyModel
{
    use HasFactory;

    protected $table = 'pelunasanpiutangdetail';

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
        // dd(request()->pelunasanpiutangid);
        $this->setRequestParameters();

        $query = DB::table($this->table . ' as pelunasanpiutangdetail');

        $query->select(
            "pelunasanpiutangdetail.id",
            "pelunasanpiutangheader.id as pelunasanpiutangid",
            "pelunasanpiutangdetail.piutangid",
            "piutang.nobukti as nobuktipiutang",
            "pelunasanpiutangdetail.tglbuktipiutang",
            "pelunasanpiutangdetail.nominalpiutang",
            "pelunasanpiutangdetail.nominalbayar",
            "pelunasanpiutangdetail.sisa",
            "pelunasanpiutangdetail.keterangan",
            "pelunasanpiutangdetail.nominalpotongan",
            "pelunasanpiutangdetail.keteranganpotongan",
            "pelunasanpiutangdetail.nominalnotadebet",
            "modifier.id as modified_by_id",
            "modifier.name as modified_by",
            "pelunasanpiutangdetail.created_at",
            "pelunasanpiutangdetail.updated_at",
        )
            ->leftJoin(DB::raw("pelunasanpiutangheader"), 'pelunasanpiutangdetail.pelunasanpiutangid', 'pelunasanpiutangheader.id')
            ->leftJoin(DB::raw("piutang"), 'pelunasanpiutangdetail.piutangid', 'piutang.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanpiutangdetail.modifiedby', 'modifier.id');

        $query->where("pelunasanpiutangdetail.pelunasanpiutangid", "=", request()->pelunasanpiutangid);

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
            return $query->orderBy('pelunasanpiutangdetail.tglbuktipiutang', $this->params['sortOrder']);
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
        $query =  DB::table('pelunasanpiutangdetail')
            ->select(
                "pelunasanpiutangdetail.id",
                "pelunasanpiutangheader.id as pelunasanpiutangid",
                "pelunasanpiutangdetail.piutangid",
                "piutang.nobukti as nobuktipiutang",
                "pelunasanpiutangdetail.tglbuktipiutang",
                "pelunasanpiutangdetail.nominalpiutang",
                "pelunasanpiutangdetail.nominalbayar",
                "pelunasanpiutangdetail.sisa",
                "pelunasanpiutangdetail.keterangan",
                "pelunasanpiutangdetail.nominalpotongan",
                "pelunasanpiutangdetail.keteranganpotongan",
                "pelunasanpiutangdetail.nominalnotadebet",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "pelunasanpiutangdetail.created_at",
                "pelunasanpiutangdetail.updated_at",
            )
            ->leftJoin(DB::raw("pelunasanpiutangheader"), 'pelunasanpiutangdetail.pelunasanpiutangid', 'pelunasanpiutangheader.id')
            ->leftJoin(DB::raw("piutang"), 'pelunasanpiutangdetail.piutangid', 'piutang.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanpiutangdetail.modifiedby', 'modifier.id')
            ->where('pelunasanpiutangid', '=', $id);


        $data = $query->get();


        return $data;
    }

    public function processStore(PelunasanPiutangHeader $pelunasanPiutangHeader, array $data): PelunasanPiutangDetail
    {
        $pelunasanPiutangDetail = new PelunasanPiutangDetail();
        $pelunasanPiutangDetail->pelunasanpiutangid = $data['pelunasanpiutangid'];
        $pelunasanPiutangDetail->piutangid = $data['piutangid'];
        $pelunasanPiutangDetail->tglbuktipiutang = $data['tglbuktipiutang'];
        $pelunasanPiutangDetail->nominalpiutang = $data['nominalpiutang'];
        $pelunasanPiutangDetail->nominalbayar = $data['nominalbayar'];
        $pelunasanPiutangDetail->sisa = $data['nominalsisa'];
        $pelunasanPiutangDetail->keterangan = $data['keterangandetail'];
        $pelunasanPiutangDetail->nominalpotongan = $data['nominalpotongan'];
        $pelunasanPiutangDetail->keteranganpotongan = $data['keteranganpotongan'];
        $pelunasanPiutangDetail->nominalnotadebet = $data['nominalnotadebet'];
        $pelunasanPiutangDetail->modifiedby = $data['modifiedby'];
        $pelunasanPiutangDetail->save();

        if (!$pelunasanPiutangDetail->save()) {
            throw new \Exception("Error storing Pelunasan Piutang Detail.");
        }

        // dd($pelunasanPiutangDetail);

        return $pelunasanPiutangDetail;
    }
}
