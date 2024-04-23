<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Parameter extends MyModel
{
    use HasFactory;

    protected $table = 'parameter';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function getdefaultparameter($data)
    {
        $grp = $data['grp'] ?? '';

        $subgrp = $data['subgrp'] ?? '';


        $query = DB::table('parameter')
            ->from(
                DB::raw("parameter")
            )
            ->select(
                'id'
            )
            ->Where('grp', '=', $grp)
            ->Where('subgrp', '=', $subgrp)
            ->Where('default', '=', 'YA')
            ->first();

        if (isset($query)) {
            $data = $query->id;
        } else {
            $data = 0;
        }

        return $data;
    }

    public function get()
    {

        // $grp = request()->grp ?? '';

        
        $this->setRequestParameters();

        $query = DB::table('parameter')
            ->select(
                'parameter.id',
                'parameter.grp',
                'parameter.subgrp',
                'parameter.kelompok',
                'parameter.text',
                'parameter.memo',
                'parameter.default',
                'parameter.modifiedby',
                'parameter.created_at',
                'parameter.updated_at',
              
            )
            ->leftJoin(DB::raw("parameter as B"), 'parameter.type', 'B.id');


        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->filter($query);

        // if ($grp) {
        //     $query->where('parameter.grp', '=', $grp);
           
        // }



        // if (request()->offset) {
        //     $query->orderBy('parameter.text', 'asc');

        //     $perPage = 20;

        //     $total_count = $query->count();
        //     $total_pages = ceil($total_count / $perPage);

        //     $data = $query->offset(($page - 1) * $perPage)
        //             ->limit($perPage)
        //             ->get(); 

        // }else{
            $this->sort($query);
            $this->paginate($query);
        // }

        $this->sort($query);
        
        // if (request()->limit > 0) {
        //     $this->paginate($query);
        // }

        $data = $query->get();

        return $data;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            `default` BIGINT UNSIGNED NULL
        )");
        $status = DB::table('parameter')
            ->select('id')
            ->where('grp', '=', 'STATUS DEFAULT PARAMETER')
            ->where('subgrp', '=', 'STATUS DEFAULT PARAMETER')
            ->where('default', '=', 'YA')
            ->first();

        $iddefault = $status->id ?? 0;
        DB::statement("INSERT INTO $tempdefault (`default`) VALUES ($iddefault)");
        $data = DB::table($tempdefault)
            ->select('default')
            ->first();
        return $data;
    }

    public function getcoa($filter)
    {
        $getcoa = Parameter::from(DB::raw("parameter"))
            ->select('memo')->where('kelompok', $filter)->get();
        $jurnal = [];
        foreach ($getcoa as $key => $coa) {
            $a = 0;
            $memo = json_decode($coa->memo, true);

            $ketcoa = AkunPusat::from(DB::raw("akunpusat"))
                ->select('keterangancoa')->where('coa', $memo['JURNAL'])->first();
            $jurnal[] = [
                'coa' => $memo['JURNAL'],
                'keterangancoa' => $ketcoa->keterangancoa
            ];
        }

        return $jurnal;
    }

    public function findAll($id)
    {
        $query = DB::table('parameter as A')->from(
            DB::raw("parameter as A")
        )
        ->select('A.id', 'A.grp', 'A.subgrp', 'A.kelompok', 'A.text', 'A.memo', 'A.default', 'A.type','B.text as type_name', 'B.grp as grup')
            ->leftJoin(DB::raw("parameter as B"), 'A.type', 'B.id')
            ->where('A.id', $id);

        $data = $query->first();
        return $data;
    }

    public function selectColumns($query)
    {
        return $query->from(
            DB::raw($this->table . "")
        )
            ->select(
                "$this->table.id",
                "$this->table.grp",
                "$this->table.subgrp",
                "$this->table.text",
                "$this->table.memo",
                "$this->table.kelompok",
                "$this->table.default",
                DB::raw("case when parameter.type = 0 then '' else B.grp end as type"),
                "$this->table.created_at",
                "$this->table.updated_at",
                "$this->table.modifiedby"
            )->leftJoin(DB::raw("parameter as B"), 'parameter.type', 'B.id');
    }

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->filter($query);
        $query = $this->sort($query);
        $filteredQuery = $query->toSql();

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $temp (
            id BIGINT NULL,
            grp VARCHAR(255) NULL,
            subgrp VARCHAR(255) NULL,
            text VARCHAR(255) NULL,
            memo LONGTEXT NULL,
            kelompok VARCHAR(1000) NULL,
            `default` VARCHAR(255) NULL, 
            type VARCHAR(255) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            modifiedby VARCHAR(50) NULL,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");
        
        DB::statement(" INSERT INTO $temp (id, grp, subgrp, text, memo, kelompok, `default`, type, modifiedby, created_at, updated_at)
            $filteredQuery
        ");

        return $temp;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'grp') {
            return $query
                ->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder'])
                ->orderBy($this->table . '.subgrp', $this->params['sortOrder'])
                ->orderBy($this->table . '.id', $this->params['sortOrder']);
        }

        if ($this->params['sortIndex'] == 'subgrp') {
            return $query
                ->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder'])
                ->orderBy($this->table . '.grp', $this->params['sortOrder'])
                ->orderBy($this->table . '.id', $this->params['sortOrder']);
        }

        return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'type') {
                            $query = $query->where('B.grp', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'type') {
                            $query = $query->orWhere('B.grp', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
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

    public function getcombodata($grp, $subgrp)
    {
        $query = DB::table('parameter')
            ->from(
                DB::raw("parameter")
            )
            ->select(
                'id',
                'text'
            )
            ->Where('grp', '=', $grp)
            ->Where('subgrp', '=', $subgrp)
            ->get();
        return $query;
    }

    // public function getBatasAwalTahun(){
    //     $query = DB::table('parameter')->from(DB::raw("parameter"))
    //     ->select('text')
    //     ->where('grp', 'BATAS AWAL TAHUN')
    //     ->where('subgrp', 'BATAS AWAL TAHUN')
    //     ->first();

    //     return $query;
    // }

    // public function getTutupBuku(){
    //     $query = DB::table('parameter')->from(DB::raw("parameter"))
    //     ->select('text')
    //     ->where('grp', 'TUTUP BUKU')
    //     ->where('subgrp', 'TUTUP BUKU')
    //     ->first();

    //     return $query;
    // }

    public function combo(){

        $this->setRequestParameters();

        $query = DB::table('parameter')
        ->select('*')
        ->where('grp', '=', request()->grp)
        ->where('subgrp', '=', request()->subgrp);

        $this->filter($query);

        $query = $query->get();

       

        return $query;
    }

    public function getComboByGroup($grp)
    {
        $query = DB::table('parameter')
            ->from(
                DB::raw("parameter")
            )
            ->select(
                'id'
            )
            ->Where('grp', '=', $grp)
            ->get();

        return $query;
    }

    // public function getComboByGroupAndText($grp, $text) 
    // {
    //     $query=DB::table('parameter')
    //         ->from (
    //             DB::raw("parameter")
    //         )
    //         ->select (
    //             'id'
    //         )
    //         ->Where('grp','=',$grp)
    //         ->Where('text','=',$text)
    //         ->first();

    //         return $query;
    // }

    public function processStore(array $data): Parameter
    {
        
        $parameter = new Parameter();
        $parameter->grp = $data['grp'];
        $parameter->subgrp = $data['subgrp'];
        $parameter->text = $data['text'];
        $parameter->kelompok = $data['kelompok'] ?? '';
        $parameter->default = $data['default'] ?? '';
        $parameter->type = $data['type'] ?? 0;
        $parameter->modifiedby = auth('api')->user()->user;

        $detailmemo = [];
        for ($i = 0; $i < count($data['key']); $i++) {
            $value = ($data['value'][$i] != null) ? $data['value'][$i] : "";
            $datadetailmemo = [
                $data['key'][$i] => $value,
            ];
            $detailmemo = array_merge($detailmemo, $datadetailmemo);
        }

        $parameter->memo = json_encode($detailmemo);
        if (!$parameter->save()) {
            throw new \Exception('Error storing parameter.');
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($parameter->getTable()),
            'postingdari' => 'ENTRY PARAMETER',
            'idtrans' => $parameter->id,
            'nobuktitrans' => $parameter->id,
            'aksi' => 'ENTRY',
            'datajson' => $parameter->toArray(),
            'modifiedby' => $parameter->modifiedby
        ]);

        // dd($parameter);
        return $parameter;
    }

    public function processUpdate(Parameter $parameter, array $data): Parameter
    {
        $parameter->grp = $data['grp'];
        $parameter->subgrp = $data['subgrp'];
        $parameter->text = $data['text'];
        $parameter->kelompok = $data['kelompok'] ?? '';
        $parameter->default = $data['default'] ?? '';
        $parameter->type =  $data['type'] ?? 0;
        $parameter->modifiedby = auth('api')->user()->user;

        $detailmemo = [];
        for ($i = 0; $i < count($data['key']); $i++) {
            $value = ($data['value'][$i] != null) ? $data['value'][$i] : "";
            $datadetailmemo = [
                $data['key'][$i] => $value,
            ];
            $detailmemo = array_merge($detailmemo, $datadetailmemo);
        }
        $parameter->memo = json_encode($detailmemo);
        if (!$parameter->save()) {
            throw new \Exception('Error storing parameter.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($parameter->getTable()),
            'postingdari' => 'EDIT PARAMETER',
            'idtrans' => $parameter->id,
            'nobuktitrans' => $parameter->id,
            'aksi' => 'EDIT',
            'datajson' => $parameter->toArray(),
            'modifiedby' => $parameter->modifiedby
        ]);

        return $parameter;
    }

    public function processDestroy($id): Parameter
    {
        $parameter = new Parameter();
        $parameter = $parameter->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($parameter->getTable()),
            'postingdari' => 'DELETE PARAMETER',
            'idtrans' => $parameter->id,
            'nobuktitrans' => $parameter->id,
            'aksi' => 'DELETE',
            'datajson' => $parameter->toArray(),
            'modifiedby' => $parameter->modifiedby
        ]);

        return $parameter;
    }

    public function getBatasAwalTahun(){
        $query = DB::table('parameter')->from(DB::raw("parameter"))
        ->select('text')
        ->where('grp', 'BATAS AWAL TAHUN')
        ->where('subgrp', 'BATAS AWAL TAHUN')
        ->first();

        return $query;
    }
}
