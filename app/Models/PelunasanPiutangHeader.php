<?php

namespace App\Models;

use App\Services\RunningNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PelunasanPiutangHeader extends MyModel
{
    use HasFactory;

    protected $table = 'pelunasanpiutangheader';

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
        $query = DB::table($this->table . ' as pelunasanpiutangheader')
            ->select(
                "pelunasanpiutangheader.id",
                "pelunasanpiutangheader.nobukti",
                "pelunasanpiutangheader.tglbukti",
                "jenispelunasan.id as jenispelunasanpiutang",
                "jenispelunasan.text as jenispelunasanpiutangnama",
                "jenispelunasan.memo as jenispelunasanpiutangmemo",
                "alatbayar.id as alatbayarid",
                "alatbayar.nama as alatbayarnama",
                "customer.id as customerid",
                "customer.nama as customernama",
                "parameter.id as status",
                "parameter.text as statusnama",
                "parameter.memo as statusmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pelunasanpiutangheader.created_at',
                'pelunasanpiutangheader.updated_at'
            )
            ->leftJoin(DB::raw("customer"), 'pelunasanpiutangheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("alatbayar"), 'pelunasanpiutangheader.alatbayarid', 'alatbayar.id')
            ->leftJoin(DB::raw("parameter"), 'pelunasanpiutangheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanpiutangheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("parameter as jenispelunasan"), 'pelunasanpiutangheader.jenispelunasanpiutang', 'jenispelunasan.id');


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
            return $query->orderBy('pelunasanpiutangheader.nobukti', $this->params['sortOrder']);
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
                            $query = $query->where('parameter.memo', '=', "%$filters[data]%");
                        } else if ($filters['field'] == 'jenispelunasanpiutangmemo') {
                            $query = $query->where('jenispelunasan.memo', '=', "%$filters[data]%");
                        } else if ($filters['field'] == 'alatbayarnama') {
                            $query = $query->where('alatbayar.nama', '=', "%$filters[data]%");
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
                            $query = $query->orWhere('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'jenispelunasanpiutangmemo') {
                            $query = $query->orWhere('jenispelunasan.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'alatbayarnama') {
                            $query = $query->orWhere('alatbayar.nama', 'like', "%$filters[data]%");
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
        $query = DB::table('pelunasanpiutangheader')
            ->select(
                "pelunasanpiutangheader.id",
                "pelunasanpiutangheader.nobukti",
                "pelunasanpiutangheader.tglbukti",
                "jenispelunasan.id as jenispelunasanpiutang",
                "jenispelunasan.text as jenispelunasanpiutangnama",
                "jenispelunasan.memo as jenispelunasanpiutangmemo",
                "alatbayar.id as alatbayarid",
                "alatbayar.nama as alatbayarnama",
                "customer.id as customerid",
                "customer.nama as customernama",
                "parameter.id as status",
                "parameter.text as statusnama",
                "parameter.memo as statusmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pelunasanpiutangheader.created_at',
                'pelunasanpiutangheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'pelunasanpiutangheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanpiutangheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("alatbayar"), 'pelunasanpiutangheader.alatbayarid', 'alatbayar.id')
            ->leftJoin(DB::raw("customer"), 'pelunasanpiutangheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter as jenispelunasan"), 'pelunasanpiutangheader.jenispelunasanpiutang', 'jenispelunasan.id')
            ->where('pelunasanpiutangheader.id', $id);
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
                jenispelunasan.id as jenispelunasanpiutang,
                jenispelunasan.text as jenispelunasanpiutangnama,
                jenispelunasan.memo as jenispelunasanpiutangmemo,
                $this->table.alatbayarid,
                alatbayar.nama as alatbayarnama,
                $this->table.customerid,
                customer.nama as customernama,
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
            ->leftJoin(DB::raw("parameter"), 'pelunasanpiutangheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pelunasanpiutangheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("alatbayar"), 'pelunasanpiutangheader.alatbayarid', 'alatbayar.id')
            ->leftJoin(DB::raw("customer"), 'pelunasanpiutangheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter as jenispelunasan"), 'pelunasanpiutangheader.jenispelunasanpiutang', 'jenispelunasan.id');
    }

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->selectColumns($query);
        $query = $this->filter($query);

        $query = $this->sort($query);
        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        // dd($query);

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            tglbukti DATETIME,
            nobukti VARCHAR(100),
            jenispelunasanpiutang INT,
            jenispelunasanpiutangnama VARCHAR(500),
            jenispelunasanpiutangmemo VARCHAR(500),
            alatbayarid INT,
            alatbayarnama VARCHAR(100),
            customerid INT,
            customernama VARCHAR(100),
            status INT,
            statusnama VARCHAR(500),
            statusmemo VARCHAR(500),
            tglcetak DATETIME,
            modifiedby INT,
            modifiedby_name VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");
        DB::table($temp)->insertUsing(["id", "tglbukti", "nobukti",  "jenispelunasanpiutang", "jenispelunasanpiutangnama", "jenispelunasanpiutangmemo", "alatbayarid", "alatbayarnama", 
        "customerid", "customernama", "status", "statusnama", "statusmemo", "tglcetak", "modifiedby", "modifiedby_name", "created_at", "updated_at"], $query);
        return $temp;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status INT NULL,
            statusnama VARCHAR(100),
            jenispelunasanpiutang INT NULL,
            jenispelunasanpiutangnama VARCHAR(100)
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

        DB::statement("INSERT INTO $tempdefault (status,statusnama,jenispelunasanpiutang, jenispelunasanpiutangnama) VALUES (?,?,?,?)", [$status->id, $status->text, $jenispelunasan->id, $jenispelunasan->text]);

        $query = DB::table(
            $tempdefault
        )
            ->select(
                'status',
                'statusnama',
                'jenispelunasanpiutang',
                'jenispelunasanpiutangnama'
            );

        $data = $query->first();
        return $data;
    }

    public function processData($data)
    {
        $noBuktiPiutangIds = [];
        $tglBuktiPiutangIds = [];
        $nominalPiutangIds = [];
        $nominalBayarIds = [];
        $sisaPiutangIds = [];
        $ketDetailIds = [];
        $potonganIds = [];
        $ketPotonganIds = [];
        $nominalNbIds = [];

        // dd($data);

        foreach ($data as $detail) {
            $piutangIds = request()->piutangid;
            $noBuktiPiutangIds[] = $detail['nobuktipiutang'];
            $tglBuktiPiutangIds[] = $detail['tglbuktipiutang'];
            $nominalPiutangIds[] = $detail['nominalpiutang'];
            $nominalBayarIds[] = $detail['nominalbayar'];
            $sisaPiutangIds[] = $detail['sisapiutang'];
            $ketDetailIds[] = $detail['keterangandetail'];
            $potonganIds[] = $detail['potongan'];
            $ketPotonganIds[] = $detail['keteranganpotongan'];
            $nominalNbIds[] = $detail['nominalnotadebet'];
        }

        $data = [
            "tglbukti" => request()->tglbukti,
            "customerid" => request()->customerid,
            "alatbayarid" => request()->alatbayarid,
            "jenispelunasanpiutang" => request()->jenispelunasanpiutang,
            "status" => request()->status,
            "piutangid" => $piutangIds,
            "nobuktipiutang" => $noBuktiPiutangIds,
            "tglbuktipiutang" => $tglBuktiPiutangIds,
            "nominalpiutang" => $nominalPiutangIds,
            "nominalbayar" => $nominalBayarIds,
            "sisapiutang" => $sisaPiutangIds,
            "keterangandetail" => $ketDetailIds,
            "potongan" => $potonganIds,
            "keteranganpotongan" => $ketPotonganIds,
            "nominalnotadebet" => $nominalNbIds,
        ];

        return $data;
    }

    public function processStore(array $data): PelunasanPiutangHeader
    {
        $pelunasanpiutangHeader = new PelunasanPiutangHeader();

        /*STORE HEADER*/
        $group = 'PELUNASAN PIUTANG HEADER BUKTI';
        $subGroup = 'PELUNASAN PIUTANG HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));
        $pelunasanpiutangHeader->tglbukti = $tglbukti;
        $pelunasanpiutangHeader->jenispelunasanpiutang = $data['jenispelunasanpiutang'] ?? 34;
        $pelunasanpiutangHeader->customerid = $data['customerid'];
        $pelunasanpiutangHeader->alatbayarid = $data['alatbayarid'] ?? 1;
        $pelunasanpiutangHeader->keterangan = $data['keterangan'] ?? '';
        $pelunasanpiutangHeader->tglcetak = $data['tglcetak'] ?? '2023-11-11';
        $pelunasanpiutangHeader->status = $data['status'] ?? 1;
        $pelunasanpiutangHeader->modifiedby = auth('api')->user()->id;

        $pelunasanpiutangHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $pelunasanpiutangHeader->getTable(), date('Y-m-d', strtotime($data['tglbukti'])));

        if (!$pelunasanpiutangHeader->save()) {
            throw new \Exception("Error storing pelunasan piutang header.");
        }

        $pelunasanpiutangHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pelunasanpiutangHeader->getTable()),
            'postingdari' => strtoupper('ENTRY PELUNASAN PIUTANG'),
            'idtrans' => $pelunasanpiutangHeader->id,
            'nobuktitrans' => $pelunasanpiutangHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pelunasanpiutangHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        $details = [];

        for ($i = 0; $i < count($data['piutangid']); $i++) {
            $tglbuktipiutang = date('Y-m-d', strtotime($data['tglbuktipiutang'][$i]));

            $pelunasanPiutangDetail = (new PelunasanPiutangDetail())->processStore($pelunasanpiutangHeader, [
                'pelunasanpiutangid' => $pelunasanpiutangHeader->id,
                'piutangid' => $data['piutangid'][$i] ?? 0,
                'tglbuktipiutang' => $tglbuktipiutang ?? '',
                'nominalpiutang' => $data['nominalpiutang'][$i] ?? 0,
                'nominalbayar' => $data['nominalbayar'][$i] ?? 0,
                'nominalsisa' => $data['sisapiutang'][$i] ?? 0,
                'keterangandetail' => $data['keterangandetail'][$i] ?? '',
                'nominalpotongan' => $data['nominalpotongan'][$i] ?? 0,
                'keteranganpotongan' => $data['keteranganpotongan'][$i] ?? '',
                'nominalnotadebet' => $data['nominalnotadebet'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
            ]);
            $details[] = $pelunasanPiutangDetail->toArray();
        }

        foreach ($details as $detail) {
            DB::table('piutang')
                ->where('id', $detail['piutangid'])
                ->update(['sisa' => $detail['sisa']]);
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($pelunasanpiutangHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY PELUNASAN PIUTANG DETAIL'),
            'idtrans' =>  $pelunasanpiutangHeaderLogTrail->id,
            'nobuktitrans' => $pelunasanpiutangHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pelunasanPiutangDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

        return $pelunasanpiutangHeader;
    }

    public function processUpdate(PelunasanPiutangHeader $pelunasanPiutangHeader, array $data): PelunasanPiutangHeader
    {
        $nobuktiOld = $pelunasanPiutangHeader->nobukti;

        /*UPDATE HEADER*/
        $group = 'PELUNASAN PIUTANG HEADER BUKTI';
        $subGroup = 'PELUNASAN PIUTANG HEADER BUKTI';

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $pelunasanPiutangHeader->tglbukti = $tglbukti;
        $pelunasanPiutangHeader->jenispelunasanpiutang = $data['jenispelunasanpiutang'] ?? 34;
        $pelunasanPiutangHeader->customerid = $data['customerid'];
        $pelunasanPiutangHeader->alatbayarid = $data['alatbayarid'] ?? 1;
        $pelunasanPiutangHeader->keterangan = $data['keterangan'] ?? '';
        $pelunasanPiutangHeader->tglcetak = $data['tglcetak'] ?? '2023-11-11';
        $pelunasanPiutangHeader->status = $data['status'] ?? 1;
        $pelunasanPiutangHeader->modifiedby = auth('api')->user()->id;

        if (!$pelunasanPiutangHeader->save()) {
            throw new \Exception("Error updating Pelunasan Piutang Header.");
        }

        $pelunasanPiutangHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pelunasanPiutangHeader->getTable()),
            'postingdari' => strtoupper('EDIT PELUNASAN PIUTANG HEADER'),
            'idtrans' => $pelunasanPiutangHeader->id,
            'nobuktitrans' => $pelunasanPiutangHeader->nobukti,
            'aksi' => 'EDIT',
            'datajson' => $pelunasanPiutangHeader->toArray(),
            'modifiedby' => auth('api')->user()->id
        ]);

        /*DELETE PEMBELIAN DETAIL*/
        $pelunasanPiutangDetail = PelunasanPiutangDetail::where('pelunasanpiutangid', $pelunasanPiutangHeader->id)->lockForUpdate()->delete();

        /*STORE PEMBELIAN DETAIL*/
        $pelunasanDetails = [];
        for ($i = 0; $i < count($data['piutangid']); $i++) {
            $tglbuktipiutang = date('Y-m-d', strtotime($data['tglbuktipiutang'][$i]));

            $pelunasanPiutangDetail = (new PelunasanPiutangDetail())->processStore($pelunasanPiutangHeader, [
                'pelunasanpiutangid' => $pelunasanPiutangHeader->id,
                'piutangid' => $data['piutangid'][$i] ?? 0,
                'tglbuktipiutang' => $tglbuktipiutang ?? '',
                'nominalpiutang' => $data['nominalpiutang'][$i] ?? 0,
                'nominalbayar' => $data['nominalbayar'][$i] ?? 0,
                'nominalsisa' => $data['sisapiutang'][$i] ?? 0,
                'keterangandetail' => $data['keterangandetail'][$i] ?? '',
                'nominalpotongan' => $data['nominalpotongan'][$i] ?? 0,
                'keteranganpotongan' => $data['keteranganpotongan'][$i] ?? '',
                'nominalnotadebet' => $data['nominalnotadebet'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
            ]);

            $pelunasanDetails[] = $pelunasanPiutangDetail->toArray();
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($pelunasanPiutangHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('EDIT PELUNASAN PIUTANG DETAIL'),
            'idtrans' =>  $pelunasanPiutangHeaderLogTrail->id,
            'nobuktitrans' => $pelunasanPiutangHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pelunasanPiutangDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

        return $pelunasanPiutangHeader;
    }

    public function processDestroy($id, $postingDari = ''): PelunasanPiutangHeader
    {
        $query = DB::table('pelunasanpiutangheader')
            ->select(
                'pelunasanpiutangheader.nobukti',
                'pelunasanpiutangheader.customerid',
                'pelunasanpiutangdetail.piutangid',
                'piutang.nobukti as piutangnobukti'
            )
            ->leftJoin('pelunasanpiutangdetail', 'pelunasanpiutangheader.id', 'pelunasanpiutangdetail.pelunasanpiutangid')
            ->leftJoin('piutang', 'pelunasanpiutangdetail.piutangid', 'piutang.id')
            ->where('pelunasanpiutangheader.id', $id)
            ->get();

        $pelunasanpiutangHeader = PelunasanPiutangDetail::where('pelunasanpiutangid', '=', $id)->get();
        $dataDetail = $pelunasanpiutangHeader->toArray();

        /*DELETE EXISTING PELUNASAN PIUTANG HEADER*/
        $pelunasanpiutangHeader = new PelunasanPiutangHeader();
        $pelunasanpiutangHeader = $pelunasanpiutangHeader->lockAndDestroy($id);
        $pelunasanpiutangHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $pelunasanpiutangHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $pelunasanpiutangHeader->id,
            'nobuktitrans' => $pelunasanpiutangHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $pelunasanpiutangHeader->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        (new LogTrail())->processStore([
            'namatabel' => 'PELUNASAN PIUTANG DETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $pelunasanpiutangHeaderLogTrail['id'],
            'nobuktitrans' => $pelunasanpiutangHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->name
        ]);

        foreach ($query as $result) {

            $queryPiutang = DB::table('pelunasanpiutangdetail')
                ->select(
                    DB::raw('SUM(nominalbayar) as nominalbayar'),
                    DB::raw('MAX(nominalpiutang) as nominalpiutang'),
                    DB::raw('MAX(nominalpiutang) - SUM(nominalbayar) as nominalsisa')
                )
                ->where('pelunasanpiutangdetail.piutangid', $result->piutangid)
                ->groupBy('piutangid')
                ->first();

            if ($queryPiutang) {
                $nominalSisa = $queryPiutang->nominalsisa;
                DB::table('piutang')
                    ->where('id', $result->piutangid)
                    ->update(['sisa' => $nominalSisa]);
            }
        }

        return $pelunasanpiutangHeader;
    }

    public function getEditPelunasanPiutangHeader($customerid, $id)
    {
        $this->setRequestParameters();

        $tempPiutang = $this->createTempPiutang($customerid);
        $tempPelunasan = $this->createTempPelunasan($id, $customerid);

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            pelunasanpiutangid BIGINT,
            piutangid INT,
            nobuktipiutang VARCHAR(100),
            tglbuktipiutang DATETIME,
            nominalpiutang FLOAT, 
            nominalbayar FLOAT, 
            sisa FLOAT,
            keterangandetail VARCHAR(255),
            nominalpotongan FLOAT, 
            keteranganpotongan VARCHAR(255),
            nominalnotadebet FLOAT
        )");

        $pelunasan = DB::table($tempPelunasan)
            ->select(
                DB::raw("pelunasanpiutangid, piutangid, nobuktipiutang, tglbuktipiutang, nominalbayar, nominalpiutang, sisa, keterangandetail, nominalpotongan, keteranganpotongan, nominalnotadebet")
            );

        DB::table($temp)->insertUsing([
            "pelunasanpiutangid", "piutangid", "nobuktipiutang", "tglbuktipiutang", "nominalbayar", "nominalpiutang", "sisa", "keterangandetail", "nominalpotongan",  "keteranganpotongan", "nominalnotadebet"
        ], $pelunasan);

        $piutang = DB::table("$tempPiutang as a")
            ->select(
                DB::raw("null as pelunasanpiutangid, a.piutangid as piutangid, a.nobuktipiutang, a.tglbuktipiutang, null as nominalbayar, a.nominalpiutang, a.sisa, null as keterangandetail, null as nominalpotongan, null as keteranganpotongan, null as nominalnotadebet ")
            )
            ->leftJoin(DB::raw("$tempPelunasan as b"), "a.piutangid", "b.piutangid")
            ->whereNull("b.piutangid")
            ->whereRaw("a.sisa > 0");

        DB::table($temp)->insertUsing([
            "pelunasanpiutangid", "piutangid", "nobuktipiutang", "tglbuktipiutang", "nominalbayar", "nominalpiutang", "sisa", "keterangandetail", "nominalpotongan", "keteranganpotongan", "nominalnotadebet"
        ], $piutang);

        $data = DB::table($temp)
            ->select(DB::raw("$temp.piutangid as id, $temp.piutangid as piutangid,pelunasanpiutangid, nobuktipiutang, tglbuktipiutang as tglbuktipiutang, nominalpiutang as nominalpiutang, keterangandetail,
            (case when nominalbayar IS NULL then 0 else nominalbayar end) as nominalbayar,
            (case when nominalpotongan IS NULL then 0 else nominalpotongan end) as nominalpotongan,
            (case when sisa IS NULL then 0 else sisa end) as sisa,
            (case when nominalbayar IS NULL then 0 else (nominalbayar + coalesce(nominalpotongan,0)) end) as total"))
            ->get();

        return $data;
    }

    public function createTempPiutang($customerid)
    {
        $temp = 'tempPiutang' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            piutangid INT UNSIGNED,
            nobuktipiutang VARCHAR(100),
            tglbuktipiutang DATETIME,
            customerid INT, 
            nominalpiutang FLOAT,
            sisa FLOAT
        )");

        $fetch = DB::table('piutang')
            ->select(
                'piutang.id',
                'piutang.nobukti as nobuktipiutang',
                'piutang.tglbukti as tglbuktipiutang',
                'piutang.customerid',
                'piutang.nominal as nominalpiutang',
                DB::raw('(piutang.nominal - COALESCE(SUM(pelunasanpiutangdetail.nominalbayar), 0) - COALESCE(SUM(pelunasanpiutangdetail.nominalpotongan), 0)) as sisa')
            )
            ->leftJoin(DB::raw("pelunasanpiutangdetail"), 'pelunasanpiutangdetail.piutangid', 'piutang.id')
            ->where("piutang.customerid", "=", $customerid)
            ->groupBy('piutang.id', 'piutang.nobukti', 'piutang.nominal', 'piutang.customerid', 'piutang.tglbukti');

        DB::table($temp)->insertUsing([
            "piutangid", "nobuktipiutang", "tglbuktipiutang", "customerid", "nominalpiutang", "sisa",
        ], $fetch);

        return $temp;
    }

    public function createTempPelunasan($id, $customerid)
    {
        $tempo = 'tempPelunasan' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempo (
            pelunasanpiutangid BIGINT,
            piutangid INT,
            nobuktipiutang VARCHAR(100),
            tglbuktipiutang DATETIME,
            nominalpiutang FLOAT, 
            nominalbayar FLOAT, 
            sisa FLOAT,
            keterangandetail VARCHAR(255),
            nominalpotongan FLOAT, 
            keteranganpotongan VARCHAR(255),
            nominalnotadebet FLOAT
        )");

        $fetch = DB::table('pelunasanpiutangdetail as a')
            ->select(
                'a.pelunasanpiutangid',
                'a.piutangid',
                'piutang.nobukti as nobuktipiutang',
                'piutang.tglbukti as tglbuktipiutang',
                'piutang.nominal as nominalpiutang',
                'a.nominalbayar as nominalbayar',
                DB::raw('(SELECT (piutang.nominal - COALESCE(SUM(b.nominalbayar), 0) - COALESCE(SUM(b.nominalpotongan), 0)) FROM pelunasanpiutangdetail b WHERE b.piutangid = piutang.id) AS sisa'),
                'a.keterangan as keterangandetail',
                'a.nominalpotongan',
                'a.keteranganpotongan',
                'a.nominalnotadebet',
            )
            ->leftJoin('piutang', 'a.piutangid', '=', 'piutang.id')
            ->where('a.pelunasanpiutangid', $id);

        DB::table($tempo)->insertUsing([
            "pelunasanpiutangid", "piutangid", "nobuktipiutang", "tglbuktipiutang", "nominalpiutang", "nominalbayar", "sisa", "keterangandetail", "nominalpotongan", "keteranganpotongan", "nominalnotadebet"
        ], $fetch);

        return $tempo;
    }
}
