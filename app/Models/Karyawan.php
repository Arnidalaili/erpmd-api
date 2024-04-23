<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Karyawan extends MyModel
{
    use HasFactory;

    protected $table = 'karyawan';

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
        // $getJudul = DB::table('parameter')->from(DB::raw("parameter with (readuncommitted)"))
        //     ->select('text')
        //     ->where('grp', 'JUDULAN LAPORAN')
        //     ->where('subgrp', 'JUDULAN LAPORAN')
        //     ->first();

        $aktif = request()->aktif ?? '';

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'karyawan.id',
                'karyawan.nama',
                'karyawan.nama2',
                'karyawan.username',
                'karyawan.email',
                'karyawan.telepon',
                'karyawan.alamat',
                'karyawan.keterangan',
                'armada.id as armadaid',
                'armada.nama as armadanama',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'karyawan.modifiedby',
                'karyawan.created_at',
                'karyawan.updated_at',
                // DB::raw("'Laporan Jenis EMKL' as judulLaporan"),
                // DB::raw("'" . $getJudul->text . "' as judul"),
                // DB::raw("'Tgl Cetak :'+format(getdate(),'dd-MM-yyyy HH:mm:ss')as tglcetak"),
                // DB::raw(" 'User :".auth('api')->user()->name."' as usercetak")
            )
            ->leftJoin(DB::raw("parameter"), 'karyawan.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("armada"), 'karyawan.armadaid', '=', 'armada.id');

        $this->filter($query);

        $karyawanId = request()->karyawanid;

        if ($karyawanId != '') {
            $query->where('karyawan.id', $karyawanId);
        }

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('karyawan.status', '=', $status->id);
        }
        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);

        if (request()->limit > 0) {
            $this->paginate($query);
        }

        $data = $query->get();

        return $data;
    }

    public function findAll($id)
    {
        $query = DB::table('karyawan')
            ->select(
                'karyawan.id',
                'karyawan.nama',
                'karyawan.nama2',
                'karyawan.telepon',
                'karyawan.alamat',
                'karyawan.keterangan',
                'armada.id as armadaid',
                'armada.nama as armadanama',
                'parameter.id as status',
                'parameter.text as statusnama',
                'karyawan.modifiedby',
                'karyawan.created_at',
                'karyawan.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'karyawan.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("armada"), 'karyawan.armadaid', '=', 'armada.id')
            ->where('karyawan.id', $id);
        $data = $query->first();
        return $data;
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

    public function selectColumns($query)
    {
        return $query->from(
            DB::raw($this->table)
        )
            ->select(
                DB::raw("
            $this->table.id,
            $this->table.nama,
            $this->table.nama2,
            $this->table.alamat,
            $this->table.telepon,
            $this->table.keterangan,
            armada.id as armadaid,
            armada.nama as armadanama,
            parameter.id as status,
            parameter.memo as statusmemo,
            $this->table.modifiedby,
            $this->table.created_at,
            $this->table.updated_at
            ")
            )->leftJoin(DB::raw("parameter"), 'karyawan.status', 'parameter.id')
            ->leftJoin(DB::raw("armada"), 'karyawan.armadaid', 'armada.id');
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
                id BIGINT NULL,
                nama VARCHAR(100) NULL,
                nama2 VARCHAR(100) NULL,
                telepon VARCHAR(13) NULL,
                alamat VARCHAR(255) NULL,
                keterangan VARCHAR(255) NULL,
                armadaid VARCHAR(100) NULL,
                armadanama VARCHAR(100) NULL,
                status VARCHAR(100) NULL,
                statusmemo VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");

        DB::table($temp)->insertUsing(["id", "nama", "nama2", "telepon", "alamat", "keterangan", "armadaid", "armadanama", "status", "statusmemo", "modifiedby", "created_at", "updated_at"], $query);

        return $temp;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        }else if($this->params['sortIndex'] == 'armadanama'){
            return $query->orderBy('armada.nama', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', '=', "$filters[data]");
                        } else if ($filters['field'] == 'armadanama') {
                            $query = $query->where('armada.nama', 'like', "%$filters[data]%");
                        }else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else if ($filters['field'] == 'statusmemo') {
                                $query = $query->OrwhereRaw('parameter.text', 'like', '%$filters[data]%');
                            } else if ($filters['field'] == 'armadanama') {
                                $query = $query->OrwhereRaw('armada.nama', 'like', '%$filters[data]%');
                            } else if ($filters['field'] == 'modified_by') {
                                $query = $query->OrwhereRaw('karyawan.modifiedby', 'like', '%$filters[data]%');
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

    public function processStore(array $data): Karyawan
    {
        $karyawan = new Karyawan();
        $karyawan->nama = $data['nama'] ?? '';
        $karyawan->nama2 = $data['nama2'] ?? '';
        $karyawan->username = $data['username'] ?? '';
        $karyawan->email = $data['email'] ?? '';
        $karyawan->telepon = $data['telepon'] ?? '';
        $karyawan->alamat = $data['alamat'] ?? '';
        $karyawan->keterangan = $data['keterangan'] ?? '';
        $karyawan->armadaid = $data['armadaid'];
        $karyawan->status = $data['status'];
        $karyawan->modifiedby = auth('api')->user()->name;

        if (!$karyawan->save()) {
            throw new \Exception('Error storing Karyawan.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($karyawan->getTable()),
            'postingdari' => 'ENTRY KARYAWAN',
            'idtrans' => $karyawan->id,
            'nobuktitrans' => $karyawan->id,
            'aksi' => 'ENTRY',
            'datajson' => $karyawan->toArray(),
            'modifiedby' => $karyawan->modifiedby
        ]);

        $statusAkses = DB::table("parameter")
            ->select('id')
            ->where('grp', '=', 'STATUS AKSES')
            ->where('text', '=', 'PUBLIC')
            ->first();


        $roleIds = DB::table("role")
            ->select('id')
            ->where('rolename', '=', 'KARYAWAN')
            ->first();

        $addUser = [
            'user' => $data['username'],
            'name' => $data['nama'],
            'email' =>  $data['email'] ?? str_replace(' ', '', $data['nama']) . '@gmail.com',
            'password' => '123456',
            'customerid' => 1,
            'dashboard' => '',
            'status' => 1,
            'statusakses' => $statusAkses->id,
            'tablekaryawan' => 'YES',
            'roleids' => [$roleIds->id]
        ];

        

        (new User())->processStore($addUser);

        return $karyawan;
    }

    public function processUpdate(Karyawan $karyawan, array $data): Karyawan
    {
        $karyawan->nama = $data['nama'] ?? '';
        $karyawan->nama2 = $data['nama2'] ?? '';
        $karyawan->username = $data['username'] ?? '';
        $karyawan->email = $data['email'] ?? '';
        $karyawan->telepon = $data['telepon'] ?? '';
        $karyawan->alamat = $data['alamat'] ?? '';
        $karyawan->keterangan = $data['keterangan'] ?? '';
        $karyawan->armadaid = $data['armadaid'];
        $karyawan->status = $data['status'];
        $karyawan->modifiedby = auth('api')->user()->user;

        $updateUser =  DB::table('user as a')->where('user', '=', $karyawan->nama)
            ->update([
                'a.name' => $karyawan->nama,
                'a.user' => $karyawan->username,
                'a.email' => $karyawan->email
            ]);

        if (!$karyawan->save()) {
            throw new \Exception('Error updating Karyawan');
        }

        (new LogTrail())->processStore([
            'namatabel' => $karyawan->getTable(),
            'postingdari' => 'EDIT KARYAWAN',
            'idtrans' => $karyawan->id,
            'nobuktitrans' => $karyawan->id,
            'aksi' => 'EDIT',
            'datajson' => $karyawan->toArray(),
            'modifiedby' => $karyawan->modifiedby
        ]);

        return $karyawan;
    }

    public function processDestroy($id): Karyawan
    {
        $getUsername = Karyawan::find($id)->nama;
        DB::table('user')->where('user', '=', $getUsername)->where('tablekaryawan', '=' ,'YES')->delete();
        $karyawan = new Karyawan();
        $karyawan = $karyawan->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($karyawan->getTable()),
            'postingdari' => 'DELETE KARYAWAN',
            'idtrans' => $karyawan->id,
            'nobuktitrans' => $karyawan->id,
            'aksi' => 'DELETE',
            'datajson' => $karyawan->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $karyawan;
    }

    public function editingAt($id, $btn)
    {
        $karyawan = Karyawan::find($id);
        if ($btn == 'EDIT') {
            $karyawan->editingby = auth('api')->user()->name;
            $karyawan->editingat = date('Y-m-d H:i:s');
        } else {

            if ($karyawan->editingby == auth('api')->user()->name) {
                $karyawan->editingby = '';
                $karyawan->editingat = null;
            }
        }
        if (!$karyawan->save()) {
            throw new \Exception("Error Update karyawan.");
        }

        return $karyawan;
    }
}
