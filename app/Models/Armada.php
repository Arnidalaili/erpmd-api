<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Armada extends MyModel
{
    use HasFactory;

    protected $table = 'armada';

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
                'armada.id',
                'armada.nama',
                'armada.jenisarmada',
                'armada.nopolisi',
                'armada.namapemilik',
                'armada.nostnk',
                'armada.keterangan',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'jenisarmada.id as jenisarmada',
                'jenisarmada.text as jenisarmadanama',
                'jenisarmada.memo as jenisarmadamemo',
                'armada.modifiedby',
                'armada.created_at',
                'armada.updated_at',
                // DB::raw("'Laporan Jenis EMKL' as judulLaporan"),
                // DB::raw("'" . $getJudul->text . "' as judul"),
                // DB::raw("'Tgl Cetak :'+format(getdate(),'dd-MM-yyyy HH:mm:ss')as tglcetak"),
                // DB::raw(" 'User :".auth('api')->user()->name."' as usercetak")
            )
            ->leftJoin(DB::raw("parameter"), 'armada.status', '=', 'parameter.id')
            ->leftJoin('parameter as jenisarmada', 'armada.jenisarmada', '=', 'jenisarmada.id');

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('armada.status', '=', $status->id);
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

            $query = DB::table('armada')
                ->select(
                    'armada.id',
                    'armada.nama',
                    'armada.jenisarmada',
                    'armada.nopolisi',
                    'armada.namapemilik',
                    'armada.nostnk',
                    'armada.keterangan',
                    'parameter.id as status',
                    'parameter.text as statusnama',
                    'parameter.memo as statusmemo',
                    'jenisarmada.id as jenisarmada',
                    'jenisarmada.text as jenisarmadanama',
                    'armada.modifiedby',
                    'armada.created_at',
                    'armada.updated_at',
                )
                ->leftJoin(DB::raw("parameter"), 'armada.status', '=', 'parameter.id')
                ->leftJoin('parameter as jenisarmada', 'armada.jenisarmada', '=', 'jenisarmada.id')
                ->where('armada.id', $id);
            $data = $query->first();


            return $data;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status INT NULL,
            statusnama VARCHAR(100),
            jenisarmada INT NULL,
            jenisarmadanama VARCHAR(100)
        )");

        $status = DB::table("parameter")
            ->select('id','text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        $jenisarmada = DB::table('parameter')
            ->select('id', 'text')
            ->where('grp', '=', 'JENIS ARMADA')
            ->where('subgrp', '=', 'JENIS ARMADA')
            ->where('default', '=', 'YA')
            ->first();
        // dd($jenisarmada->text);

        DB::statement("INSERT INTO $tempdefault (status,statusnama,jenisarmada,jenisarmadanama) VALUES (?,?,?,?)", [ $status->id, $status->text, $jenisarmada->id, $jenisarmada->text]);

        $query = DB::table($tempdefault)
            ->select(
                'status',
                'statusnama',
                'jenisarmada',
                'jenisarmadanama'
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
                $this->table.nopolisi,
                $this->table.namapemilik,
                $this->table.nostnk,
                $this->table.keterangan,
                parameter.id as status,
                parameter.memo as statusmemo,
                jenisarmada.id as jenisarmada,
                jenisarmada.text as jenisarmadanama,
                jenisarmada.memo as jenisarmadamemo,
                $this->table.modifiedby,
                $this->table.created_at,
                $this->table.updated_at
            ")
            )
            ->leftJoin(DB::raw("parameter"), 'armada.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("parameter as jenisarmada"), 'armada.jenisarmada', '=', 'jenisarmada.id');
            // ->leftJoin('parameter as jenisarmada', 'armada.jenisarmada', '=', 'jenisarmada.id');
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
                nopolisi VARCHAR(100) NULL,
                namapemilik VARCHAR(100) NULL,
                nostnk VARCHAR(100) NULL,
                keterangan VARCHAR(255) NULL,
                status VARCHAR(100) NULL,
                statusmemo VARCHAR(100) NULL,
                jenisarmada VARCHAR(100) NULL,
                jenisarmadanama VARCHAR(100) NULL,
                jenisarmadamemo VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");

        DB::table($temp)->insertUsing(["id", "nama", "nopolisi", "namapemilik","nostnk","keterangan","status","statusmemo","jenisarmada", "jenisarmadanama","jenisarmadamemo", "modifiedby", "created_at", "updated_at"],$query);
        

        return $temp;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        }else if($this->params['sortIndex'] == 'jenisarmadamemo'){
            return $query->orderBy('jenisarmada.memo', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'jenisarmadamemo') {
                            $query = $query->where('jenisarmada.text', '=', $filters['data']);
                        }else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl') {
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
                            if ($filters['field'] == 'jenisarmadamemo') {
                                $query = $query->orWhere('jenisarmada.memo', '=', $filters['data']);
                            } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else if($filters['field'] == 'statusmemo'){
                                $query = $query->where('parameter.text', 'like', '%$filters[data]%');
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

    public function processStore(array $data): Armada
    {
        $armada = new Armada();
        $armada->nama = $data['nama'] ?? '';
        $armada->jenisarmada = $data['jenisarmada'] ?? '';
        $armada->nopolisi = $data['nopolisi'] ?? '';
        $armada->namapemilik = $data['namapemilik'] ?? '';
        $armada->nostnk = $data['nostnk'] ?? '';
        $armada->keterangan = $data['keterangan'] ?? '';
        $armada->status = $data['status'];
        $armada->modifiedby = auth('api')->user()->name;

        if (!$armada->save()) {
            throw new \Exception('Error storing Armada.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($armada->getTable()),
            'postingdari' => 'ENTRY ARMADA',
            'idtrans' => $armada->id,
            'nobuktitrans' => $armada->id,
            'aksi' => 'ENTRY',
            'datajson' => $armada->toArray(),
            'modifiedby' => $armada->modifiedby
        ]);

        return $armada;
    }

    public function processUpdate(Armada $armada, array $data): Armada
    {
        $armada->nama = $data['nama'] ?? '';
        $armada->jenisarmada = $data['jenisarmada'] ?? '';
        $armada->nopolisi = $data['nopolisi'] ?? '';
        $armada->namapemilik = $data['namapemilik'] ?? '';
        $armada->nostnk = $data['nostnk'] ?? '';
        $armada->keterangan = $data['keterangan'] ?? '';
        $armada->status = $data['status'];
        $armada->modifiedby = auth('api')->user()->name;

        if (!$armada->save()) {
            throw new \Exception('Error updating Armada');
        }

        (new LogTrail())->processStore([
            'namatabel' => $armada->getTable(),
            'postingdari' => 'EDIT ARMADA',
            'idtrans' => $armada->id,
            'nobuktitrans' => $armada->id,
            'aksi' => 'EDIT',
            'datajson' => $armada->toArray(),
            'modifiedby' => $armada->modifiedby
        ]);

        return $armada;
    }

    public function processDestroy($id): Armada
    {
        $armada = new Armada();
        $armada = $armada->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($armada->getTable()),
            'postingdari' => 'DELETE ARMADA',
            'idtrans' => $armada->id,
            'nobuktitrans' => $armada->id,
            'aksi' => 'DELETE',
            'datajson' => $armada->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $armada;
    }

    public function editingAt($id, $btn)
    {
       
        $armada = Armada::find($id);
        if ($btn == 'EDIT') {
            $armada->editingby = auth('api')->user()->name;
            $armada->editingat = date('Y-m-d H:i:s');
        } else {

            if ($armada->editingby == auth('api')->user()->name) {
                $armada->editingby = '';
                $armada->editingat = null;
            }
        }
        if (!$armada->save()) {
            throw new \Exception("Error Update armada.");
        }

        return $armada;
    }

}
