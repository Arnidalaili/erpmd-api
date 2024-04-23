<?php

namespace App\Models;

use App\Services\RunningNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Piutang extends MyModel
{
    use HasFactory;

    protected $table = 'piutang';

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
        $query = DB::table($this->table . ' as piutang')
            ->select(
                "piutang.id",
                "piutang.tglbukti",
                "piutang.nobukti",
                "piutang.tglbuktipenjualan",
                "piutang.tgljatuhtempo",
                "piutang.keterangan",
                "piutang.nominalpiutang",
                "piutang.nominalbayar",
                "piutang.nominalsisa",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "piutang.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'piutang.created_at',
                'piutang.updated_at',
                'piutang.flag'
            )
            ->leftJoin(DB::raw("parameter"), 'piutang.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'piutang.modifiedby', 'modifier.id')
            ->leftJoin('supplier as s', function ($join) {
                $join->on('piutang.flag', '=', DB::raw("'RB'"))
                    ->on('s.id', '=', 'piutang.customerid');
            })
            ->leftJoin('customer as c', function ($join) {
                $join->on('piutang.flag', '<>', DB::raw("'RB'"))
                    ->on('c.id', '=', 'piutang.customerid');
            })
            ->leftJoin('returbeliheader as rb', function ($join) {
                $join->on('piutang.flag', '=', DB::raw("'RB'"))
                    ->on('rb.id', '=', 'piutang.penjualanid');
            })
            ->leftJoin('penjualanheader as pj', function ($join) {
                $join->on('piutang.flag', '<>', DB::raw("'RB'"))
                    ->on('pj.id', '=', 'piutang.penjualanid');
            })
            ->selectRaw("CASE 
                    WHEN piutang.flag = 'RB' THEN s.id 
                    ELSE c.id 
                END AS customerid")
            ->selectRaw("CASE 
                    WHEN piutang.flag = 'RB' THEN s.nama 
                    ELSE c.nama 
                END AS customernama")
            ->selectRaw("CASE 
                    WHEN piutang.flag = 'RB' THEN rb.id
                    ELSE pj.id
                END AS penjualanid")
            ->selectRaw("CASE 
                    WHEN piutang.flag = 'RB' THEN rb.nobukti
                    ELSE pj.nobukti
                END AS penjualannobukti");

        // dd($query->get());

        if (request()->tgldari && request()->tglsampai) {
            $query->whereBetween($this->table . '.tglbukti', [date('Y-m-d', strtotime(request()->tgldari)), date('Y-m-d', strtotime(request()->tglsampai))]);
        }

        if (request()->jenis == 'LUNAS') {
            $query->where('piutang.nominalsisa', '=', "0");
        } else if (request()->jenis == 'BELUM LUNAS') {
            $query->where('piutang.nominalsisa', '>', "0");
        }

        if (request()->customer) {
            $query->where('customer.id', '=', request()->customer);
        }

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);

        $data = $query->get();
        return $data;
    }

    public function findAll($id)
    {
        $query = DB::table('piutang')
            ->select(
                "piutang.id",
                "piutang.tglbukti",
                "piutang.nobukti",
                "penjualanheader.id as penjualanid",
                "penjualanheader.nobukti as penjualannobukti",
                "piutang.tglbuktipenjualan",
                "piutang.tgljatuhtempo",
                "customer.id as customerid",
                "customer.nama as customernama",
                "piutang.keterangan",
                "piutang.nominalpiutang",
                "piutang.nominalbayar",
                "piutang.nominalsisa",
                "parameter.id as status",
                "parameter.text as statusnama",
                "parameter.memo as statusmemo",
                "piutang.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'piutang.created_at',
                'piutang.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'piutang.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'piutang.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("customer"), 'piutang.customerid', 'customer.id')
            ->leftJoin(DB::raw("penjualanheader"), 'piutang.penjualanid', 'penjualanheader.id')
            ->where('piutang.id', $id);
        // dd($query->get());

        $data = $query->first();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('penjualanheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(piutang.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(piutang.nobukti, '/', 1)"), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'customernama') {
            return $query->orderBy('customer.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'penjualannobukti') {
            return $query->orderBy('penjualanheader.nobukti', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'penjualannobukti') {
                            $query = $query->where('penjualanheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'customernama') {
                            $query = $query->where('customer.nama', 'like', "%$filters[data]%");
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
                        if ($filters['field'] == 'penjualannobukti') {
                            $query = $query->where('penjualanheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'customernama') {
                            $query = $query->where('customer.nama', 'like', "%$filters[data]%");
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
                $this->table.penjualanid,
                penjualanheader.nobukti as penjualannobukti,
                $this->table.tglbuktipenjualan,
                $this->table.tgljatuhtempo,
                $this->table.customerid,
                customer.nama as customernama,
                $this->table.keterangan,
                $this->table.nominalpiutang,
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
            ->leftJoin(DB::raw("parameter"), 'piutang.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'piutang.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("customer"), 'piutang.customerid', 'customer.id')
            ->leftJoin(DB::raw("penjualanheader"), 'piutang.penjualanid', 'penjualanheader.id');
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
            penjualanid INT,
            penjualannobukti VARCHAR(20),
            tglbuktipenjualan DATETIME,
            tgljatuhtempo DATETIME,
            customerid INT,
            customernama VARCHAR(100),
            keterangan VARCHAR(500),
            nominalpiutang FLOAT,
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
            "id", "tglbukti", "nobukti",  "penjualanid", "penjualannobukti", "tglbuktipenjualan",
            "tgljatuhtempo", "customerid", "customernama", "keterangan", "nominalpiutang", "nominalbayar", "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
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
            jenisid INT NULL,
            jenisnama VARCHAR(100)
        )");

        $status = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        $jenis = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'JENIS HUTANG PIUTANG')
            ->where('subgrp', '=', 'JENIS HUTANG PIUTANG')
            ->first();


        DB::statement("INSERT INTO $tempdefault (status,statusnama,jenisid,jenisnama) VALUES (?,?,?,?)", [$status->id, $status->text, $jenis->id, $jenis->text]);

        $query = DB::table(
            $tempdefault
        )
            ->select(
                'status',
                'statusnama',
                'jenisid',
                'jenisnama'
            );

        $data = $query->first();
        return $data;
    }

    public function processStore(array $data): Piutang
    {
        // dd($data);
        $piutang = new Piutang();
        /*STORE*/
        $group = 'PIUTANG BUKTI';
        $subGroup = 'PIUTANG BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));
        $tglbuktipenjualan = date('Y-m-d', strtotime($data['tglbuktipenjualan']));
        $tgljatuhtempo = date('Y-m-d', strtotime($data['tgljatuhtempo']));

        $piutang->tglbukti = $tglbukti;
        $piutang->penjualanid = $data['penjualanid'];

        if ($tglbuktipenjualan && $tglbuktipenjualan != "1970-01-01") {
            $piutang->tglbuktipenjualan = $tglbuktipenjualan;
        } else {
            $piutang->tglbuktipenjualan = null;
        }

        $piutang->tgljatuhtempo = $tgljatuhtempo;
        $piutang->customerid = $data['customerid'];
        $piutang->keterangan = $data['keterangan'];
        $piutang->nominalpiutang = $data['nominalpiutang'];
        $piutang->nominalbayar = $data['nominalbayar'] ?? 0;
        $piutang->nominalsisa = $data['nominalsisa'] ?? 0;
        // $piutang->tglcetak = $data['tglcetak'] ?? '';
        $piutang->status = $data['status'];
        $piutang->flag = $data['flag'] ?? 'J';
        $piutang->modifiedby = auth('api')->user()->id;

        // dd($piutang);

        $piutang->nobukti = (new RunningNumberService)->get($group, $subGroup, $piutang->getTable(), date('Y-m-d', strtotime($data['tglbukti'])));

        if (!$piutang->save()) {
            throw new \Exception("Error storing piutang header.");
        }

        $piutangLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($piutang->getTable()),
            'postingdari' => strtoupper('ENTRY PIUTANG'),
            'idtrans' => $piutang->id,
            'nobuktitrans' => $piutang->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $piutang->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        // dd($piutang);

        return $piutang;
    }

    public function processUpdate(Piutang $piutang, array $data): Piutang
    {
        /*UPDATE*/
        $group = 'PIUTANG BUKTI';
        $subGroup = 'PIUTANG BUKTI';

        $piutang->customerid = $data['customerid'];
        $piutang->keterangan = $data['keterangan'] ?? '';
        $piutang->nominalpiutang = $data['nominalpiutang'];
        $piutang->nominalbayar = $data['nominalbayar'] ?? 0;
        $piutang->nominalsisa = $data['nominalsisa'] ?? 0;
        $piutang->status = $data['status'];
        $piutang->modifiedby = auth('api')->user()->id;

        if (!$piutang->save()) {
            throw new \Exception('Error updating piutang');
        }

        (new LogTrail())->processStore([
            'namatabel' => $piutang->getTable(),
            'postingdari' => 'EDIT piutang',
            'idtrans' => $piutang->id,
            'nobuktitrans' => $piutang->id,
            'aksi' => 'EDIT',
            'datajson' => $piutang->toArray(),
            'modifiedby' => $piutang->modifiedby
        ]);

        return $piutang;
    }

    public function processDestroy($id): Piutang
    {
        $piutang = new Piutang();
        $piutang = $piutang->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($piutang->getTable()),
            'postingdari' => 'DELETE PIUTANG',
            'idtrans' => $piutang->id,
            'nobuktitrans' => $piutang->id,
            'aksi' => 'DELETE',
            'datajson' => $piutang->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $piutang;
    }

    public function cekValidasiAksi($penjualanid)
    {
        $penjualanHeader = DB::table('penjualanheader')
            ->from(
                DB::raw("penjualanheader as a")
            )
            ->select(
                'a.id',
                'a.nobukti'
            )
            ->where('a.id', '=', $penjualanid)
            ->first();


        if (isset($penjualanHeader)) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Penjualan ' . $penjualanHeader->nobukti,
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

    public function createTempPiutang($customerid)
    {
        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            nobukti VARCHAR(100),
            nominalbayar FLOAT,
            nominalsisa FLOAT
        )");

        $fetch = DB::table('piutang')
            ->select(
                'piutang.id',
                'piutang.nobukti',
                DB::raw('SUM(pelunasanpiutangdetail.nominalbayar) as nominalbayar'),
                DB::raw('(piutang.nominal - COALESCE(SUM(pelunasanpiutangdetail.nominalbayar), 0) - COALESCE(SUM(pelunasanpiutangdetail.nominalpotongan), 0)) as nominalsisa')
            )
            ->leftJoin(DB::raw("pelunasanpiutangdetail"), 'pelunasanpiutangdetail.piutangid', 'piutang.id')
            ->where("piutang.customerid", "=", $customerid)
            ->groupBy('piutang.id', 'piutang.nobukti', 'piutang.nominal');

        DB::table($temp)->insertUsing([
            "id", "nobukti", "nominalbayar", "nominalsisa",
        ], $fetch);

        return $temp;
    }

    public function getPiutang($customerid)
    {
        $this->setRequestParameters();

        $temp = $this->createTempPiutang($customerid);

        $query = DB::table('piutang')->from(DB::raw("piutang"))
            ->select(DB::raw("piutang.id as id, piutang.nobukti as nobuktipiutang, piutang.tglbukti as tglbuktipiutang, piutang.nominal as nominalpiutang, $temp.nominalsisa as nominalsisa"))
            ->leftJoin(DB::raw("$temp"), 'piutang.id', '=', "$temp.id")
            ->whereRaw("piutang.nobukti = $temp.nobukti")
            ->where(function ($query) use ($temp) {
                $query->whereRaw("$temp.nominalsisa != 0")
                    ->orWhereRaw("$temp.nominalsisa is null");
            });

        $data = $query->get();

        return $data;
    }
}
