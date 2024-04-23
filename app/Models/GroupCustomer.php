<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GroupCustomer extends MyModel
{
    use HasFactory;

    protected $table = 'groupcustomer';

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
                'groupcustomer.id',
                'groupcustomer.nama',
                'groupcustomer.keterangan',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'groupcustomer.modifiedby',
                'groupcustomer.created_at',
                'groupcustomer.updated_at',
                // DB::raw("'Laporan Jenis EMKL' as judulLaporan"),
                // DB::raw("'" . $getJudul->text . "' as judul"),
                // DB::raw("'Tgl Cetak :'+format(getdate(),'dd-MM-yyyy HH:mm:ss')as tglcetak"),
                // DB::raw(" 'User :".auth('api')->user()->name."' as usercetak")
            )
            ->leftJoin(DB::raw("parameter"), 'groupcustomer.status', '=', 'parameter.id');

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('groupcustomer.status', '=', $status->id);
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


        DB::statement("INSERT INTO $tempdefault (status,statusnama) VALUES (?,?)", [$status->id,$status->text]);

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
                $this->table.keterangan,
                parameter.id as status,
                parameter.memo as statusmemo,
                $this->table.modifiedby,
                $this->table.created_at,
                $this->table.updated_at
            "))
            ->leftJoin(DB::raw("parameter"), 'groupcustomer.status', '=', 'parameter.id');
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
                keterangan VARCHAR(255) NULL,
                status VARCHAR(100) NULL,
                statusmemo VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");
       

        DB::table($temp)->insertUsing(["id", "nama", "keterangan", "status","statusmemo", "modifiedby", "created_at", "updated_at"],$query);
        
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

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', '=', "$filters[data]");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl' ) {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        }else{
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
                            }else {
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
        $query = DB::table('groupcustomer')
            ->select(
                'groupcustomer.id',
                'groupcustomer.nama',
                'groupcustomer.keterangan',
                'parameter.id as status',
                'parameter.text as statusnama',
                'groupcustomer.modifiedby',
                'groupcustomer.created_at',
                'groupcustomer.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'groupcustomer.status', '=', 'parameter.id')
            ->where('groupcustomer.id', $id);
        $data = $query->first();


        return $data;
    }

    public function processStore(array $data): GroupCustomer
    {
        $groupCustomer = new GroupCustomer();
        $groupCustomer->nama = $data['nama'] ?? '';
        $groupCustomer->keterangan = $data['keterangan'] ?? '';
        $groupCustomer->status = $data['status'];
        $groupCustomer->modifiedby = auth('api')->user()->name;

        if (!$groupCustomer->save()) {
            throw new \Exception('Error storing Group Customer.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($groupCustomer->getTable()),
            'postingdari' => 'ENTRY GROUP CUSTOMER',
            'idtrans' => $groupCustomer->id,
            'nobuktitrans' => $groupCustomer->id,
            'aksi' => 'ENTRY',
            'datajson' => $groupCustomer->toArray(),
            'modifiedby' => $groupCustomer->modifiedby
        ]);

        return $groupCustomer;
    }

    public function processUpdate(GroupCustomer $groupCustomer, array $data): GroupCustomer
    {
        $groupCustomer->nama = $data['nama'] ?? '';
        $groupCustomer->keterangan = $data['keterangan'] ?? '';
        $groupCustomer->status = $data['status'];
        $groupCustomer->modifiedby = auth('api')->user()->user;

        if (!$groupCustomer->save()) {
            throw new \Exception('Error updating Group Customer');
        }

        (new LogTrail())->processStore([
            'namatabel' => $groupCustomer->getTable(),
            'postingdari' => 'EDIT GROUP CUSTOMER',
            'idtrans' => $groupCustomer->id,
            'nobuktitrans' => $groupCustomer->id,
            'aksi' => 'EDIT',
            'datajson' => $groupCustomer->toArray(),
            'modifiedby' => $groupCustomer->modifiedby
        ]);

        return $groupCustomer;
    }

    public function processDestroy($id): GroupCustomer
    {
        $groupCustomer = new GroupCustomer();
        $groupCustomer = $groupCustomer->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($groupCustomer->getTable()),
            'postingdari' => 'DELETE GROUP CUSTOMER',
            'idtrans' => $groupCustomer->id,
            'nobuktitrans' => $groupCustomer->id,
            'aksi' => 'DELETE',
            'datajson' => $groupCustomer->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $groupCustomer;
    }

    public function editingAt($id, $btn)
    {
        $groupCustomer  = groupCustomer ::find($id);
        if ($btn == 'EDIT') {
            $groupCustomer ->editingby = auth('api')->user()->name;
            $groupCustomer ->editingat = date('Y-m-d H:i:s');
        } else {

            if ($groupCustomer ->editingby == auth('api')->user()->name) {
                $groupCustomer ->editingby = '';
                $groupCustomer ->editingat = null;
            }
        }
        if (!$groupCustomer ->save()) {
            throw new \Exception("Error Update group customer .");
        }

        return $groupCustomer ;
    }
}
