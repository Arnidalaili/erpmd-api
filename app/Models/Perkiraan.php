<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Perkiraan extends MyModel
{
    use HasFactory;

    protected $table = 'perkiraan';

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

        $aktif = request()->aktif ?? '';
        $group = request()->group ?? '';
        // dd($group);

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'perkiraan.id',
                'perkiraan.nama',
                'operator.id as operator',
                'operator.memo as operatormemo',
                'groupperkiraan.id as groupperkiraan',
                'groupperkiraan.memo as groupperkiraanmemo',
                'perkiraan.keterangan',
                'perkiraan.seqno',
                'statusperkiraan.id as statusperkiraan',
                'statusperkiraan.memo as statusperkiraanmemo',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'perkiraan.modifiedby',
                'perkiraan.created_at',
                'perkiraan.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'perkiraan.status', '=', 'parameter.id')
            ->leftJoin('parameter as groupperkiraan', 'perkiraan.groupperkiraan', '=', 'groupperkiraan.id')
            ->leftJoin('parameter as statusperkiraan', 'perkiraan.statusperkiraan', '=', 'statusperkiraan.id')
            ->leftJoin('parameter as operator', 'perkiraan.operator', '=', 'operator.id');

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('perkiraan.status', '=', $status->id);
        }

        if ($group == 'belanja') {
            $groupperkiraan = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'GROUP PERKIRAAN')
                ->where('text', '=', 'BELANJA')
                ->first();
            $query->where('perkiraan.groupperkiraan', '=', $groupperkiraan->id);
        } else if ($group == 'armada') {
            $groupperkiraan = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'GROUP PERKIRAAN')
                ->where('text', '=', 'ARMADA')
                ->first();
            $query->where('perkiraan.groupperkiraan', '=', $groupperkiraan->id);
        }

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);

        if (request()->limit > 0) {
            $this->paginate($query);
        }

        $data = $query->get();
        // dd($data);

        return $data;
    }

    public function findAll($id)
    {
        $query = DB::table('perkiraan')
            ->select(
                'perkiraan.id',
                'perkiraan.seqno',
                'perkiraan.nama',
                'operator.id as operator',
                'operator.text as operatornama',
                'groupperkiraan.id as groupperkiraan',
                'groupperkiraan.text as groupperkiraannama',
                'perkiraan.keterangan',
                'statusperkiraan.id as statusperkiraan',
                'statusperkiraan.text as statusperkiraannama',
                'parameter.id as status',
                'parameter.text as statusnama',
                'perkiraan.modifiedby',
                'perkiraan.created_at',
                'perkiraan.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'perkiraan.status', '=', 'parameter.id')
            ->leftJoin('parameter as groupperkiraan', 'perkiraan.groupperkiraan', '=', 'groupperkiraan.id')
            ->leftJoin('parameter as statusperkiraan', 'perkiraan.statusperkiraan', '=', 'statusperkiraan.id')
            ->leftJoin('parameter as operator', 'perkiraan.operator', '=', 'operator.id')
            ->where('perkiraan.id', $id);
        $data = $query->first();
        return $data;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status BIGINT UNSIGNED NULL,
            statusnama VARCHAR(100),
            operator BIGINT UNSIGNED NULL,
            operatornama VARCHAR(100),
            groupperkiraan BIGINT UNSIGNED NULL,
            groupperkiraannama VARCHAR(100),
            statusperkiraan BIGINT UNSIGNED NULL,
            statusperkiraannama VARCHAR(100)
        )");
        // dd('test');

        $status = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        $operator = DB::table('parameter')
            ->select('id', 'text')
            ->where('grp', '=', 'OPERATOR')
            ->where('subgrp', '=', 'OPERATOR')
            ->where('default', '=', 'YA')
            ->first();

        $groupperkiraan = DB::table('parameter')
            ->select('id', 'text')
            ->where('grp', '=', 'GROUP PERKIRAAN')
            ->where('subgrp', '=', 'GROUP PERKIRAAN')
            ->where('default', '=', 'YA')
            ->first();

        $statusperkiraan = DB::table('parameter')
            ->select('id', 'text')
            ->where('grp', '=', 'PERKIRAAN')
            ->where('subgrp', '=', 'PERKIRAAN')
            ->first();


        DB::statement("INSERT INTO $tempdefault (status,statusnama,operator,operatornama,groupperkiraan,groupperkiraannama, statusperkiraan, statusperkiraannama) VALUES (?,?,?,?,?,?,?,?)", [
            $status->id, $status->text,
            $operator->id, $operator->text,
            $groupperkiraan->id, $groupperkiraan->text,
            $statusperkiraan->id, $statusperkiraan->text,
        ]);
        $query = DB::table($tempdefault)
            ->select(
                'status',
                'statusnama',
                'operator',
                'operatornama',
                'groupperkiraan',
                'groupperkiraannama',
                'statusperkiraan',
                'statusperkiraannama'
            );

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
                $this->table.seqno,
                $this->table.nama,
                operator.id as operator,
                operator.memo as operatormemo,
                groupperkiraan.id as groupperkiraan,
                groupperkiraan.memo as groupperkiraanmemo,
                $this->table.keterangan,
                statusperkiraan.id as statusperkiraan,
                statusperkiraan.memo as statusperkiraanmemo,
                parameter.id as status,
                parameter.memo as statusmemo,
                $this->table.modifiedby,
                $this->table.created_at,
                $this->table.updated_at
            ")
            )
            ->leftJoin(DB::raw("parameter"), 'perkiraan.status', '=', 'parameter.id')
            ->leftJoin('parameter as operator', 'perkiraan.operator', '=', 'operator.id')
            ->leftJoin('parameter as groupperkiraan', 'perkiraan.groupperkiraan', '=', 'groupperkiraan.id')
            ->leftJoin('parameter as statusperkiraan', 'perkiraan.statusperkiraan', '=', 'statusperkiraan.id');

    }

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->selectColumns($query);
        $query = $this->filter($query);
        $query = $this->sort($query);
        // dd($query->get());

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $temp (
                id BIGINT NULL,
                seqno VARCHAR(100) NULL,
                nama VARCHAR(100) NULL,
                operator VARCHAR(100) NULL,
                operatormemo VARCHAR(100) NULL,
                groupperkiraan VARCHAR(100) NULL,
                groupperkiraanmemo VARCHAR(100) NULL,
                keterangan VARCHAR(255) NULL,
                statusperkiraan VARCHAR(100) NULL,
                statusperkiraanmemo VARCHAR(100) NULL,
                status VARCHAR(100) NULL,
                statusmemo VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");

        DB::table($temp)->insertUsing(["id", "seqno", "nama", "operator", "operatormemo", "groupperkiraan", "groupperkiraanmemo", "keterangan", "statusperkiraan", "statusperkiraanmemo", "status", "statusmemo", "modifiedby", "created_at", "updated_at"], $query);

        return $temp;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'operatormemo') {
            return $query->orderBy('operator.memo', $this->params['sortOrder']);
        }else if($this->params['sortIndex'] == 'statusmemo'){
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        }else if($this->params['sortIndex'] == 'groupperkiraanmemo'){
            return $query->orderBy('groupperkiraan.memo', $this->params['sortOrder']);
        }else if($this->params['sortIndex'] == 'statusperkiraanmemo'){
            return $query->orderBy('statusperkiraan.memo', $this->params['sortOrder']);
        }else{
            return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
        }
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'operatormemo') {
                            $query = $query->where('operator.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'groupperkiraanmemo') {
                            $query = $query->where('groupperkiraan.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'statusperkiraanmemo') {
                            $query = $query->where('statusperkiraan.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', 'like', '%$filters[data]%');
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else if ($filters['field'] == 'groupperkiraanmemo') {
                                $query = $query->where('groupperkiraan.text', '=', $filters['data']);
                            } else if ($filters['field'] == 'statusperkiraanmemo') {
                                $query = $query->where('statusperkiraan.text', '=', $filters['data']);
                            } else if ($filters['field'] == 'statusmemo') {
                                $query = $query->OrwhereRaw('parameter.text', 'like', '%$filters[data]%');
                            } else if ($filters['field'] == 'operatormemo') {
                                $query = $query->OrwhereRaw('operator.text', 'like', '%$filters[data]%');
                            } else {
                                $query = $query->OrwhereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                            }
                        }
                    });
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

    public function processStore(array $data): Perkiraan
    {
        // dd($data);
        $perkiraan = new Perkiraan();
        $perkiraan->nama = $data['nama'] ?? '';
        $perkiraan->operator = $data['operator'] ?? '';
        $perkiraan->groupperkiraan = $data['groupperkiraan'] ?? '';
        $perkiraan->keterangan = $data['keterangan'] ?? '';
        $perkiraan->status = $data['status'];
        $perkiraan->seqno = $data['seqno'];
        $perkiraan->statusperkiraan = $data['statusperkiraan'];
        $perkiraan->modifiedby = auth('api')->user()->name;

        if (!$perkiraan->save()) {
            throw new \Exception('Error Storing Perkiraan.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($perkiraan->getTable()),
            'postingdari' => 'ENTRY PERKIRAAN',
            'idtrans' => $perkiraan->id,
            'nobuktitrans' => $perkiraan->id,
            'aksi' => 'ENTRY',
            'datajson' => $perkiraan->toArray(),
            'modifiedby' => $perkiraan->modifiedby
        ]);

        // dd($perkiraan);
        return $perkiraan;
    }

    public function processUpdate(Perkiraan $perkiraan, array $data): Perkiraan
    {
        $perkiraan->nama = $data['nama'] ?? '';
        $perkiraan->operator = $data['operator'] ?? '';
        $perkiraan->groupperkiraan = $data['groupperkiraan'] ?? '';
        $perkiraan->keterangan = $data['keterangan'] ?? '';
        $perkiraan->status = $data['status'];
        $perkiraan->seqno = $data['seqno'];
        $perkiraan->statusperkiraan = $data['statusperkiraan'];
        $perkiraan->modifiedby = auth('api')->user()->name;

        if (!$perkiraan->save()) {
            throw new \Exception('Error Updating Perkiraan');
        }

        (new LogTrail())->processStore([
            'namatabel' => $perkiraan->getTable(),
            'postingdari' => 'EDIT PERKIRAAN',
            'idtrans' => $perkiraan->id,
            'nobuktitrans' => $perkiraan->id,
            'aksi' => 'EDIT',
            'datajson' => $perkiraan->toArray(),
            'modifiedby' => $perkiraan->modifiedby
        ]);

        return $perkiraan;
    }

    public function processDestroy($id): Perkiraan
    {
        $perkiraan = new Perkiraan();
        $perkiraan = $perkiraan->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($perkiraan->getTable()),
            'postingdari' => 'DELETE PERKIRAAN',
            'idtrans' => $perkiraan->id,
            'nobuktitrans' => $perkiraan->id,
            'aksi' => 'DELETE',
            'datajson' => $perkiraan->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $perkiraan;
    }

    public function editingAt($id, $btn)
    {
        $perkiraan = perkiraan::find($id);
        if ($btn == 'EDIT') {
            $perkiraan->editingby = auth('api')->user()->name;
            $perkiraan->editingat = date('Y-m-d H:i:s');
        } else {

            if ($perkiraan->editingby == auth('api')->user()->name) {
                $perkiraan->editingby = '';
                $perkiraan->editingat = null;
            }
        }
        if (!$perkiraan->save()) {
            throw new \Exception("Error Update perkiraan.");
        }

        return $perkiraan;
    }

    public function cekValidasiAksi($id)
    {
        $transaksibelanja = DB::table('transaksibelanja')
            ->from(
                DB::raw("transaksibelanja as a")
            )
            ->select(
                'a.id',
                'a.perkiraanid',
                'b.nama as perkiraannama',
                'a.karyawanid',
            )
            ->leftJoin('perkiraan as b', 'a.perkiraanid', 'b.id')
            ->where('a.perkiraanid', '=', $id)
            ->first();

        if (isset($transaksibelanja)) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Transaksi Belanja ' . $transaksibelanja->perkiraannama,
                'kodeerror' => 'SATL'
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
}
