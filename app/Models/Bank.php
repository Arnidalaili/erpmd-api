<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Bank extends MyModel
{
    use HasFactory;

    protected $table = 'bank';

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

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'bank.id',
                'bank.nama',
                'tipebank.id as tipebank',
                'tipebank.memo as tipebankmemo',
                'bank.keterangan',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'bank.modifiedby',
                'bank.created_at',
                'bank.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'bank.status', '=', 'parameter.id')
            ->leftJoin('parameter as tipebank', 'bank.tipebank', '=', 'tipebank.id');

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('bank.status', '=', $status->id);
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

        $query = DB::table('bank')
            ->select(
                'bank.id',
                'bank.nama',
                'tipebank.id as tipebank',
                'tipebank.text as tipebanknama',
                'bank.keterangan',
                'parameter.id as status',
                'parameter.text as statusnama',
                'bank.modifiedby',
                'bank.created_at',
                'bank.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'bank.status', '=', 'parameter.id')
            ->leftJoin('parameter as tipebank', 'bank.tipebank', '=', 'tipebank.id')
            ->where('bank.id', $id);
        $data = $query->first();
        return $data;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status BIGINT UNSIGNED NULL,
            statusnama VARCHAR(100),
            tipebank BIGINT UNSIGNED NULL,
            tipebanknama VARCHAR(100)
        )");

        $status = DB::table("parameter")
            ->select('id','text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        $tipebank = DB::table('parameter')
            ->select('id', 'text')
            ->where('grp', '=', 'TIPE BANK')
            ->where('subgrp', '=', 'TIPE BANK')
            ->where('default', '=', 'YA')
            ->first();

        DB::statement("INSERT INTO $tempdefault (status,statusnama,tipebank, tipebanknama) VALUES (?,?,?,?)", [ $status->id, $status->text, $tipebank->id, $tipebank->text]);
        $query = DB::table($tempdefault)
            ->select(
                'status',
                'statusnama',
                'tipebank',
                'tipebanknama'
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
                tipebank.id as tipebank,
                tipebank.memo as tipebankmemo,
                $this->table.keterangan,
                parameter.id as status,
                parameter.memo as statusmemo,
                $this->table.modifiedby,
                $this->table.created_at,
                $this->table.updated_at
            ")
            )
            ->leftJoin(DB::raw("parameter"), 'bank.status', '=', 'parameter.id')
            ->leftJoin('parameter as tipebank', 'bank.tipebank', '=', 'tipebank.id');
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
                tipebank VARCHAR(100) NULL,
                tipebankmemo VARCHAR(100) NULL,
                keterangan VARCHAR(255) NULL,
                status VARCHAR(100) NULL,
                statusmemo VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");

        DB::table($temp)->insertUsing(["id", "nama", "tipebank","tipebankmemo", "keterangan", "status","statusmemo", "modifiedby", "created_at", "updated_at"],$query);

        return $temp;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'tipebankmemo') {
            return $query->orderBy('tipebank.memo', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'tipebankmemo') {
                            $query = $query->where('tipebank.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        }else if($filters['field'] == 'statusmemo'){
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
                            }else if($filters['field'] == 'statusmemo'){
                                $query = $query->OrwhereRaw('parameter.text', 'like', '%$filters[data]%');
                            }else if($filters['field'] == 'tipebankmemo'){
                                $query = $query->OrwhereRaw('tipebank.text', 'like', '%$filters[data]%');
                            }  else {
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

    public function processStore(array $data): Bank
    {
        $bank = new Bank();
        $bank->nama = $data['nama'] ?? '';
        $bank->tipebank = $data['tipebank'] ?? '';
        $bank->keterangan = $data['keterangan'] ?? '';
        $bank->status = $data['status'];
        $bank->modifiedby = auth('api')->user()->name;

        if (!$bank->save()) {
            throw new \Exception('Error storing Bank.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($bank->getTable()),
            'postingdari' => 'ENTRY BANK',
            'idtrans' => $bank->id,
            'nobuktitrans' => $bank->id,
            'aksi' => 'ENTRY',
            'datajson' => $bank->toArray(),
            'modifiedby' => $bank->modifiedby
        ]);

        return $bank;
    }

    public function processUpdate(Bank $bank, array $data): Bank
    {
        $bank->nama = $data['nama'] ?? '';
        $bank->tipebank = $data['tipebank'] ?? '';
        $bank->keterangan = $data['keterangan'] ?? '';
        $bank->status = $data['status'];
        $bank->modifiedby = auth('api')->user()->name;

        if (!$bank->save()) {
            throw new \Exception('Error updating Bank');
        }

        (new LogTrail())->processStore([
            'namatabel' => $bank->getTable(),
            'postingdari' => 'EDIT BANK',
            'idtrans' => $bank->id,
            'nobuktitrans' => $bank->id,
            'aksi' => 'EDIT',
            'datajson' => $bank->toArray(),
            'modifiedby' => $bank->modifiedby
        ]);

        return $bank;
    }

    public function processDestroy($id): Bank
    {
        $bank = new Bank();
        $bank = $bank->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($bank->getTable()),
            'postingdari' => 'DELETE BANK',
            'idtrans' => $bank->id,
            'nobuktitrans' => $bank->id,
            'aksi' => 'DELETE',
            'datajson' => $bank->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $bank;
    }
    
    public function editingAt($id, $btn)
    {
        $bank = Bank::find($id);
        if ($btn == 'EDIT') {
            $bank->editingby = auth('api')->user()->name;
            $bank->editingat = date('Y-m-d H:i:s');
        } else {

            if ($bank->editingby == auth('api')->user()->name) {
                $bank->editingby = '';
                $bank->editingat = null;
            }
        }
        if (!$bank->save()) {
            throw new \Exception("Error Update bank.");
        }

        return $bank;
    }
}
