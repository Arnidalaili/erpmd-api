<?php

namespace App\Models;

use App\Services\RunningNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PelunasanHutangHeader extends MyModel
{
    use HasFactory;

    protected $table = 'pelunasanhutangheader';

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
        $query = DB::table($this->table . ' as pelunasanhutangheader')
            ->select(
                "pelunasanhutangheader.id",
                "pelunasanhutangheader.tglbukti",
                "pelunasanhutangheader.nobukti",
                "jenispelunasan.id as jenispelunasanhutang",
                "jenispelunasan.memo as jenispelunasanhutangmemo",
                "pelunasanhutangheader.alatbayarid",
                "alatbayar.nama as alatbayarnama",
                "pelunasanhutangheader.supplierid",
                "supplier.nama as suppliernama",
                "pelunasanhutangheader.tglcetak",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pelunasanhutangheader.created_at',
                'pelunasanhutangheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'pelunasanhutangheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanhutangheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("alatbayar"), 'pelunasanhutangheader.alatbayarid', 'alatbayar.id')
            ->leftJoin(DB::raw("supplier"), 'pelunasanhutangheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("parameter as jenispelunasan"), 'pelunasanhutangheader.jenispelunasanhutang', 'jenispelunasan.id');

        // dd($query->get());

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);
        $this->filter($query);

        $data = $query->get();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pelunasanhutangheader.nobukti', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'suppliernama') {
                            $query = $query->where('supplier.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'alatbayarnama') {
                            $query = $query->where('alatbayar.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'jenispelunasanhutangmemo') {
                            $query = $query->where('jenispelunasan.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'suppliernama') {
                            $query = $query->where('supplier.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'alatbayarnama') {
                            $query = $query->where('alatbayar.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->orWhere('parameter.memo', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'jenispelunasanhutangmemo') {
                            $query = $query->where('jenispelunasan.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                            $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
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

    public function paginate($query)
    {
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }

    public function findAll($id)
    {
        $query = DB::table('pelunasanhutangheader')
            ->select(
                "pelunasanhutangheader.id",
                "pelunasanhutangheader.tglbukti",
                "pelunasanhutangheader.nobukti",
                "jenispelunasan.id as jenispelunasanhutang",
                "jenispelunasan.memo as jenispelunasanhutangmemo",
                "jenispelunasan.text as jenispelunasanhutangnama",
                "pelunasanhutangheader.alatbayarid",
                "alatbayar.nama as alatbayarnama",
                "pelunasanhutangheader.supplierid",
                "supplier.nama as suppliernama",
                "pelunasanhutangheader.tglcetak",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "parameter.text as statusnama",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pelunasanhutangheader.created_at',
                'pelunasanhutangheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'pelunasanhutangheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanhutangheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("alatbayar"), 'pelunasanhutangheader.alatbayarid', 'alatbayar.id')
            ->leftJoin(DB::raw("supplier"), 'pelunasanhutangheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("parameter as jenispelunasan"), 'pelunasanhutangheader.jenispelunasanhutang', 'jenispelunasan.id')
            ->where('pelunasanhutangheader.id', $id);

        $data = $query->first();
        return $data;
    }

    public function selectColumns($query)
    {
        return $query->from(
            DB::raw($this->table)
        )
            ->select(
                DB::raw("
                $this->table.id,
                $this->table.tglbukti,
                $this->table.nobukti,
                jenispelunasan.id as jenispelunasanhutang,
                jenispelunasan.text as jenispelunasanhutangnama,
                jenispelunasan.memo as jenispelunasanhutangmemo,
                $this->table.alatbayarid,
                alatbayar.nama as alatbayarnama,
                $this->table.supplierid,
                supplier.nama as suppliernama,
                parameter.id as status,
                parameter.text as statusnama,
                parameter.memo as statusmemo,
                $this->table.tglcetak,
                modifier.id as modifiedby,
                modifier.name as modifiedby_name,
                $this->table.created_at,
                $this->table.updated_at
            ")
            )
            ->leftJoin(DB::raw("parameter"), 'pelunasanhutangheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanhutangheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("alatbayar"), 'pelunasanhutangheader.alatbayarid', 'alatbayar.id')
            ->leftJoin(DB::raw("supplier"), 'pelunasanhutangheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("parameter as jenispelunasan"), 'pelunasanhutangheader.jenispelunasanhutang', 'jenispelunasan.id');
    }

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->selectColumns($query);
        $query = $this->filter($query);
        $query = $this->sort($query);

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            tglbukti DATETIME,
            nobukti VARCHAR(100),
            jenispelunasanhutang INT,
            jenispelunasanhutangnama VARCHAR(500),
            jenispelunasanhutangmemo VARCHAR(500),
            alatbayarid INT,
            alatbayarnama VARCHAR(100),
            supplierid INT,
            suppliernama VARCHAR(100),
            status INT,
            statusnama VARCHAR(500),
            statusmemo VARCHAR(500),
            tglcetak DATETIME,
            modifiedby INT,
            modifiedby_name VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME,
            position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");
        DB::table($temp)->insertUsing([
            "id", "tglbukti", "nobukti", "jenispelunasanhutang", "jenispelunasanhutangnama", "jenispelunasanhutangmemo", "alatbayarid", "alatbayarnama", "supplierid", "suppliernama",
            "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
            "created_at", "updated_at"
        ], $query);
        return $temp;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status INT NULL,
            statusnama VARCHAR(100),
            jenispelunasanhutang INT NULL,
            jenispelunasanhutangnama VARCHAR(100)
        )");

        $status = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        $jenispelunasan = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'JENIS PELUNASAN')
            ->where('subgrp', '=', 'JENIS PELUNASAN')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        DB::statement("INSERT INTO $tempdefault (status,statusnama,jenispelunasanhutang, jenispelunasanhutangnama) VALUES (?,?,?,?)", [$status->id, $status->text, $jenispelunasan->id, $jenispelunasan->text]);

        $query = DB::table(
            $tempdefault
        )
            ->select(
                'status',
                'statusnama',
                'jenispelunasanhutang',
                'jenispelunasanhutangnama'
            );

        $data = $query->first();
        return $data;
    }

    public function processData($data)
    {
        $noBuktiHutangIds = [];
        $tglBuktiHutangIds = [];
        $nominalHutangIds = [];
        $nominalBayarIds = [];
        $sisaHutangIds = [];
        $ketDetailIds = [];
        $potonganIds = [];
        $ketPotonganIds = [];
        $nominalNbIds = [];

        // dd($data);

        foreach ($data as $detail) {
            $hutangIds = request()->hutangid;
            $noBuktiHutangIds[] = $detail['nobuktihutang'];
            $tglBuktiHutangIds[] = $detail['tglbuktihutang'];
            $nominalHutangIds[] = $detail['nominalhutang'];
            $nominalBayarIds[] = $detail['nominalbayar'];
            $sisaHutangIds[] = $detail['sisahutang'];
            $ketDetailIds[] = $detail['keterangandetail'];
            $potonganIds[] = $detail['potongan'];
            $ketPotonganIds[] = $detail['keteranganpotongan'];
            $nominalNbIds[] = $detail['nominalnotadebet'];
        }

        $data = [
            "tglbukti" => request()->tglbukti,
            "supplierid" => request()->supplierid,
            "alatbayarid" => request()->alatbayarid,
            "jenispelunasanhutang" => request()->jenispelunasanhutang,
            "status" => request()->status,
            "hutangid" => $hutangIds,
            "nobuktihutang" => $noBuktiHutangIds,
            "tglbuktihutang" => $tglBuktiHutangIds,
            "nominalhutang" => $nominalHutangIds,
            "nominalbayar" => $nominalBayarIds,
            "sisahutang" => $sisaHutangIds,
            "keterangandetail" => $ketDetailIds,
            "potongan" => $potonganIds,
            "keteranganpotongan" => $ketPotonganIds,
            "nominalnotadebet" => $nominalNbIds,
        ];

        // dd($data);
        return $data;
    }

    public function processStore(array $data): PelunasanHutangHeader
    {
        $pelunasanhutangHeader = new PelunasanHutangHeader();

        /*STORE HEADER*/
        $group = 'PELUNASAN HUTANG HEADER BUKTI';
        $subGroup = 'PELUNASAN HUTANG HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));
        $pelunasanhutangHeader->tglbukti = $tglbukti;
        $pelunasanhutangHeader->jenispelunasanhutang = $data['jenispelunasanhutang'] ?? 34;
        $pelunasanhutangHeader->supplierid = $data['supplierid'];
        $pelunasanhutangHeader->alatbayarid = $data['alatbayarid'] ?? 1;
        $pelunasanhutangHeader->keterangan = $data['keterangan'] ?? '';
        $pelunasanhutangHeader->tglcetak = $data['tglcetak'] ?? '2023-11-11';
        $pelunasanhutangHeader->status = $data['status'] ?? 1;
        $pelunasanhutangHeader->modifiedby = auth('api')->user()->id;

        $pelunasanhutangHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $pelunasanhutangHeader->getTable(), date('Y-m-d', strtotime($data['tglbukti'])));

        if (!$pelunasanhutangHeader->save()) {
            throw new \Exception("Error storing pelunasan hutang header.");
        }

        $pelunasanHutangHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pelunasanhutangHeader->getTable()),
            'postingdari' => strtoupper('ENTRY PELUNASAN HUTANG'),
            'idtrans' => $pelunasanhutangHeader->id,
            'nobuktitrans' => $pelunasanhutangHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pelunasanhutangHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        $details = [];

        for ($i = 0; $i < count($data['hutangid']); $i++) {
            $tglbuktihutang = date('Y-m-d', strtotime($data['tglbuktihutang'][$i]));

            $pelunasanHutangDetail = (new PelunasanHutangDetail())->processStore($pelunasanhutangHeader, [
                'pelunasanhutangid' => $pelunasanhutangHeader->id,
                'hutangid' => $data['hutangid'][$i] ?? 0,
                'tglbuktihutang' => $tglbuktihutang ?? '',
                'nominalhutang' => $data['nominalhutang'][$i] ?? 0,
                'nominalbayar' => $data['nominalbayar'][$i] ?? 0,
                'nominalsisa' => $data['sisahutang'][$i] ?? 0,
                'keterangandetail' => $data['keterangandetail'][$i] ?? '',
                'nominalpotongan' => $data['nominalpotongan'][$i] ?? 0,
                'keteranganpotongan' => $data['keteranganpotongan'][$i] ?? '',
                'nominalnotadebet' => $data['nominalnotadebet'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
            ]);
            $details[] = $pelunasanHutangDetail->toArray();
        }
        // dd($details);

        foreach ($details as $detail) {
            DB::table('hutang')
                ->where('id', $detail['hutangid'])
                ->update(['nominalsisa' => $detail['sisa']]);
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($pelunasanHutangHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY PELUNASAN HUTANG DETAIL'),
            'idtrans' =>  $pelunasanHutangHeaderLogTrail->id,
            'nobuktitrans' => $pelunasanhutangHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pelunasanHutangDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

        return $pelunasanhutangHeader;
    }

    public function processUpdate(PelunasanHutangHeader $pelunasanHutangHeader, array $data): PelunasanHutangHeader
    {
        $nobuktiOld = $pelunasanHutangHeader->nobukti;

        /*UPDATE HEADER*/
        $group = 'PELUNASAN HUTANG HEADER BUKTI';
        $subGroup = 'PELUNASAN HUTANG HEADER BUKTI';

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $pelunasanHutangHeader->tglbukti = $tglbukti;
        $pelunasanHutangHeader->jenispelunasanhutang = $data['jenispelunasanhutang'] ?? 34;
        $pelunasanHutangHeader->supplierid = $data['supplierid'];
        $pelunasanHutangHeader->alatbayarid = $data['alatbayarid'] ?? 1;
        $pelunasanHutangHeader->keterangan = $data['keterangan'] ?? '';
        $pelunasanHutangHeader->tglcetak = $data['tglcetak'] ?? '2023-11-11';
        $pelunasanHutangHeader->status = $data['status'] ?? 1;
        $pelunasanHutangHeader->modifiedby = auth('api')->user()->id;

        if (!$pelunasanHutangHeader->save()) {
            throw new \Exception("Error updating Pelunasan Hutang Header.");
        }

        $pelunasanHutangHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pelunasanHutangHeader->getTable()),
            'postingdari' => strtoupper('EDIT PELUNASAN HUTANG HEADER'),
            'idtrans' => $pelunasanHutangHeader->id,
            'nobuktitrans' => $pelunasanHutangHeader->nobukti,
            'aksi' => 'EDIT',
            'datajson' => $pelunasanHutangHeader->toArray(),
            'modifiedby' => auth('api')->user()->id
        ]);

        /*DELETE PEMBELIAN DETAIL*/
        $pelunasanHutangDetail = PelunasanHutangDetail::where('pelunasanhutangid', $pelunasanHutangHeader->id)->lockForUpdate()->delete();

        /*STORE PEMBELIAN DETAIL*/
        $pelunasanDetails = [];
        for ($i = 0; $i < count($data['hutangid']); $i++) {
            $tglbuktihutang = date('Y-m-d', strtotime($data['tglbuktihutang'][$i]));

            $pelunasanHutangDetail = (new PelunasanHutangDetail())->processStore($pelunasanHutangHeader, [
                'pelunasanhutangid' => $pelunasanHutangHeader->id,
                'hutangid' => $data['hutangid'][$i] ?? 0,
                'tglbuktihutang' => $tglbuktihutang ?? '',
                'nominalhutang' => $data['nominalhutang'][$i] ?? 0,
                'nominalbayar' => $data['nominalbayar'][$i] ?? 0,
                'nominalsisa' => $data['sisahutang'][$i] ?? 0,
                'keterangandetail' => $data['keterangandetail'][$i] ?? '',
                'nominalpotongan' => $data['nominalpotongan'][$i] ?? 0,
                'keteranganpotongan' => $data['keteranganpotongan'][$i] ?? '',
                'nominalnotadebet' => $data['nominalnotadebet'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
            ]);

            $pelunasanDetails[] = $pelunasanHutangDetail->toArray();
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($pelunasanHutangHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('EDIT PELUNASAN HUTANG DETAIL'),
            'idtrans' =>  $pelunasanHutangHeaderLogTrail->id,
            'nobuktitrans' => $pelunasanHutangHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pelunasanHutangDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

        return $pelunasanHutangHeader;
    }

    public function processDestroy($id, $postingDari = ''): PelunasanHutangHeader
    {
        $query = DB::table('pelunasanhutangheader')
            ->select(
                'pelunasanhutangheader.nobukti',
                'pelunasanhutangheader.supplierid',
                'pelunasanhutangdetail.hutangid',
                'hutang.nobukti as hutangnobukti'
            )
            ->leftJoin('pelunasanhutangdetail', 'pelunasanhutangheader.id', 'pelunasanhutangdetail.pelunasanhutangid')
            ->leftJoin('hutang', 'pelunasanhutangdetail.hutangid', 'hutang.id')
            ->where('pelunasanhutangheader.id', $id)
            ->get();

        $pelunasanhutangHeader = PelunasanHutangDetail::where('pelunasanhutangid', '=', $id)->get();
        $dataDetail = $pelunasanhutangHeader->toArray();

        /*DELETE EXISTING PELUNASAN HUTANG HEADER*/
        $pelunasanhutangHeader = new PelunasanhutangHeader();
        $pelunasanhutangHeader = $pelunasanhutangHeader->lockAndDestroy($id);
        $pelunasanhutangHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $pelunasanhutangHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $pelunasanhutangHeader->id,
            'nobuktitrans' => $pelunasanhutangHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $pelunasanhutangHeader->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        (new LogTrail())->processStore([
            'namatabel' => 'PELUNASAN HUTANG DETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $pelunasanhutangHeaderLogTrail['id'],
            'nobuktitrans' => $pelunasanhutangHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->name
        ]);

        foreach ($query as $result) {

            $queryHutang = DB::table('pelunasanhutangdetail')
                ->select(
                    DB::raw('SUM(nominalbayar) as nominalbayar'),
                    DB::raw('MAX(nominalhutang) as nominalhutang'),
                    DB::raw('MAX(nominalhutang) - SUM(nominalbayar) as nominalsisa')
                )
                ->where('pelunasanhutangdetail.hutangid', $result->hutangid)
                ->groupBy('hutangid')
                ->first();

            if ($queryHutang) {
                $nominalSisa = $queryHutang->nominalsisa;
                DB::table('hutang')
                    ->where('id', $result->hutangid)
                    ->update(['nominalsisa' => $nominalSisa]);
            }
        }

        return $pelunasanhutangHeader;
    }

    public function getEditPelunasanHutangHeader($supplierid, $id)
    {
        $this->setRequestParameters();

        $tempHutang = $this->createTempHutang($supplierid);
        $tempPelunasan = $this->createTempPelunasan($id, $supplierid);

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            pelunasanhutangid BIGINT,
            hutangid INT,
            nobuktihutang VARCHAR(100),
            tglbuktihutang DATETIME,
            nominalhutang FLOAT, 
            nominalbayar FLOAT, 
            nominalsisa FLOAT,
            keterangandetail VARCHAR(255),
            nominalpotongan FLOAT, 
            keteranganpotongan VARCHAR(255),
            nominalnotadebet FLOAT
        )");

        $pelunasan = DB::table($tempPelunasan)
            ->select(
                DB::raw("pelunasanhutangid, hutangid, nobuktihutang, tglbuktihutang, nominalbayar, nominalhutang, nominalsisa, keterangandetail, nominalpotongan, keteranganpotongan, nominalnotadebet")
            );

        DB::table($temp)->insertUsing([
            "pelunasanhutangid", "hutangid", "nobuktihutang", "tglbuktihutang", "nominalbayar", "nominalhutang", "nominalsisa", "keterangandetail", "nominalpotongan",  "keteranganpotongan", "nominalnotadebet"
        ], $pelunasan);

        $hutang = DB::table("$tempHutang as a")
            ->select(
                DB::raw("null as pelunasanhutangid, a.hutangid as hutangid, a.nobuktihutang, a.tglbuktihutang, null as nominalbayar, a.nominalhutang, a.nominalsisa, null as keterangandetail, null as nominalpotongan, null as keteranganpotongan, null as nominalnotadebet ")
            )
            ->leftJoin(DB::raw("$tempPelunasan as b"), "a.hutangid", "b.hutangid")
            ->whereNull("b.hutangid")
            ->whereRaw("a.nominalsisa > 0");

        DB::table($temp)->insertUsing([
            "pelunasanhutangid", "hutangid", "nobuktihutang", "tglbuktihutang", "nominalbayar", "nominalhutang", "nominalsisa", "keterangandetail", "nominalpotongan", "keteranganpotongan", "nominalnotadebet"
        ], $hutang);

        $data = DB::table($temp)
            ->select(DB::raw("$temp.hutangid as id, $temp.hutangid as hutangid,pelunasanhutangid, nobuktihutang, tglbuktihutang as tglbuktihutang, nominalhutang as nominalhutang, keterangandetail,
            (case when nominalbayar IS NULL then 0 else nominalbayar end) as nominalbayar,
            (case when nominalpotongan IS NULL then 0 else nominalpotongan end) as nominalpotongan,
            (case when nominalsisa IS NULL then 0 else nominalsisa end) as nominalsisa,
            (case when nominalbayar IS NULL then 0 else (nominalbayar + coalesce(nominalpotongan,0)) end) as total"))
            ->get();

        return $data;
    }

    public function createTempHutang($supplierid)
    {
        $temp = 'tempHutang' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            hutangid INT UNSIGNED,
            nobuktihutang VARCHAR(100),
            tglbuktihutang DATETIME,
            supplierid INT, 
            nominalhutang FLOAT,
            nominalsisa FLOAT
        )");

        $fetch = DB::table('hutang')
            ->select(
                'hutang.id',
                'hutang.nobukti as nobuktihutang',
                'hutang.tglbukti as tglbuktihutang',
                'hutang.supplierid',
                'hutang.nominalhutang as nominalhutang',
                DB::raw('(hutang.nominalhutang - COALESCE(SUM(pelunasanhutangdetail.nominalbayar), 0) - COALESCE(SUM(pelunasanhutangdetail.nominalpotongan), 0)) as nominalsisa')
            )
            ->leftJoin(DB::raw("pelunasanhutangdetail"), 'pelunasanhutangdetail.hutangid', 'hutang.id')
            ->where("hutang.supplierid", "=", $supplierid)
            ->groupBy('hutang.id', 'hutang.nobukti', 'hutang.nominalhutang', 'hutang.supplierid', 'hutang.tglbukti');

        DB::table($temp)->insertUsing([
            "hutangid", "nobuktihutang", "tglbuktihutang", "supplierid", "nominalhutang", "nominalsisa",
        ], $fetch);

        return $temp;
    }

    public function createTempPelunasan($id, $supplierid)
    {
        $tempo = 'tempPelunasan' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempo (
            pelunasanhutangid BIGINT,
            hutangid INT,
            nobuktihutang VARCHAR(100),
            tglbuktihutang DATETIME,
            nominalhutang FLOAT, 
            nominalbayar FLOAT, 
            nominalsisa FLOAT,
            keterangandetail VARCHAR(255),
            nominalpotongan FLOAT, 
            keteranganpotongan VARCHAR(255),
            nominalnotadebet FLOAT
        )");

        $fetch = DB::table('pelunasanhutangdetail as a')
            ->select(
                'a.pelunasanhutangid',
                'a.hutangid',
                'hutang.nobukti as nobuktihutang',
                'hutang.tglbukti as tglbuktihutang',
                'hutang.nominalhutang as nominalhutang',
                'a.nominalbayar as nominalbayar',
                DB::raw('(SELECT (hutang.nominalhutang - COALESCE(SUM(b.nominalbayar), 0) - COALESCE(SUM(b.nominalpotongan), 0)) FROM pelunasanhutangdetail b WHERE b.hutangid = hutang.id) AS sisa'),
                'a.keterangan as keterangandetail',
                'a.nominalpotongan',
                'a.keteranganpotongan',
                'a.nominalnotadebet',
            )
            ->leftJoin('hutang', 'a.hutangid', '=', 'hutang.id')
            ->where('a.pelunasanhutangid', $id);

        DB::table($tempo)->insertUsing([
            "pelunasanhutangid", "hutangid", "nobuktihutang", "tglbuktihutang", "nominalhutang", "nominalbayar", "nominalsisa", "keterangandetail", "nominalpotongan", "keteranganpotongan", "nominalnotadebet"
        ], $fetch);

        return $tempo;
    }
}
