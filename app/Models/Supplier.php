<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Supplier extends MyModel
{
    use HasFactory;

    protected $table = 'supplier';

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
        $ownerid = request()->ownerid ?? '';

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'supplier.id',
                DB::raw('0 as ownerid'),
                'supplier.id as supplierid',
                'supplier.nama',
                'supplier.telepon',
                'supplier.alamat',
                'supplier.keterangan',
                'karyawan.id as karyawanid',
                'karyawan.nama as karyawannama',
                'supplier.potongan',
                'top.id as top',
                'top.text as toptext',
                'top.memo as topmemo',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'supplier.modifiedby',
                'supplier.created_at',
                'supplier.updated_at',
                DB::raw("'supplier' as flag")
            )
            ->leftJoin(DB::raw("parameter"), 'supplier.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("karyawan"), 'supplier.karyawanid', '=', 'karyawan.id')
            ->leftJoin('parameter as top', 'supplier.top', '=', 'top.id');


        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('supplier.status', '=', $status->id);
        }

        if ($ownerid != '') {
            // create table temp
            $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

            DB::statement("CREATE TEMPORARY TABLE $temp (
                id INT UNSIGNED,
                ownerid INT UNSIGNED,
                supplierid INT UNSIGNED,
                nama VARCHAR(100),
                telepon VARCHAR(255),
                alamat VARCHAR(255),
                keterangan VARCHAR(255),
                karyawanid INT UNSIGNED,
                karyawannama VARCHAR(100),
                potongan FLOAT,
                top INT UNSIGNED,
                toptext VARCHAR(100),
                topmemo VARCHAR(100),
                status INT,
                statusmemo VARCHAR(200),
                modifiedby VARCHAR(200),
                created_at DATETIME,
                updated_at DATETIME,
                flag VARCHAR(100),
                position INT AUTO_INCREMENT PRIMARY KEY
                )
            ");

            // insert supplier to temp
            DB::table($temp)->insertUsing([
                "id", "ownerid", "supplierid", "nama", "telepon", "alamat", "keterangan", "karyawanid", "karyawannama", "potongan", "top", "toptext", "topmemo", "status", "statusmemo", "modifiedby", "created_at", "updated_at", "flag"
            ], $query);

            // select owner
            $ownerQuery = DB::table('owner')
                ->select(
                    'owner.id',
                    'owner.id as ownerid',
                    DB::raw('0 as supplierid'),
                    'owner.nama',
                    'owner.telepon',
                    'owner.alamat',
                    'owner.keterangan',
                    DB::raw('NULL as karyawanid'),
                    DB::raw('NULL as karyawannama'),
                    DB::raw('0 as potongan'),
                    DB::raw('NULL as top'),
                    DB::raw('NULL as toptext'),
                    DB::raw('NULL as topmemo'),
                    DB::raw('NULL as status'),
                    DB::raw('NULL as statusmemo'),
                    'owner.modifiedby',
                    'owner.created_at',
                    'owner.updated_at',
                    DB::raw("'owner' as flag")
                );

            // insert owner to temp
            DB::table($temp)->insertUsing([
                "id", "ownerid", "supplierid", "nama", "telepon", "alamat", "keterangan", "karyawanid", "karyawannama", "potongan", "top", "toptext", "topmemo", "status", "statusmemo", "modifiedby", "created_at", "updated_at", "flag"
            ], $ownerQuery);

            $query = DB::table(DB::raw($temp))
                ->select(
                    // DB::raw("row_number() OVER () as row_num"),
                    DB::raw("$temp.ownerid"),
                    DB::raw("$temp.supplierid"),
                    DB::raw("$temp.nama"),
                    DB::raw("$temp.telepon"),
                    DB::raw("$temp.alamat"),
                    DB::raw("$temp.keterangan"),
                    DB::raw("$temp.karyawanid"),
                    DB::raw("$temp.karyawannama"),
                    DB::raw("$temp.potongan"),
                    DB::raw("$temp.top"),
                    DB::raw("$temp.toptext"),
                    DB::raw("$temp.topmemo"),
                    DB::raw("$temp.status"),
                    DB::raw("$temp.statusmemo"),
                    DB::raw("$temp.modifiedby"),
                    DB::raw("$temp.created_at"),
                    DB::raw("$temp.updated_at"),
                    DB::raw("$temp.flag"),
                );

            $this->filterOwner($query,$temp);
            $this->totalRows = $query->count();
            $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
            // $this->sort($query);
            $this->paginate($query);
            
            // dd($query->get());
            $result = $query->get();


        } else {

            $this->filter($query);

            $this->totalRows = $query->count();
            $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

            $this->sort($query);
            $this->paginate($query);
        }
        $data = $query->get();

        if ($ownerid != '') {
            return $result;
        } else {
            return $data;
        }
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status BIGINT UNSIGNED NULL,
            statusnama VARCHAR(100),
            top BIGINT UNSIGNED NULL,
            topnama VARCHAR(100)
        )");

        $status = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        $top = DB::table('parameter')
            ->select('id', 'text')
            ->where('grp', '=', 'TOP')
            ->where('subgrp', '=', 'TOP')
            ->where('default', '=', 'YA')
            ->first();

        DB::statement("INSERT INTO $tempdefault (status, statusnama, top, topnama) VALUES (?,?,?,?)", [$status->id, $status->text, $top->id, $top->text]);

        $query = DB::table($tempdefault)
            ->select(
                'status',
                'statusnama',
                'top',
                'topnama'
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
                $this->table.telepon,
                $this->table.alamat,
                $this->table.keterangan,
                karyawan.id as karyawanid,
                karyawan.nama as karyawannama, 
                supplier.potongan,
                top.id as top,
                top.memo as topmemo,
                parameter.id as status,
                parameter.memo as statusmemo,
                $this->table.modifiedby,
                $this->table.created_at,
                $this->table.updated_at
            ")
            )
            ->leftJoin(DB::raw("parameter"), 'supplier.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("karyawan"), 'supplier.karyawanid', '=', 'karyawan.id')
            ->leftJoin('parameter as top', 'supplier.top', '=', 'top.id');
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
                telepon VARCHAR(255) NULL,
                alamat VARCHAR(255) NULL,
                keterangan VARCHAR(255) NULL,
                karyawanid VARCHAR(100) NULL,
                karyawannama VARCHAR(100) NULL,
                potongan FLOAT NULL,
                top VARCHAR(100) NULL,
                topmemo VARCHAR(100) NULL,
                status VARCHAR(100) NULL,
                statusmemo VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");


        DB::table($temp)->insertUsing(["id", "nama", "telepon", "alamat", "keterangan", "karyawanid", "karyawannama", "potongan", "top", "topmemo", "status", "statusmemo", "modifiedby", "created_at", "updated_at"], $query);

        return $temp;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'topmemo') {
            return $query->orderBy('top.memo', $this->params['sortOrder']);
        }else if($this->params['sortIndex'] == 'statusmemo'){
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        }else{
            return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
        }
    }

    public function filterOwner($query,$temp, $relationFields = [])
    {
        
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                   
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        // dd($filters['field'] == 'nama');
                        if (strpos($filters['field'], 'supplier.') === 0) {
                            // Filter untuk kolom supplier
                            $field = str_replace('supplier.', '', $filters['field']);
                            if ($field == 'created_at' || $field == 'updated_at' || $field == 'tgl') {
                                $query = $query->whereRaw("DATE_FORMAT($this->table.$field, '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else {
                                $query = $query->whereRaw($this->table . "." .  $field . " LIKE '%" . escapeLike($filters['data']) . "%'");
                            }
                        } elseif ($filters['field'] == 'nama') {
                            // Filter untuk kolom owner
                            $field = str_replace('owner.', '', $filters['field']);
                          
                            if ($field == 'created_at' || $field == 'updated_at' || $field == 'tgl') {
                                $query = $query->whereRaw("DATE_FORMAT(owner.$field, '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            }else if ($filters['field'] == 'nama') {
                                $query = $query->where("$temp.nama", 'like', "%$filters[data]%");
                            } else {
                                $query = $query->whereRaw("owner.$field LIKE '%" . escapeLike($filters['data']) . "%'");
                            }
                        }
                    }
                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if (strpos($filters['field'], 'supplier.') === 0) {
                                // Filter untuk kolom supplier
                                $field = str_replace('supplier.', '', $filters['field']);
                                if ($field == 'created_at' || $field == 'updated_at') {
                                    $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$field, '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                                } else {
                                    $query = $query->OrwhereRaw($this->table . "." .  $field . " LIKE '%" . escapeLike($filters['data']) . "%'");
                                }
                            } elseif (strpos($filters['field'], 'owner.') === 0) {
                                // Filter untuk kolom owner
                                $field = str_replace('owner.', '', $filters['field']);
                                if ($field == 'created_at' || $field == 'updated_at') {
                                    $query = $query->OrwhereRaw("DATE_FORMAT(owner.$field, '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                                } else {
                                    $query = $query->OrwhereRaw("owner.$field LIKE '%" . escapeLike($filters['data']) . "%'");
                                }
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


    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'statusmemo') {
                            $query->where('parameter.text', '=', "$filters[data]");
                        } else if ($filters['field'] == 'topmemo') {
                            $query = $query->where('top.text', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'karyawannama') {
                            $query = $query->where('karyawan.nama', 'like', "%$filters[data]%");
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
                            } else if ($filters['field'] == 'statusmemo') {
                                $query = $query->OrwhereRaw('parameter.text', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'karyawannama') {
                                $query = $query->OrwhereRaw('karyawan.nama', 'like', "%$filters[data]%");
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

    public function findAll($id)
    {
        $query = DB::table('supplier')
            ->select(
                'supplier.id',
                'supplier.nama',
                'supplier.telepon',
                'supplier.alamat',
                'supplier.keterangan',
                'karyawan.id as karyawanid',
                'karyawan.nama as karyawannama',
                'supplier.potongan',
                'top.id as top',
                'top.text as topnama',
                'parameter.id as status',
                'parameter.text as statusnama',
                'supplier.modifiedby',
                'supplier.created_at',
                'supplier.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'supplier.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("karyawan"), 'supplier.karyawanid', '=', 'karyawan.id')
            ->leftJoin('parameter as top', 'supplier.top', '=', 'top.id')
            ->where('supplier.id', $id);
        $data = $query->first();
        return $data;
    }

    public function processStore(array $data): Supplier
    {
        $supplier = new Supplier();
        $supplier->nama = $data['nama'] ?? '';
        $supplier->telepon = $data['telepon'] ?? '';
        $supplier->alamat = $data['alamat'] ?? '';
        $supplier->keterangan = $data['keterangan'] ?? '';
        $supplier->karyawanid = $data['karyawanid'] ?? '';
        $supplier->potongan = $data['potongan'] ?? '';
        $supplier->top = $data['top'] ?? '';
        $supplier->status = $data['status'];
        $supplier->modifiedby = auth('api')->user()->name;

        if (!$supplier->save()) {
            throw new \Exception('Error storing Supplier.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($supplier->getTable()),
            'postingdari' => 'ENTRY SUPPLIER',
            'idtrans' => $supplier->id,
            'nobuktitrans' => $supplier->id,
            'aksi' => 'ENTRY',
            'datajson' => $supplier->toArray(),
            'modifiedby' => $supplier->modifiedby
        ]);
        return $supplier;
    }

    public function processUpdate(Supplier $supplier, array $data): Supplier
    {
        $supplier->nama = $data['nama'] ?? '';
        $supplier->telepon = $data['telepon'] ?? '';
        $supplier->alamat = $data['alamat'] ?? '';
        $supplier->keterangan = $data['keterangan'] ?? '';
        $supplier->karyawanid = $data['karyawanid'];
        $supplier->potongan = $data['potongan'];
        $supplier->top = $data['top'];
        $supplier->status = $data['status'];
        $supplier->modifiedby = auth('api')->user()->user;

        if (!$supplier->save()) {
            throw new \Exception('Error updating Supplier');
        }

        (new LogTrail())->processStore([
            'namatabel' => $supplier->getTable(),
            'postingdari' => 'EDIT SUPPLIER',
            'idtrans' => $supplier->id,
            'nobuktitrans' => $supplier->id,
            'aksi' => 'EDIT',
            'datajson' => $supplier->toArray(),
            'modifiedby' => $supplier->modifiedby
        ]);

        return $supplier;
    }

    public function processDestroy($id): Supplier
    {
        $supplier = new Supplier();
        $supplier = $supplier->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($supplier->getTable()),
            'postingdari' => 'DELETE SUPPLIER',
            'idtrans' => $supplier->id,
            'nobuktitrans' => $supplier->id,
            'aksi' => 'DELETE',
            'datajson' => $supplier->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $supplier;
    }

    public function editingAt($id, $btn)
    {
        $supplier = Supplier::find($id);
        if ($btn == 'EDIT') {
            $supplier->editingby = auth('api')->user()->name;
            $supplier->editingat = date('Y-m-d H:i:s');
        } else {

            if ($supplier->editingby == auth('api')->user()->name) {
                $supplier->editingby = '';
                $supplier->editingat = null;
            }
        }
        if (!$supplier->save()) {
            throw new \Exception("Error Update supplier.");
        }

        return $supplier;
    }
}
