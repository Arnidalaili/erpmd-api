<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Customer extends MyModel
{
    use HasFactory;

    protected $table = 'customer';

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
                'customer.id',
                'customer.nama',
                'customer.nama2',
                'customer.username',
                'customer.email',
                'customer.telepon',
                'customer.alamat',
                'customer.keterangan',
                'owner.id as ownerid',
                'owner.nama as ownernama',
                'hargaproduct.id as hargaproduct',
                'hargaproduct.memo as hargaproductmemo',
                'groupcustomer.id as groupid',
                'groupcustomer.nama as groupnama',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'customer.modifiedby',
                'customer.created_at',
                'customer.updated_at',
                // DB::raw("'Laporan Jenis EMKL' as judulLaporan"),
                // DB::raw("'" . $getJudul->text . "' as judul"),
                // DB::raw("'Tgl Cetak :'+format(getdate(),'dd-MM-yyyy HH:mm:ss')as tglcetak"),
                // DB::raw(" 'User :".auth('api')->user()->name."' as usercetak")
            )
            ->leftJoin(DB::raw("parameter"), 'customer.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("owner"), 'customer.ownerid', '=', 'owner.id')
            ->leftJoin('parameter as hargaproduct', 'customer.hargaproduct', '=', 'hargaproduct.id')
            ->leftJoin(DB::raw("groupcustomer"), 'customer.groupid', '=', 'groupcustomer.id');

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('customer.status', '=', $status->id);
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
        $query = DB::table('customer')
            ->select(
                'customer.id',
                'customer.nama',
                'customer.nama2',
                'customer.telepon',
                'customer.alamat',
                'customer.keterangan',
                'owner.id as ownerid',
                'owner.nama as ownernama',
                'hargaproduct.id as hargaproduct',
                'hargaproduct.text as hargaproductnama',
                'groupcustomer.id as groupid',
                'groupcustomer.nama as groupnama',
                'parameter.id as status',
                'parameter.text as statusnama',
                'customer.modifiedby',
                'customer.created_at',
                'customer.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'customer.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("owner"), 'customer.ownerid', '=', 'owner.id')
            ->leftJoin('parameter as hargaproduct', 'customer.hargaproduct', '=', 'hargaproduct.id')
            ->leftJoin(DB::raw("groupcustomer"), 'customer.groupid', '=', 'groupcustomer.id')
            ->where('customer.id', $id);

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
            owner.id as ownerid,
            owner.nama as ownernama,
            hargaproduct.id as hargaproduct,
            hargaproduct.memo as hargaproductmemo,
            groupcustomer.id as groupid,
            groupcustomer.nama as groupnama,
            parameter.id as status,
            parameter.memo as statusmemo,
            $this->table.modifiedby,
            $this->table.created_at,
            $this->table.updated_at
            ")
            )->leftJoin(DB::raw("parameter"), 'customer.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("owner"), 'customer.ownerid', '=', 'owner.id')
            ->leftJoin('parameter as hargaproduct', 'customer.hargaproduct', '=', 'hargaproduct.id')
            ->leftJoin(DB::raw("groupcustomer"), 'customer.groupid', '=', 'groupcustomer.id');
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
                telepon VARCHAR(100) NULL,
                alamat VARCHAR(255) NULL,
                keterangan VARCHAR(255) NULL,
                ownerid VARCHAR(100) NULL,
                ownernama VARCHAR(100) NULL,
                hargaproduct VARCHAR(100) NULL,
                hargaproductmemo VARCHAR(100) NULL,
                groupid VARCHAR(100) NULL,
                groupnama VARCHAR(100) NULL,
                status VARCHAR(100) NULL,
                statusmemo VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");

        DB::table($temp)->insertUsing(["id", "nama", "nama2", "telepon", "alamat", "keterangan", "ownerid", "ownernama", "hargaproduct", "hargaproductmemo", "groupid", "groupnama", "status", "statusmemo", "modifiedby", "created_at", "updated_at"], $query);

        return $temp;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'hargaproductmemo') {
            return $query->orderBy('hargaproduct.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'groupnama') {
            return $query->orderBy('groupcustomer.nama', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', '=', "$filters[data]");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'ownernama') {
                            $query = $query->where('owner.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'hargaproductmemo') {
                            $query = $query->where('hargaproduct.text', '=', "$filters[data]");
                        } else if ($filters['field'] == 'groupnama') {
                            $query = $query->where('groupcustomer.nama', 'like', "%$filters[data]%");
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'statusmemo') {
                                $query = $query->orWhere('parameter.text', '=', "$filters[data]");
                            } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else if ($filters['field'] == 'ownernama') {
                                $query = $query->where('owner.nama', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'hargaproductmemo') {
                                $query = $query->where('hargaproduct.text', '=', "$filters[data]");
                            } else if ($filters['field'] == 'groupnama') {
                                $query = $query->where('groupcustomer.nama', 'like', "%$filters[data]%");
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

    public function processStore(array $data): Customer
    {

        $customer = new Customer();
        $customer->nama = $data['nama'] ?? '';
        $customer->nama2 = $data['nama2'] ?? '';
        $customer->username = $data['username'] ?? '';
        $customer->email = $data['email'] ?? '';
        $customer->telepon = $data['telepon'] ?? 0;
        $customer->alamat = $data['alamat'] ?? '';
        $customer->keterangan = $data['keterangan'] ?? '';
        $customer->ownerid = $data['ownerid'];
        $customer->hargaproduct = $data['hargaproduct'];
        $customer->groupid = $data['groupid'];
        $customer->status = $data['status'];
        $customer->modifiedby = auth('api')->user()->name;

        if (!$customer->save()) {
            throw new \Exception('Error storing Customer.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($customer->getTable()),
            'postingdari' => 'ENTRY CUSTOMER',
            'idtrans' => $customer->id,
            'nobuktitrans' => $customer->id,
            'aksi' => 'ENTRY',
            'datajson' => $customer->toArray(),
            'modifiedby' => $customer->modifiedby
        ]);

        $statusAkses = DB::table("parameter")
            ->select('id')
            ->where('grp', '=', 'STATUS AKSES')
            ->where('text', '=', 'PUBLIC')
            ->first();


        $roleIds = DB::table("role")
            ->select('id')
            ->where('rolename', '=', 'CUSTOMER')
            ->first();


        $addUser = [
            'user' => $data['username'],
            'name' => $data['nama'],
            'email' => $data['email'] ?? str_replace(' ', '', $data['nama']) . '@gmail.com',
            'password' => '123456',
            'customerid' => $customer->id,
            'dashboard' => '',
            'status' => 1,
            'statusakses' => $statusAkses->id,
            'tablecustomer' => 'YES',
            'roleids' => [$roleIds->id]
        ];

        // dd($addUser);

        (new User())->processStore($addUser);



        return $customer;
    }

    public function processUpdate(Customer $customer, array $data): Customer
    {

        $customer->nama = $data['nama'] ?? '';
        $customer->nama2 = $data['nama2'] ?? '';
        $customer->username = $data['username'] ?? '';
        $customer->email = $data['email'] ?? '';
        $customer->telepon = $data['telepon'] ?? '';
        $customer->alamat = $data['alamat'] ?? '';
        $customer->keterangan = $data['keterangan'] ?? '';
        $customer->ownerid = $data['ownerid'];
        $customer->hargaproduct = $data['hargaproduct'];
        $customer->groupid = $data['groupid'];
        $customer->status = $data['status'];
        $customer->modifiedby = auth('api')->user()->user;

        $updateUser =  DB::table('user as a')->where('customerid', '=', $customer->id)
            ->update([
                'a.name' => $customer->nama,
                'a.user' => $customer->username,
                'a.email' => $customer->email
            ]);

        if (!$customer->save()) {
            throw new \Exception('Error updating Customer');
        }

        (new LogTrail())->processStore([
            'namatabel' => $customer->getTable(),
            'postingdari' => 'EDIT CUSTOMER',
            'idtrans' => $customer->id,
            'nobuktitrans' => $customer->id,
            'aksi' => 'EDIT',
            'datajson' => $customer->toArray(),
            'modifiedby' => $customer->modifiedby
        ]);

        return $customer;
    }

    public function processDestroy($id): Customer
    {
        $getUsername = Customer::find($id)->nama;
        DB::table('user')->where('user', '=', $getUsername)->where('tablecustomer', '=', 'YES')->delete();
        $customer = new Customer();
        $customer = $customer->lockAndDestroy($id);


        (new LogTrail())->processStore([
            'namatabel' => strtoupper($customer->getTable()),
            'postingdari' => 'DELETE CUSTOMER',
            'idtrans' => $customer->id,
            'nobuktitrans' => $customer->id,
            'aksi' => 'DELETE',
            'datajson' => $customer->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $customer;
    }

    public function editingAt($id, $btn)
    {
        $customer = Customer::find($id);
        if ($btn == 'EDIT') {
            $customer->editingby = auth('api')->user()->name;
            $customer->editingat = date('Y-m-d H:i:s');
        } else {

            if ($customer->editingby == auth('api')->user()->name) {
                $customer->editingby = '';
                $customer->editingat = null;
            }
        }
        if (!$customer->save()) {
            throw new \Exception("Error Update customer.");
        }

        return $customer;
    }

    public function cekValidasiAksi($id)
    {
        $pesananFinalHeader = DB::table('pesananfinalheader')
            ->from(
                DB::raw("pesananfinalheader as a")
            )
            ->select(
                'a.id',
                'a.customerid'
            )
            ->where('a.customerid', '=', $id)
            ->first();

        if ($pesananFinalHeader) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Pembelian ',
                'kodeerror' => 'CTB'
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
