<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransaksiBelanja extends MyModel
{
    use HasFactory;

    protected $table = 'transaksibelanja';

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
        $query = DB::table($this->table . ' as transaksibelanja')
            ->select(
                "transaksibelanja.id",
                "transaksibelanja.perkiraanid",
                "perkiraan.nama as perkiraannama",
                "perkiraan.seqno as perkiraanseqno",
                "transaksibelanja.tglbukti",
                "transaksibelanja.karyawanid",
                "karyawan.nama as karyawannama",
                "transaksibelanja.pembelianid",
                "pembelianheader.nobukti as pembeliannobukti",
                "transaksibelanja.nominal",
                "transaksibelanja.keterangan",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "transaksibelanja.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'transaksibelanja.created_at',
                'transaksibelanja.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'transaksibelanja.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'transaksibelanja.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("karyawan"), 'transaksibelanja.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("perkiraan"), 'transaksibelanja.perkiraanid', 'perkiraan.id')
            ->leftJoin(DB::raw("pembelianheader"), 'transaksibelanja.pembelianid', 'pembelianheader.id');

        if (request()->tgldari && request()->tglsampai) {
            $query->whereBetween($this->table . '.tglbukti', [date('Y-m-d', strtotime(request()->tgldari)), date('Y-m-d', strtotime(request()->tglsampai))]);
        }


        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);

        // dd($query->get());
        foreach ($query->get() as $item) {
            if ($item->perkiraanseqno === '1') {
                $this->totalPanjar += $item->nominal;
            } else {
                $this->totalBiaya += $item->nominal;
            }
        }

        $this->totalSisa = $this->totalPanjar - $this->totalBiaya;

        // dd($this->totalPanjar, $this->totalBiaya, $this->totalSisa);

        $data = $query->get();

        // dd($data);
        return $data;
    }

    public function findAll($id)
    {
        $query = DB::table('transaksibelanja')
            ->select(
                "transaksibelanja.id",
                "transaksibelanja.perkiraanid",
                "perkiraan.nama as perkiraannama",
                "transaksibelanja.tglbukti",
                "transaksibelanja.karyawanid",
                "karyawan.nama as karyawannama",
                "transaksibelanja.pembelianid",
                "pembelianheader.nobukti as pembeliannobukti",
                "transaksibelanja.nominal",
                "transaksibelanja.keterangan",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "parameter.text as statusnama",
                "transaksibelanja.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'transaksibelanja.created_at',
                'transaksibelanja.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'transaksibelanja.status', 'parameter.id')
            ->leftJoin(DB::raw("perkiraan"), 'transaksibelanja.perkiraanid', 'perkiraan.id')
            ->leftJoin(DB::raw("user as modifier"), 'transaksibelanja.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("karyawan"), 'transaksibelanja.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("pembelianheader"), 'transaksibelanja.pembelianid', 'pembelianheader.id')
            ->where('transaksibelanja.id', $id);

        $data = $query->first();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'tglbukti') {
            return $query->orderBy('transaksibelanja.tglbukti', $this->params['sortOrder'])
                ->orderBy('karyawan.nama', 'ASC')->orderBy('perkiraan.seqno', 'ASC');
        } else if ($this->params['sortIndex'] == 'perkiraannama') {
            return $query->orderBy('perkiraan.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'karyawannama') {
            return $query->orderBy('karyawan.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'pembeliannobukti') {
            return $query->orderBy('pembelianheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'perkiraanseqno') {
            return $query->orderBy('perkiraan.seqno', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'modifiedby_name') {
            return $query->orderBy('modifier.name', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
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
                        if ($filters['field'] == 'karyawannama') {
                            $query = $query->where('karyawan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'perkiraannama') {
                            $query = $query->where('perkiraan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'pembeliannobukti') {
                            $query = $query->where('pembelianheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->where('modifier.name', 'LIKE', "%$filters[data]%");
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'karyawannama') {
                            $query = $query->where('karyawan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->orWhere('parameter.memo', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'perkiraannama') {
                            $query = $query->where('perkiraan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'pembeliannobukti') {
                            $query = $query->orWhereRaw('pembelianheader.nobukti', 'like', "%$filters[data]%");
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

    public function selectColumns($query)
    {
        return $query->from(
            DB::raw($this->table)
        )
            ->select(
                DB::raw("
                $this->table.id,
                $this->table.perkiraanid,
                perkiraan.nama as perkiraannama,
                $this->table.tglbukti,
                $this->table.karyawanid,
                karyawan.nama as karyawannama,
                $this->table.pembelianid,
                pembelianheader.nobukti as pembeliannobukti,
                $this->table.nominal,
                $this->table.keterangan,
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
            ->leftJoin(DB::raw("parameter"), 'transaksibelanja.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'transaksibelanja.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("karyawan"), 'transaksibelanja.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("perkiraan"), 'transaksibelanja.perkiraanid', 'perkiraan.id')
            ->leftJoin(DB::raw("pembelianheader"), 'transaksibelanja.pembelianid', 'pembelianheader.id');
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
            perkiraanid INT,
            perkiraannama VARCHAR(100),
            tglbukti DATETIME,
            karyawanid INT,
            karyawannama VARCHAR(100),
            pembelianid INT,
            pembeliannobukti VARCHAR(100),
            nominal FLOAT,
            keterangan VARCHAR(500),
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
            "id", "perkiraanid", "perkiraannama", "tglbukti", "karyawanid", "karyawannama", "pembelianid", "pembeliannobukti", "nominal", "keterangan", "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
            "created_at", "updated_at"
        ], $query);
        return $temp;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status INT NULL,
            statusnama VARCHAR(100)
        )");

        $status = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        DB::statement("INSERT INTO $tempdefault (status,statusnama) VALUES (?,?)", [$status->id, $status->text]);

        $query = DB::table(
            $tempdefault
        )
            ->select(
                'status',
                'statusnama'
            );

        $data = $query->first();
        return $data;
    }

    public function processStore(array $data): TransaksiBelanja
    {
        // dd($data);
        $transaksiBelanja = new TransaksiBelanja();

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $transaksiBelanja->perkiraanid = $data['perkiraanid'];
        $transaksiBelanja->tglbukti = $tglbukti;
        $transaksiBelanja->karyawanid = $data['karyawanid'];
        $transaksiBelanja->pembelianid = $data['pembelianid'] ?? 0;
        $transaksiBelanja->nominal = $data['nominal'] ?? 0;
        $transaksiBelanja->keterangan = $data['keterangan'] ?? '';
        $transaksiBelanja->status = $data['status'] ?? 1;
        $transaksiBelanja->modifiedby = auth('api')->user()->id;

        if (!$transaksiBelanja->save()) {
            throw new \Exception("Error storing transaksi belanja.");
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($transaksiBelanja->getTable()),
            'postingdari' => strtoupper('ENTRY TRANSAKSI BELANJA'),
            'idtrans' => $transaksiBelanja->id,
            'nobuktitrans' => $transaksiBelanja->id,
            'aksi' => 'ENTRY',
            'datajson' => $transaksiBelanja->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        return $transaksiBelanja;
    }

    public function processUpdate(TransaksiBelanja $transaksiBelanja, array $data): TransaksiBelanja
    {
        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $transaksiBelanja->perkiraanid = $data['perkiraanid'];
        $transaksiBelanja->tglbukti = $tglbukti;
        $transaksiBelanja->karyawanid = $data['karyawanid'];
        $transaksiBelanja->pembelianid = $data['pembelianid'] ?? 0;
        $transaksiBelanja->nominal = $data['nominal'] ?? 0;
        $transaksiBelanja->keterangan = $data['keterangan'] ?? '';
        $transaksiBelanja->status = $data['status'] ?? 1;
        $transaksiBelanja->modifiedby = auth('api')->user()->id;

        if (!$transaksiBelanja->save()) {
            throw new \Exception("Error updating Transaksi Belanja.");
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($transaksiBelanja->getTable()),
            'postingdari' => strtoupper('EDIT TRANSAKSI BELANJA'),
            'idtrans' => $transaksiBelanja->id,
            'nobuktitrans' => $transaksiBelanja->id,
            'aksi' => 'EDIT',
            'datajson' => $transaksiBelanja->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        return $transaksiBelanja;
    }

    public function processDestroy($id): TransaksiBelanja
    {
        $transaksiBelanja = new TransaksiBelanja();

        $transaksiBelanja = $transaksiBelanja->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($transaksiBelanja->getTable()),
            'postingdari' => 'DELETE TRANSAKSI BELANJA HEADER',
            'idtrans' => $transaksiBelanja->id,
            'nobuktitrans' => $transaksiBelanja->id,
            'aksi' => 'DELETE',
            'datajson' => $transaksiBelanja->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $transaksiBelanja;
    }

    public function cekValidasiAksi($pembelianid)
    {
        $transaksibelanja = DB::table('transaksibelanja')
            ->from(
                DB::raw("transaksibelanja as a")
            )
            ->select(
                'a.pembelianid',
                'a.perkiraanid',
                'b.nama as perkiraannama'
            )
            ->leftJoin('perkiraan as b', 'a.perkiraanid', 'b.id')
            ->where('a.pembelianid', '=', $pembelianid)
            ->first();

        if ($pembelianid != 0) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Transaksi Belanja ' . $transaksibelanja->perkiraannama,
                'kodeerror' => 'TDT'
            ];
            goto selesai;
        }
        $data = [
            'kondisi' => false,
            'keterangan' => '',
        ];

        selesai:
        return $data;
    }

    public function findEditAll()
    {
        $tgldari =  date('Y-m-d', strtotime(request()->tgldariheader));
        $tglsampai =  date('Y-m-d', strtotime(request()->tglsampaiheader));

        $this->setRequestParameters();
        $query = DB::table($this->table . ' as transaksibelanja')
            ->select(
                "transaksibelanja.id",
                "transaksibelanja.perkiraanid",
                "perkiraan.nama as perkiraannama",
                "transaksibelanja.tglbukti",
                "transaksibelanja.karyawanid",
                "karyawan.nama as karyawannama",
                "transaksibelanja.pembelianid",
                "pembelianheader.nobukti as pembeliannobukti",
                "transaksibelanja.nominal",
                "transaksibelanja.keterangan",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "transaksibelanja.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'transaksibelanja.created_at',
                'transaksibelanja.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'transaksibelanja.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'transaksibelanja.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("karyawan"), 'transaksibelanja.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("perkiraan"), 'transaksibelanja.perkiraanid', 'perkiraan.id')
            ->leftJoin(DB::raw("pembelianheader"), 'transaksibelanja.pembelianid', 'pembelianheader.id');

        if (request()->karyawanid != '') {

            $query->where('karyawan.id', request()->karyawanid);
        }


        if (request()->tgldariheader != '' && request()->tglsampaiheader != '') {
            $query->where('transaksibelanja.tglbukti', '>=', $tgldari)->where('transaksibelanja.tglbukti', '<=', $tglsampai);
        }



        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);
        $this->filter($query);
        // $this->paginate($query);

        $data = $query->get();
        return $data;
    }

    public function processData($data)
    {
        $tglBuktis = [];
        $ids = [];
        $perkiraanIds = [];
        $perkiraanNamas = [];
        $pembelianIds = [];
        $pembelianNamas = [];
        $karyawanIds = [];
        $karyawanNamas = [];
        $nominals = [];
        $keterangans = [];
        foreach ($data as $detail) {
            $ids[] = $detail['id'];
            $tglBuktis[] = $detail['tglbukti'];
            $perkiraanIds[] = $detail['perkiraanid'];
            $perkiraanNamas[] = $detail['perkiraannama'];
            $pembelianIds[] = ($detail['pembelianid'] !== "") ? $detail['pembelianid'] : 0;
            // $pembelianNamas[] = $detail['pembeliannama'];
            $karyawanIds[] = $detail['karyawanid'];
            // $karyawanNamas[] = $detail['karyawannama'];
            $nominals[] = $detail['nominal'];
            $keterangans[] = $detail['keterangan'];
        }

        $data = [
            "id" =>  $ids,
            "tglbukti" =>  $tglBuktis,
            "perkiraanid" => $perkiraanIds,
            "perkiraannama" => $perkiraanNamas,
            "pembelianid" => $pembelianIds,
            // "pembeliannama" => $pembelianNamas,
            "karyawanid" => $karyawanIds,
            // "karyawannama" => $karyawanNamas,
            "nominal" => $nominals,
            "keterangan" => $keterangans,

        ];

        return $data;
    }

    public function processEditAllOld($data)
    {
        if (request()->deletedId) {
            for ($i = 0; $i < count(request()->deletedId); $i++) {
                $deletedID = TransaksiBelanja::where('id', request()->deletedId[$i])->first();

                if ($deletedID) {
                    TransaksiBelanja::where('id', $deletedID->id)->delete();
                }
            }
        }
        for ($i = 0; $i < count($data['perkiraanid']); $i++) {
            $transaksiBelanjaID = TransaksiBelanja::where('id', $data['id'][$i])->first();

            $transaksiBelanja = new TransaksiBelanja();
            if ($transaksiBelanjaID) {
                $transaksiBelanja = $transaksiBelanjaID;
            }
            $transaksiBelanja->tglbukti = date('Y-m-d', strtotime($data['tglbukti'][$i]));
            $transaksiBelanja->perkiraanid = $data['perkiraanid'][$i];
            $transaksiBelanja->karyawanid = $data['karyawanid'][$i];
            $transaksiBelanja->nominal = $data['nominal'][$i];
            $transaksiBelanja->keterangan = $data['keterangan'][$i];
            $transaksiBelanja->status = 1;
            $transaksiBelanja->modifiedby = auth('api')->user()->id;
            $transaksiBelanja->save();

            if (!$transaksiBelanja->save()) {
                throw new \Exception("Error storing transaksi belanja.");
            }

            (new LogTrail())->processStore([
                'namatabel' => 'transaksibelanja',
                'postingdari' => strtoupper('EDIT TRANSAKSI BELANJA'),
                'idtrans' => $transaksiBelanja->id,
                'nobuktitrans' => $transaksiBelanja->id,
                'aksi' => 'EDIT',
                'datajson' => $transaksiBelanja->toArray(),
                'modifiedby' => auth('api')->user()->user
            ]);
        }
        return $data;
    }

    public function processEditAll($data)
    {
        // dd($data);
        $tempHeader = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempHeader (
            id INT UNSIGNED NULL,
            perkiraanid INT,
            tglbukti DATE,
            karyawanid VARCHAR(100),
            pembelianid INT,
            nominal VARCHAR(500),
            keterangan VARCHAR(500),
            tglcetak DATE,
            status INT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        )");

        for ($i = 0; $i < count($data['tglbukti']); $i++) {
            DB::table($tempHeader)->insert([
                'id' => $data['id'][$i],
                'perkiraanid' =>  $data['perkiraanid'][$i],
                'tglbukti' => date('Y-m-d', strtotime($data['tglbukti'][$i])),
                'karyawanid' => $data['karyawanid'][$i],
                'pembelianid' => $data['pembelianid'][$i] ?? 0,
                'nominal' => $data['nominal'][$i] ?? 0,
                'keterangan' => $data['keterangan'][$i] ?? 0,
                'status' => 1,
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // dd(DB::table($tempHeader)->get());
        // update transaksi belanja
        $queryUpdate = DB::table('transaksibelanja as a')
            ->join("$tempHeader as c", 'a.id', '=', 'c.id')
            ->update([
                'a.id' => DB::raw('c.id'),
                'a.perkiraanid' => DB::raw('c.perkiraanid'),
                'a.tglbukti' => DB::raw('c.tglbukti'),
                'a.karyawanid' => DB::raw('c.karyawanid'),
                'a.pembelianid' => DB::raw('c.pembelianid'),
                'a.nominal' => DB::raw('c.nominal'),
                'a.keterangan' => DB::raw('c.keterangan'),
                'a.tglcetak' => DB::raw('c.tglcetak'),
                'a.status' => DB::raw('c.status'),
                'a.modifiedby' => DB::raw('c.modifiedby'),
                'a.created_at' => DB::raw('c.created_at'),
                'a.updated_at' => DB::raw('c.updated_at')
            ]);

        // delete transaksi belanja
        $queryDelete = DB::table('transaksibelanja as a')
            ->leftJoin("$tempHeader as b", 'a.id', '=', 'b.id')
            ->whereNull('b.id')
            ->where('a.karyawanid',request()->karyawanid)
            ->delete();

        // dd($queryDelete);

        //insert transaksi belanja
        $insertAddRowQuery =  DB::table("$tempHeader as a")
            ->where("a.id", '=', '0');


        DB::table('transaksibelanja')->insertUsing(["id", "perkiraanid", "tglbukti", "karyawanid", "pembelianid", "nominal", "keterangan", "tglcetak", "status", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

        // dd($queryDelete->get());

        return $data;
    }

    public function getTransaksiBelanja()
    {
        $tempPembelian = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempPembelian (
            id INT UNSIGNED NULL,
            supplierid INT,
            nobukti VARCHAR(100),
            karyawanid INT,
            tglterima DATE,
            tglbukti DATE,
            keterangan VARCHAR(500),
            subtotal VARCHAR(500),
            potongan VARCHAR(500),
            total VARCHAR(500),
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        )");

        $tempTransaksiBelanja = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempTransaksiBelanja (
            id INT UNSIGNED NULL,
            perkiraanid INT,
            perkiraannama VARCHAR(500),
            tglbukti DATE,
            karyawanid VARCHAR(100),
            karyawannama VARCHAR(100),
            pembelianid INT,
            pembeliannobukti VARCHAR(100),
            nominal VARCHAR(500),
            keterangan VARCHAR(500),
            tglcetak DATE,
            status INT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        )");


        $getPerkiraan = DB::table('perkiraan')
            ->select(
                'id',
                'nama'
            )->where('nama', 'PANJAR')->where('seqno', 2)->first();


        $getSeqno = DB::table('perkiraan')
            ->select(
                'perkiraan.id',
                DB::raw('IFNULL(transaksibelanja.nominal, 0) as nominal'),
                'perkiraan.seqno',
                'transaksibelanja.karyawanid as karyawanid'
            )
            ->leftJoin("transaksibelanja", 'perkiraan.id', '=', 'transaksibelanja.perkiraanid');
            
            if (request()->karyawanid != '') {
                $getSeqno->where('karyawanid', request()->karyawanid);
            }

        $dataPerkiraan = $getSeqno->get();


        $pembelianHeader = new PembelianHeader();

        $getPembelian = DB::table('pembelianheader')
            ->leftJoin(DB::raw("karyawan"), 'pembelianheader.karyawanid', 'karyawan.id')
            ->select(
                DB::raw('MAX(karyawan.id) as karyawanid'),
                DB::raw('MAX(karyawan.nama) as karyawannama'),
                DB::raw('MAX(pembelianheader.tglbukti) as tglbukti')
            )->groupBy('karyawan.id');

        

        if (request()->karyawanid != '') {
            $getPembelian->where('karyawan.id', request()->karyawanid);
        }

        $tglpengiriman = date('Y-m-d', strtotime(request()->tglpengirimanbeli));
        if (request()->tglpengirimanbeli != '') {
            $getPembelian->where('pembelianheader.tglbukti', $tglpengiriman);
        }

        $data = $getPembelian->get();

        // dd($data);

        foreach ($data as $row => $pembelian) {
            //    select belanja where karyawanid= $pembelian->karyawanid and tgl first
            $datamentah = DB::select(DB::raw("SELECT 
                0 as id,
                " . $getPerkiraan->id . " as perkiraanid,
                '" . $getPerkiraan->nama . "' as perkiraannama,
                " . $pembelian->karyawanid . " as karyawanid,
                '" . $pembelian->karyawannama . "' as karyawannama,
                0 as nominal,
                '' as keterangan
            "));

            $transaksiBelanja = DB::table('transaksibelanja')
                ->where('karyawanid', $pembelian->karyawanid)
                ->where('tglbukti', $pembelian->tglbukti)->first();


            if ($transaksiBelanja) {
                //  semua transaksi belanja where karyawan and tgl
                $transaksiBelanja = DB::table('transaksibelanja')
                    ->select(
                        "transaksibelanja.id",
                        "transaksibelanja.perkiraanid",
                        "perkiraan.nama as perkiraannama",
                        "transaksibelanja.tglbukti",
                        "transaksibelanja.karyawanid",
                        "karyawan.nama as karyawannama",
                        "transaksibelanja.pembelianid",
                        "pembelianheader.nobukti as pembeliannobukti",
                        "transaksibelanja.nominal",
                        "transaksibelanja.keterangan",
                        "transaksibelanja.tglcetak",
                        "parameter.id as status",
                        'modifier.id as modifiedby',
                        'transaksibelanja.created_at',
                        'transaksibelanja.updated_at'
                    )
                    ->leftJoin(DB::raw("parameter"), 'transaksibelanja.status', 'parameter.id')
                    ->leftJoin(DB::raw("perkiraan"), 'transaksibelanja.perkiraanid', 'perkiraan.id')
                    ->leftJoin(DB::raw("user as modifier"), 'transaksibelanja.modifiedby', 'modifier.id')
                    ->leftJoin(DB::raw("karyawan"), 'transaksibelanja.karyawanid', 'karyawan.id')
                    ->leftJoin(DB::raw("pembelianheader"), 'transaksibelanja.pembelianid', 'pembelianheader.id')
                    ->where('karyawan.id', $pembelian->karyawanid)
                    ->orderBy('perkiraan.seqno','DESC')
                    ->where('transaksibelanja.tglbukti', $pembelian->tglbukti);

                // dd($transaksiBelanja->first());


                DB::table($tempTransaksiBelanja)->insertUsing(["id", "perkiraanid","perkiraannama", "tglbukti", "karyawanid","karyawannama", "pembelianid","pembeliannobukti", "nominal", "keterangan", "tglcetak", "status", "modifiedby", "created_at", "updated_at"], $transaksiBelanja);
            } else {
                DB::table($tempTransaksiBelanja)->insert([
                    'id' => $datamentah[0]->id,
                    'perkiraanid' => $datamentah[0]->perkiraanid,
                    'perkiraannama' => $datamentah[0]->perkiraannama,
                    'karyawanid' => $datamentah[0]->karyawanid,
                    'karyawannama' => $datamentah[0]->karyawannama,
                    'nominal' => $datamentah[0]->nominal,
                    'keterangan' => $datamentah[0]->keterangan,
                ]);
            }
        }

        $getDataTransaki = DB::table($tempTransaksiBelanja)->get();

        $totalPanjar = 0;
        $totalBiaya = 0;
        $totalBiayaParkir = 0;
        $totalBiayaMakan = 0;
        $totalBiayaBensin = 0;
        foreach ($dataPerkiraan as $item) {
            if ($item->seqno === '2') {
                $totalPanjar += $item->nominal;
            } else {
                $totalBiaya += $item->nominal;
            }
        }

        $this->totalPanjar = $totalPanjar;
        $this->totalBiaya = $totalBiaya;
        // $this->totalBiayaParkir = $totalBiayaParkir;
        // $this->totalBiayaMakan = $totalBiayaMakan;
        // $this->totalBiayaBensin = $totalBiayaBensin;

        // $this->totalSisa = $this->totalPanjar - $this->totalBiaya;

        // dd($getDataTransaki);

        return $getDataTransaki;
    }
}
