<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GroupProduct extends MyModel
{
    use HasFactory;

    protected $table = 'groupproduct';

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
                'groupproduct.id',
                'groupproduct.nama',
                'groupproduct.keterangan',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'groupproduct.modifiedby',
                'groupproduct.created_at',
                'groupproduct.updated_at',
                // DB::raw("'Laporan Jenis EMKL' as judulLaporan"),
                // DB::raw("'" . $getJudul->text . "' as judul"),
                // DB::raw("'Tgl Cetak :'+format(getdate(),'dd-MM-yyyy HH:mm:ss')as tglcetak"),
                // DB::raw(" 'User :".auth('api')->user()->name."' as usercetak")
            )
            ->leftJoin(DB::raw("parameter"), 'groupproduct.status', '=', 'parameter.id');

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('groupproduct.status', '=', $status->id);
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
        $query = DB::table('groupproduct')
            ->select(
                'groupproduct.id',
                'groupproduct.nama',
                'groupproduct.keterangan',
                'parameter.id as status',
                'parameter.text as statusnama',
                'groupproduct.modifiedby',
                'groupproduct.created_at',
                'groupproduct.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'groupproduct.status', '=', 'parameter.id')
            ->where('groupproduct.id', $id);
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
            ")
        )->leftJoin(DB::raw("parameter"), 'groupproduct.status', 'parameter.id');
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
        if ($this->params['sortIndex'] == 'statusmemo') {
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
                        if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl' ) {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        }else if($filters['field'] == 'statusmemo'){
                            $query = $query->where('parameter.text', 'like', '%$filters[data]%');
                        } else{
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            }else if($filters['field'] == 'statusmemo'){
                                $query = $query->OrwhereRaw('parameter.text', 'like', '%$filters[data]%');
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

    public function processStore(array $data): GroupProduct
    {
        $groupProduct = new GroupProduct();
        $groupProduct->nama = $data['nama'] ?? '';
        $groupProduct->keterangan = $data['keterangan'] ?? '';
        $groupProduct->status = $data['status'];
        $groupProduct->modifiedby = auth('api')->user()->name;

        if (!$groupProduct->save()) {
            throw new \Exception('Error storing Group Product.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($groupProduct->getTable()),
            'postingdari' => 'ENTRY GROUP PRODUCT',
            'idtrans' => $groupProduct->id,
            'nobuktitrans' => $groupProduct->id,
            'aksi' => 'ENTRY',
            'datajson' => $groupProduct->toArray(),
            'modifiedby' => $groupProduct->modifiedby
        ]);

        return $groupProduct;
    }

    public function processUpdate(GroupProduct $groupProduct, array $data): GroupProduct
    {
        $groupProduct->nama = $data['nama'] ?? '';
        $groupProduct->keterangan = $data['keterangan'] ?? '';
        $groupProduct->status = $data['status'];
        $groupProduct->modifiedby = auth('api')->user()->user;

        if (!$groupProduct->save()) {
            throw new \Exception('Error updating Group Product');
        }

        (new LogTrail())->processStore([
            'namatabel' => $groupProduct->getTable(),
            'postingdari' => 'EDIT GROUP PRODUCT',
            'idtrans' => $groupProduct->id,
            'nobuktitrans' => $groupProduct->id,
            'aksi' => 'EDIT',
            'datajson' => $groupProduct->toArray(),
            'modifiedby' => $groupProduct->modifiedby
        ]);

        return $groupProduct;
    }

    public function processDestroy($id): GroupProduct
    {
        $groupProduct = new GroupProduct();
        $groupProduct = $groupProduct->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($groupProduct->getTable()),
            'postingdari' => 'DELETE GROUP PRODUCT',
            'idtrans' => $groupProduct->id,
            'nobuktitrans' => $groupProduct->id,
            'aksi' => 'DELETE',
            'datajson' => $groupProduct->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $groupProduct;
    }

    public function editingAt($id, $btn)
    {
        $groupProduct = GroupProduct::find($id);
        if ($btn == 'EDIT') {
            $groupProduct->editingby = auth('api')->user()->name;
            $groupProduct->editingat = date('Y-m-d H:i:s');
        } else {

            if ($groupProduct->editingby == auth('api')->user()->name) {
                $groupProduct->editingby = '';
                $groupProduct->editingat = null;
            }
        }
        if (!$groupProduct->save()) {
            throw new \Exception("Error Update group product.");
        }

        return $groupProduct;
    }
}
