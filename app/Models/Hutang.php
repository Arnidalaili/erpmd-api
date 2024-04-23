<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\RunningNumberService;
use Illuminate\Support\Facades\Schema;

class Hutang extends MyModel
{
    use HasFactory;

    protected $table = 'hutang';

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
        $query = DB::table($this->table . ' as hutang')
            ->select(
                "hutang.id",
                "hutang.tglbukti",
                "hutang.nobukti",
                "hutang.tglbuktipembelian",
                "hutang.tgljatuhtempo",
                "hutang.keterangan",
                "hutang.nominalhutang",
                "hutang.nominalbayar",
                "hutang.nominalsisa",
                "hutang.flag",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "hutang.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'hutang.created_at',
                'hutang.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'hutang.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'hutang.modifiedby', 'modifier.id')
            ->leftJoin('owner as o', function ($join) {
                $join->on('hutang.flag', '=', DB::raw("'OWNER'"))
                    ->on('o.id', '=', 'hutang.supplierid');
            })
            ->leftJoin('customer as c', function ($join) {
                $join->on('hutang.flag', '=', DB::raw("'RJ'"))
                    ->on('c.id', '=', 'hutang.supplierid');
            })
            ->leftJoin('supplier as s', function ($join) {
                $join->on('hutang.flag', '<>', DB::raw("'OWNER'"))
                    ->on('s.id', '=', 'hutang.supplierid');
            })
            ->leftJoin('returjualheader as rj', function ($join) {
                $join->on('hutang.flag', '=', DB::raw("'RJ'"))
                    ->on('rj.id', '=', 'hutang.pembelianid');
            })
            ->leftJoin('pembelianheader as pb', function ($join) {
                $join->on('hutang.flag', '<>', DB::raw("'RJ'"))
                    ->on('pb.id', '=', 'hutang.pembelianid');
            })
            ->selectRaw("CASE 
                    WHEN hutang.flag = 'OWNER' THEN o.id 
                    WHEN hutang.flag = 'RJ' THEN c.id 
                    ELSE s.id 
                END AS supplierid")
            ->selectRaw("CASE 
                    WHEN hutang.flag = 'OWNER' THEN o.nama 
                    WHEN hutang.flag = 'RJ' THEN c.nama 
                    ELSE s.nama 
                END AS suppliernama")
            ->selectRaw("CASE 
                    WHEN hutang.flag = 'RJ' THEN rj.id
                    ELSE pb.id
                END AS pembelianid")
            ->selectRaw("CASE 
                    WHEN hutang.flag = 'RJ' THEN rj.nobukti
                    ELSE pb.nobukti
                END AS pembeliannobukti");


        if (request()->tgldari && request()->tglsampai) {
            $query->whereBetween($this->table . '.tglbukti', [date('Y-m-d', strtotime(request()->tgldari)), date('Y-m-d', strtotime(request()->tglsampai))]);
        }

        if (request()->jenis == 'LUNAS') {
            $query->where('hutang.nominalsisa', '=', "0");
        } else if (request()->jenis == 'BELUM LUNAS') {
            $query->where('hutang.nominalsisa', '>', "0");
        }

        if (request()->supplier) {
            $query->where('hutang.supplierid', '=', request()->supplier);
        } elseif (request()->flag == 'OWNER') {
            $query->where('hutang.supplierid', '=', request()->supplier)
                ->where('hutang.flag', '=', 'OWNER');

            // dd($query->toSql());
        }


        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);

        $data = $query->get();
        // dd($data);
        return $data;
    }

    public function findAll($id)
    {
        $query = DB::table('hutang')
            ->select(
                "hutang.id",
                "hutang.tglbukti",
                "hutang.nobukti",
                "hutang.pembelianid",
                "pembelianheader.nobukti as pembeliannobukti",
                "hutang.tglbuktipembelian",
                "hutang.tgljatuhtempo",
                "hutang.supplierid",
                "supplier.nama as suppliernama",
                "hutang.keterangan",
                "hutang.nominalhutang",
                "hutang.nominalbayar",
                "hutang.nominalsisa",
                "hutang.flag",
                "parameter.id as status",
                "parameter.text as statusnama",
                "hutang.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'hutang.created_at',
                'hutang.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'hutang.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'hutang.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("supplier"), 'hutang.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("pembelianheader"), 'hutang.pembelianid', 'pembelianheader.id')
            ->leftJoin('owner as o', function ($join) {
                $join->on('hutang.flag', '=', DB::raw("'OWNER'"))
                    ->on('o.id', '=', 'hutang.supplierid');
            })
            ->leftJoin('supplier as s', function ($join) {
                $join->on('hutang.flag', '<>', DB::raw("'OWNER'"))
                    ->on('s.id', '=', 'hutang.supplierid');
            })
            ->selectRaw("CASE 
                WHEN hutang.flag = 'OWNER' THEN o.id 
                ELSE s.id 
            END AS supplierid")
            ->selectRaw("CASE 
                WHEN hutang.flag = 'OWNER' THEN o.nama 
                ELSE s.nama 
            END AS suppliernama")
            ->where('hutang.id', $id);

        $data = $query->first();
        // dd($data);
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pembelianheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(hutang.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(hutang.nobukti, '/', 1)"), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'suppliernama') {
            return $query->orderBy('s.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'pembeliannobukti') {
            return $query->orderBy('pembelianheader.nobukti', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'pembeliannobukti') {
                            $query = $query->where('pembelianheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'suppliernama') {
                            $query = $query->where('s.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'pembeliannobukti') {
                            $query = $query->orWhere('pembelianheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'suppliernama') {
                            $query = $query->orWhere('s.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->orWhere('parameter.memo', 'LIKE', "%$filters[data]%");
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
                $this->table.tglbukti,
                $this->table.nobukti,
                $this->table.pembelianid,
                pembelianheader.nobukti as pembeliannobukti,
                $this->table.tglbuktipembelian,
                $this->table.tgljatuhtempo,
                $this->table.supplierid,
                supplier.nama as suppliernama,
                $this->table.keterangan,
                $this->table.nominalhutang,
                $this->table.nominalbayar,
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
            ->leftJoin(DB::raw("parameter"), 'hutang.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'hutang.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("supplier"), 'hutang.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("pembelianheader"), 'hutang.pembelianid', 'pembelianheader.id');
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
            pembelianid INT,
            pembeliannobukti VARCHAR(20),
            tglbuktipembelian DATETIME,
            tgljatuhtempo DATETIME,
            supplierid INT,
            suppliernama VARCHAR(100),
            keterangan VARCHAR(500),
            nominalhutang FLOAT,
            nominalbayar FLOAT,
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
            "id", "tglbukti", "nobukti",  "pembelianid", "pembeliannobukti", "tglbuktipembelian",
            "tgljatuhtempo", "supplierid", "suppliernama", "keterangan", "nominalhutang", "nominalbayar", "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
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

    public function processStore(array $data): Hutang
    {
        $hutang = new Hutang();

        /*STORE*/
        $group = 'HUTANG BUKTI';
        $subGroup = 'HUTANG BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));
        $tglbuktipembelian = date('Y-m-d', strtotime($data['tglbuktipembelian']));
        $tgljatuhtempo = date('Y-m-d', strtotime($data['tgljatuhtempo']));

        $hutang->tglbukti = $tglbukti;
        $hutang->pembelianid = $data['pembelianid'];

        if ($tglbuktipembelian && $tglbuktipembelian != "1970-01-01") {
            $hutang->tglbuktipembelian = $tglbuktipembelian;
        } else {
            $hutang->tglbuktipembelian = null;
        }

        $hutang->tgljatuhtempo = $tgljatuhtempo;
        $hutang->supplierid = $data['supplierid'];
        $hutang->keterangan = $data['keterangan'];
        $hutang->nominalhutang = $data['nominalhutang'];
        $hutang->nominalbayar = $data['nominalbayar'] ?? 0;
        $hutang->nominalsisa = $data['nominalsisa'] ?? 0;
        $hutang->flag = $data['flag'] ?? 'B';
        // $hutang->tglcetak = $data['tglcetak'] ?? '';
        $hutang->status = $data['status'];
        $hutang->modifiedby = auth('api')->user()->id;

        // dd($hutang);

        $hutang->nobukti = (new RunningNumberService)->get($group, $subGroup, $hutang->getTable(), date('Y-m-d', strtotime($data['tglbukti'])));

        if (!$hutang->save()) {
            throw new \Exception("Error storing hutang header.");
        }

        $hutangLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($hutang->getTable()),
            'postingdari' => strtoupper('ENTRY HUTANG'),
            'idtrans' => $hutang->id,
            'nobuktitrans' => $hutang->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $hutang->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);
        // dd($hutang);

        return $hutang;
    }

    public function processUpdate(Hutang $hutang, array $data): Hutang
    {

        /*UPDATE*/
        $group = 'HUTANG BUKTI';
        $subGroup = 'HUTANG BUKTI';

        $hutang->pembelianid = $data['pembelianid'] ?? 0;
        $hutang->supplierid = $data['supplierid'];
        $hutang->keterangan = $data['keterangan'] ?? '';
        $hutang->nominalhutang = $data['nominalhutang'];
        $hutang->nominalbayar = $data['nominalbayar'] ?? 0;
        $hutang->nominalsisa = $data['nominalsisa'] ?? 0;
        $hutang->status = $data['status'];
        $hutang->flag = $data['flag'] ?? 'SUPPLIER';
        $hutang->modifiedby = auth('api')->user()->id;

        if (!$hutang->save()) {
            throw new \Exception('Error updating Hutang');
        }

        (new LogTrail())->processStore([
            'namatabel' => $hutang->getTable(),
            'postingdari' => 'EDIT HUTANG',
            'idtrans' => $hutang->id,
            'nobuktitrans' => $hutang->id,
            'aksi' => 'EDIT',
            'datajson' => $hutang->toArray(),
            'modifiedby' => $hutang->modifiedby
        ]);

        $trbelanja = DB::table('transaksibelanja')
            ->select('pembelianid', 'id', 'keterangan', 'perkiraanid')
            ->where('pembelianid', $data['pembelianid'])
            ->get();

        if (!$trbelanja->isEmpty()) {
            if ($data['nominalbayar'] == 0 || $data['nominalbayar'] == null) {
                DB::table('transaksibelanja')
                    ->select('pembelianid', 'nominal')
                    ->where('transaksibelanja.pembelianid', $data['pembelianid'])
                    ->delete();
            } else {
                DB::table('transaksibelanja')
                    ->select('pembelianid', 'nominal')
                    ->where('transaksibelanja.pembelianid', $data['pembelianid'])
                    ->update(['transaksibelanja.nominal' => $data['nominalbayar']]);
            }
        }

        // dd($hutang);
        return $hutang;
    }

    public function processDestroy($id): Hutang
    {
        $hutang = new Hutang();
        $hutang = $hutang->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($hutang->getTable()),
            'postingdari' => 'DELETE HUTANG',
            'idtrans' => $hutang->id,
            'nobuktitrans' => $hutang->id,
            'aksi' => 'DELETE',
            'datajson' => $hutang->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $hutang;
    }

    public function cekValidasiAksi($pembelianid)
    {
        $pembelianheader = DB::table('pembelianheader')
            ->from(
                DB::raw("pembelianheader as a")
            )
            ->select(
                'a.id',
                'a.nobukti'
            )
            ->where('a.id', '=', $pembelianid)
            ->first();

        if (isset($pembelianheader)) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Pembelian ' . $pembelianheader->nobukti,
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

    public function createTempHutang($supplierid)
    {
        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            nobukti VARCHAR(100),
            nominalbayar FLOAT,
            nominalsisa FLOAT
        )");

        $fetch = DB::table('hutang')
            ->select(
                'hutang.id',
                'hutang.nobukti',
                DB::raw('SUM(pelunasanhutangdetail.nominalbayar) as nominalbayar'),
                DB::raw('(hutang.nominal - COALESCE(SUM(pelunasanhutangdetail.nominalbayar), 0) - COALESCE(SUM(pelunasanhutangdetail.nominalpotongan), 0)) as nominalsisa')
            )
            ->leftJoin(DB::raw("pelunasanhutangdetail"), 'pelunasanhutangdetail.hutangid', 'hutang.id')
            ->where("hutang.supplierid", "=", $supplierid)
            ->groupBy('hutang.id', 'hutang.nobukti', 'hutang.nominalhutang');

        DB::table($temp)->insertUsing([
            "id", "nobukti", "nominalbayar", "nominalsisa",
        ], $fetch);

        return $temp;
    }

    public function getHutang($supplierid)
    {
        $this->setRequestParameters();

        $temp = $this->createTempHutang($supplierid);

        $query = DB::table('hutang')->from(DB::raw("hutang"))
            ->select(DB::raw("hutang.id as id, hutang.nobukti as nobuktihutang, hutang.tglbukti as tglbuktihutang, hutang.nominalhutang as nominalhutang, $temp.nominalsisa as nominalsisa"))
            ->leftJoin(DB::raw("$temp"), 'hutang.id', '=', "$temp.id")
            ->whereRaw("hutang.nobukti = $temp.nobukti")
            ->where(function ($query) use ($temp) {
                $query->whereRaw("$temp.nominalsisa != 0")
                    ->orWhereRaw("$temp.nominalsisa is null");
            });

        $data = $query->get();

        return $data;
    }
}
