<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AlatBayar extends MyModel
{
    use HasFactory;

    protected $table = 'alatbayar';

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
                'alatbayar.id',
                'alatbayar.nama',
                'alatbayar.keterangan',
                'bank.id as bankid',
                'bank.nama as banknama',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'alatbayar.modifiedby',
                'alatbayar.created_at',
                'alatbayar.updated_at',
                // DB::raw("'Laporan Jenis EMKL' as judulLaporan"),
                // DB::raw("'" . $getJudul->text . "' as judul"),
                // DB::raw("'Tgl Cetak :'+format(getdate(),'dd-MM-yyyy HH:mm:ss')as tglcetak"),
                // DB::raw(" 'User :".auth('api')->user()->name."' as usercetak")
            )
            ->leftJoin(DB::raw("parameter"), 'alatbayar.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("bank"), 'alatbayar.bankid', '=', 'bank.id');

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('alatbayar.status', '=', $status->id);
        }
        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);
        $this->paginate($query);

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

    public function findAll($id)
    {
        $query = DB::table('alatbayar')
            ->select(
                'alatbayar.id',
                'alatbayar.nama',
                'alatbayar.keterangan',
                'bank.id as bankid',
                'bank.nama as banknama',
                'parameter.id as status',
                'parameter.text as statusnama',
                'alatbayar.modifiedby',
                'alatbayar.created_at',
                'alatbayar.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'alatbayar.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("bank"), 'alatbayar.bankid', '=', 'bank.id')
            ->where('alatbayar.id', $id);
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
            bank.id as bankid,
            bank.nama as banknama,
            parameter.id as status,
            parameter.text as statusnama,
            $this->table.modifiedby,
            $this->table.created_at,
            $this->table.updated_at
            ")
        ) 
        ->leftJoin(DB::raw("parameter"), 'alatbayar.status', '=', 'parameter.id')
        ->leftJoin(DB::raw("bank"), 'alatbayar.bankid', '=', 'bank.id');
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
                bankid VARCHAR(100) NULL,
                banknama VARCHAR(100) NULL,
                status VARCHAR(100) NULL,
                statusnama VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");
       
        DB::table($temp)->insertUsing(["id", "nama", "keterangan", "bankid","banknama", "status","statusnama", "modifiedby", "created_at", "updated_at"],$query);
        
        return $temp;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'banknama') {
            return $query->orderBy('bank.nama', $this->params['sortOrder']);
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
                        } else if ($filters['field'] == 'banknama') {
                            $query = $query->where('bank.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl' ) {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else{
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'statusmemo') {
                                $query = $query->orWhere('parameter.text', '=', "$filters[data]");
                            } else if ($filters['field'] == 'banknama') {
                                $query = $query->where('bank.nama', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
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

    public function processStore(array $data): AlatBayar
    {
        $alatBayar = new AlatBayar();
        $alatBayar->nama = $data['nama'] ?? '';
        $alatBayar->keterangan = $data['keterangan'] ?? '';
        $alatBayar->bankid = $data['bankid'];
        $alatBayar->status = $data['status'];
        $alatBayar->modifiedby = auth('api')->user()->name;

        if (!$alatBayar->save()) {
            throw new \Exception('Error storing Alat Bayar.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($alatBayar->getTable()),
            'postingdari' => 'ENTRY ALAT BAYAR',
            'idtrans' => $alatBayar->id,
            'nobuktitrans' => $alatBayar->id,
            'aksi' => 'ENTRY',
            'datajson' => $alatBayar->toArray(),
            'modifiedby' => $alatBayar->modifiedby
        ]);

        return $alatBayar;
    }

    public function processUpdate(AlatBayar $alatBayar, array $data): AlatBayar
    {
        $alatBayar->nama = $data['nama'] ?? '';
        $alatBayar->keterangan = $data['keterangan'] ?? '';
        $alatBayar->bankid = $data['bankid'];
        $alatBayar->status = $data['status'];
        $alatBayar->modifiedby = auth('api')->user()->user;

        if (!$alatBayar->save()) {
            throw new \Exception('Error updating Alat Bayar');
        }

        (new LogTrail())->processStore([
            'namatabel' => $alatBayar->getTable(),
            'postingdari' => 'EDIT ALAT BAYAR',
            'idtrans' => $alatBayar->id,
            'nobuktitrans' => $alatBayar->id,
            'aksi' => 'EDIT',
            'datajson' => $alatBayar->toArray(),
            'modifiedby' => $alatBayar->modifiedby
        ]);

        return $alatBayar;
    }

    public function processDestroy($id): AlatBayar
    {
        $alatBayar = new AlatBayar();
        $alatBayar = $alatBayar->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($alatBayar->getTable()),
            'postingdari' => 'DELETE ALAT BAYAR',
            'idtrans' => $alatBayar->id,
            'nobuktitrans' => $alatBayar->id,
            'aksi' => 'DELETE',
            'datajson' => $alatBayar->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $alatBayar;
    }

    public function editingAt($id, $btn)
    {
        $alatBayar = AlatBayar::find($id);
        if ($btn == 'EDIT') {
            $alatBayar->editingby = auth('api')->user()->name;
            $alatBayar->editingat = date('Y-m-d H:i:s');
        } else {

            if ($alatBayar->editingby == auth('api')->user()->name) {
                $alatBayar->editingby = '';
                $alatBayar->editingat = null;
            }
        }
        if (!$alatBayar->save()) {
            throw new \Exception("Error Update alat bayar.");
        }

        return $alatBayar;
    }
}
