<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransaksiArmada extends MyModel
{
    use HasFactory;

    protected $table = 'transaksiarmada';

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
        $query = DB::table($this->table . ' as transaksiarmada')
            ->select(
                "transaksiarmada.id",
                "transaksiarmada.perkiraanid",
                "perkiraan.nama as perkiraannama",
                "perkiraan.seqno as perkiraanseqno",
                "transaksiarmada.tglbukti",
                "transaksiarmada.karyawanid",
                "karyawan.nama as karyawannama",
                "transaksiarmada.armadaid",
                "armada.nama as armadanama",
                "transaksiarmada.nominal",
                "transaksiarmada.keterangan",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "transaksiarmada.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'transaksiarmada.created_at',
                'transaksiarmada.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'transaksiarmada.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'transaksiarmada.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("karyawan"), 'transaksiarmada.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("perkiraan"), 'transaksiarmada.perkiraanid', 'perkiraan.id')
            ->leftJoin(DB::raw("armada"), 'transaksiarmada.armadaid', 'armada.id');

        if (request()->tgldari && request()->tglsampai) {
            $query->whereBetween($this->table . '.tglbukti', [date('Y-m-d', strtotime(request()->tgldari)), date('Y-m-d', strtotime(request()->tglsampai))]);
        }

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);

        foreach ($query->get() as $item) {
            if ($item->perkiraanseqno === '1') {
                $this->totalPanjar += $item->nominal;
            } else {
                $this->totalBiaya += $item->nominal;
            }
        }

        $this->totalSisa = $this->totalPanjar - $this->totalBiaya;

        $data = $query->get();
        return $data;
    }

    public function findAll($id)
    {
        $query = DB::table('transaksiarmada')
            ->select(
                "transaksiarmada.id",
                "transaksiarmada.perkiraanid",
                "perkiraan.nama as perkiraannama",
                "transaksiarmada.tglbukti",
                "transaksiarmada.karyawanid",
                "karyawan.nama as karyawannama",
                "transaksiarmada.armadaid",
                "armada.nama as armadanama",
                "transaksiarmada.nominal",
                "transaksiarmada.keterangan",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "parameter.text as statusnama",
                "transaksiarmada.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'transaksiarmada.created_at',
                'transaksiarmada.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'transaksiarmada.status', 'parameter.id')
            ->leftJoin(DB::raw("perkiraan"), 'transaksiarmada.perkiraanid', 'perkiraan.id')
            ->leftJoin(DB::raw("user as modifier"), 'transaksiarmada.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("karyawan"), 'transaksiarmada.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("armada"), 'transaksiarmada.armadaid', 'armada.id')
            ->where('transaksiarmada.id', $id);

        $data = $query->first();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'tglbukti') {
            return $query->orderBy('transaksiarmada.tglbukti', $this->params['sortOrder'])
                ->orderBy('karyawan.nama', 'ASC')->orderBy('perkiraan.seqno', 'ASC');
        } else if ($this->params['sortIndex'] == 'perkiraannama') {
            return $query->orderBy('perkiraan.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'karyawannama') {
            return $query->orderBy('karyawan.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'armadanama') {
            return $query->orderBy('armada.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'modifiedby_name') {
            return $query->orderBy('modifier.name', $this->params['sortOrder']);
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
                        } else if ($filters['field'] == 'armadanama') {
                            $query = $query->where('armada.nama', 'like', "%$filters[data]%");
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
                        } else if ($filters['field'] == 'armadanama') {
                            $query = $query->where('armada.nama', 'like', "%$filters[data]%");
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
                $this->table.armadaid,
                armada.nama as armadanama,
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
            ->leftJoin(DB::raw("parameter"), 'transaksiarmada.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'transaksiarmada.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("karyawan"), 'transaksiarmada.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("perkiraan"), 'transaksiarmada.perkiraanid', 'perkiraan.id')
            ->leftJoin(DB::raw("armada"), 'transaksiarmada.armadaid', 'armada.id');
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
            armadaid INT,
            armadanama VARCHAR(100),
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
            "id", "perkiraanid", "perkiraannama", "tglbukti", "karyawanid", "karyawannama", "armadaid", "armadanama", "nominal", "keterangan", "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
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

    public function processStore(array $data): TransaksiArmada
    {
        $transaksiArmada = new TransaksiArmada();

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $transaksiArmada->perkiraanid = $data['perkiraanid'];
        $transaksiArmada->tglbukti = $tglbukti;
        $transaksiArmada->karyawanid = $data['karyawanid'];
        $transaksiArmada->armadaid = $data['armadaid'] ?? 0;
        $transaksiArmada->nominal = $data['nominal'] ?? 0;
        $transaksiArmada->keterangan = $data['keterangan'] ?? '';
        $transaksiArmada->status = $data['status'] ?? 1;
        $transaksiArmada->modifiedby = auth('api')->user()->id;

        if (!$transaksiArmada->save()) {
            throw new \Exception("Error storing transaksi armada.");
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($transaksiArmada->getTable()),
            'postingdari' => strtoupper('ENTRY TRANSAKSI ARMADA'),
            'idtrans' => $transaksiArmada->id,
            'nobuktitrans' => $transaksiArmada->id,
            'aksi' => 'ENTRY',
            'datajson' => $transaksiArmada->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);


        return $transaksiArmada;
    }

    public function processUpdate(TransaksiArmada $transaksiArmada, array $data): TransaksiArmada
    {
        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $transaksiArmada->perkiraanid = $data['perkiraanid'];
        $transaksiArmada->tglbukti = $tglbukti;
        $transaksiArmada->karyawanid = $data['karyawanid'];
        $transaksiArmada->armadaid = $data['armadaid'] ?? 0;
        $transaksiArmada->nominal = $data['nominal'] ?? 0;
        $transaksiArmada->keterangan = $data['keterangan'] ?? '';
        $transaksiArmada->status = $data['status'] ?? 1;
        $transaksiArmada->modifiedby = auth('api')->user()->id;

        if (!$transaksiArmada->save()) {
            throw new \Exception("Error updating Transaksi Armada.");
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($transaksiArmada->getTable()),
            'postingdari' => strtoupper('EDIT TRANSAKSI ARMADA'),
            'idtrans' => $transaksiArmada->id,
            'nobuktitrans' => $transaksiArmada->id,
            'aksi' => 'EDIT',
            'datajson' => $transaksiArmada->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        return $transaksiArmada;
    }

    public function processDestroy($id): TransaksiArmada
    {
        $transaksiArmada = new TransaksiArmada();

        $transaksiArmada = $transaksiArmada->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($transaksiArmada->getTable()),
            'postingdari' => 'DELETE TRANSAKSI ARMADA HEADER',
            'idtrans' => $transaksiArmada->id,
            'nobuktitrans' => $transaksiArmada->id,
            'aksi' => 'DELETE',
            'datajson' => $transaksiArmada->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $transaksiArmada;
    }

    public function findEditAll()
    {
        $tgldari =  date('Y-m-d', strtotime(request()->tgldariheader));
        $tglsampai =  date('Y-m-d', strtotime(request()->tglsampaiheader));

        $this->setRequestParameters();
        $query = DB::table($this->table . ' as transaksiarmada')
            ->select(
                "transaksiarmada.id",
                "transaksiarmada.perkiraanid",
                "perkiraan.nama as perkiraannama",
                "transaksiarmada.tglbukti",
                "transaksiarmada.karyawanid",
                "karyawan.nama as karyawannama",
                "transaksiarmada.armadaid",
                "armada.nama as armadanama",
                "transaksiarmada.nominal",
                "transaksiarmada.keterangan",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "transaksiarmada.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'transaksiarmada.created_at',
                'transaksiarmada.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'transaksiarmada.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'transaksiarmada.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("karyawan"), 'transaksiarmada.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("perkiraan"), 'transaksiarmada.perkiraanid', 'perkiraan.id')
            ->leftJoin(DB::raw("armada"), 'transaksiarmada.armadaid', 'armada.id')
            ->where('transaksiarmada.tglbukti', '>=', $tgldari)
            ->where('transaksiarmada.tglbukti', '<=', $tglsampai);

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
        $karyawanIds = [];
        $karyawanNamas = [];
        $armadaIds = [];
        $nominals = [];
        $keterangans = [];
        foreach ($data as $detail) {
            $ids[] = $detail['id'];
            $tglBuktis[] = $detail['tglbukti'];
            $perkiraanIds[] = $detail['perkiraanid'];
            $perkiraanNamas[] = $detail['perkiraannama'];
            $karyawanIds[] = $detail['karyawanid'];
            $karyawanNamas[] = $detail['karyawannama'];
            $armadaIds[] = $detail['armadaid'];
            $nominals[] = $detail['nominal'];
            $keterangans[] = $detail['keterangan'];
        }

        $data = [
            "id" =>  $ids,
            "tglbukti" =>  $tglBuktis,
            "perkiraanid" => $perkiraanIds,
            "perkiraannama" => $perkiraanNamas,
            "karyawanid" => $karyawanIds,
            "karyawannama" => $karyawanNamas,
            "armadaid" => $armadaIds,
            "nominal" => $nominals,
            "keterangan" => $keterangans,

        ];

        return $data;
    }

    public function processEditAll($data)
    {
        if (request()->deletedId) {
            for ($i = 0; $i < count(request()->deletedId); $i++) {
                $deletedID = TransaksiArmada::where('id', request()->deletedId[$i])->first();

                if ($deletedID) {
                    TransaksiArmada::where('id', $deletedID->id)->delete();
                }
            }
        }
        for ($i = 0; $i < count($data['perkiraanid']); $i++) {
            $transaksiArmadaID = TransaksiArmada::where('id', $data['id'][$i])->first();

            $transaksiArmada = new TransaksiArmada();
            if ($transaksiArmadaID) {
                $transaksiArmada = $transaksiArmadaID;
            }
            $transaksiArmada->tglbukti = date('Y-m-d', strtotime($data['tglbukti'][$i]));
            $transaksiArmada->perkiraanid = $data['perkiraanid'][$i];
            $transaksiArmada->karyawanid = $data['karyawanid'][$i];
            $transaksiArmada->armadaid = $data['armadaid'][$i];
            $transaksiArmada->nominal = $data['nominal'][$i];
            $transaksiArmada->keterangan = $data['keterangan'][$i];
            $transaksiArmada->status = 1;
            $transaksiArmada->modifiedby = auth('api')->user()->id;
            $transaksiArmada->save();

            if (!$transaksiArmada->save()) {
                throw new \Exception("Error storing transaksi belanja.");
            }

            (new LogTrail())->processStore([
                'namatabel' => 'transaksiArmada',
                'postingdari' => strtoupper('EDIT TRANSAKSI BELANJA'),
                'idtrans' => $transaksiArmada->id,
                'nobuktitrans' => $transaksiArmada->id,
                'aksi' => 'EDIT',
                'datajson' => $transaksiArmada->toArray(),
                'modifiedby' => auth('api')->user()->user
            ]);
        }
        return $data;
    }
}
