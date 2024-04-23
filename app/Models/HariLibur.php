<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HariLibur extends MyModel
{
    use HasFactory;

    protected $table = 'harilibur';

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

        $getJudul = DB::table('parameter')->from(DB::raw("parameter"))
            ->select('text')
            ->where('grp', 'JUDULAN LAPORAN')
            ->where('subgrp', 'JUDULAN LAPORAN')
            ->first();

        $aktif = request()->aktif ?? '';
        $load = request()->load ?? '';

        $query = DB::table($this->table)
            ->select(
                "$this->table.id",
                "$this->table.tgl",
                "$this->table.keterangan",
                "parameter.memo as status",
                "$this->table.modifiedby",
                "$this->table.created_at",
                "$this->table.updated_at",
                // DB::raw("'Laporan Hari Libur' as judulLaporan"),
                // DB::raw("'" . $getJudul->text . "' as judul"),
                DB::raw("CONCAT('User :', '" . auth('api')->user()->name . "') as usercetak")
            )->leftJoin('parameter', 'harilibur.status', '=', 'parameter.id');

        $this->filter($query);
        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('harilibur.status', '=', $status->id);
        }

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        if ($load == '') {
            $this->sort($query);
            $this->paginate($query);
        }

        $data = $query->get();
        return $data;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status INT NULL
        )");

        $status = DB::table("parameter")
            ->select('id')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();
        DB::statement("INSERT INTO $tempdefault (status) VALUES (?)", [$status->id]);

        $query = DB::table(
            $tempdefault
        )
            ->select(
                'status'
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
            $this->table.tgl,
            $this->table.keterangan,
            parameter.text as status,
            $this->table.modifiedby,
            $this->table.created_at,
            $this->table.updated_at
            ")
            )->leftJoin(DB::raw("parameter"), 'harilibur.status', 'parameter.id');
    }

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->filter($query);
        $query = $this->sort($query);
        $filteredQuery = $query->toSql();
        // dd($filteredQuery);

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $temp (
                id BIGINT NULL,
                tgl DATE NULL,
                keterangan VARCHAR(1000) NULL,
                status VARCHAR(1000) NULL,
                modifiedby VARCHAR(255) NOT NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");
        DB::statement("
            INSERT INTO $temp (id, tgl, keterangan, status, modifiedby, created_at, updated_at)
            $filteredQuery
        ");
        
        return $temp;
    }

    public function sort($query)
    {
        return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'status') {
                            $query = $query->where('parameter.text', '=', "$filters[data]");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl' ) {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else{
                            // $query = $query->where($this->table . '.' . $filters['field'], 'LIKE', "%$filters[data]%");
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'status') {
                                $query = $query->orWhere('parameter.text', '=', "$filters[data]");
                            } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else {
                                // $query = $query->orWhere($this->table . '.' . $filters['field'], 'LIKE', "%$filters[data]%");
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

    public function processStore(array $data): HariLibur
    {

        $hariLibur = new HariLibur();
        $hariLibur->tgl = date('Y-m-d', strtotime($data['tgl']));
        $hariLibur->keterangan = $data['keterangan'] ?? '';
        $hariLibur->status = $data['status'];
        $hariLibur->modifiedby = auth('api')->user()->name;


        if (!$hariLibur->save()) {
            throw new \Exception('Error storing hari libur.');
        }
        // dd($hariLibur);
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($hariLibur->getTable()),
            'postingdari' => 'ENTRY HARI LIBUR',
            'idtrans' => $hariLibur->id,
            'nobuktitrans' => $hariLibur->id,
            'aksi' => 'ENTRY',
            'datajson' => $hariLibur->toArray(),
            'modifiedby' => $hariLibur->modifiedby
        ]);

        return $hariLibur;
    }

    public function processUpdate(HariLibur $harilibur, array $data): HariLibur
    {
        $harilibur->tgl = date('Y-m-d', strtotime($data['tgl']));
        $harilibur->keterangan = $data['keterangan'] ?? '';
        $harilibur->status = $data['status'];
        $harilibur->modifiedby = auth('api')->user()->user;

        if (!$harilibur->save()) {
            throw new \Exception('Error updating hari libur.');
        }

        (new LogTrail())->processStore([
            'namatabel' => $harilibur->getTable(),
            'postingdari' => 'EDIT HARI LIBUR',
            'idtrans' => $harilibur->id,
            'nobuktitrans' => $harilibur->id,
            'aksi' => 'EDIT',
            'datajson' => $harilibur->toArray(),
            'modifiedby' => $harilibur->modifiedby
        ]);

        return $harilibur;
    }

    public function processDestroy($id): HariLibur
    {
        $harilibur = new harilibur();
        $harilibur = $harilibur->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($harilibur->getTable()),
            'postingdari' => 'DELETE HARI LIBUR',
            'idtrans' => $harilibur->id,
            'nobuktitrans' => $harilibur->id,
            'aksi' => 'DELETE',
            'datajson' => $harilibur->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $harilibur;
    }
}
