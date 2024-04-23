<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stevebauman\Location\Facades\Location;

class Error extends MyModel
{
    use HasFactory;

    
    protected $table = 'error';

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

        $query = DB::table($this->table)->from(
            DB::raw($this->table)
        )
            ->select(
                'id',
                'kodeerror',
                'keterangan',
                'modifiedby',
                'created_at',
                'updated_at',
                DB::raw("'Laporan Error' as judulLaporan"),
                DB::raw(" 'User :" . auth('api')->user()->name . "' as usercetak")
            );

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        // $this->selectColumns($query);
        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);

        $data = $query->get();

        return $data;
    }

    public function selectColumns($query)
    {
        return $query->from(
            DB::raw($this->table)
        )->select(
            DB::raw(
                "$this->table.id,
                $this->table.kodeerror,
                $this->table.keterangan,
                $this->table.modifiedby,
                $this->table.created_at,
                $this->table.updated_at",
            )
        );
    }

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->filter($query);
        $query = $this->sort($query);
        $filteredQuery = $query->toSql();

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement(" CREATE TEMPORARY TABLE $temp (
                id BIGINT NULL,
                kodeerror VARCHAR(50) NULL,
                keterangan VARCHAR(1000) NULL,
                modifiedby VARCHAR(50) NULL,
                created_at DATETIME NULL,                                   
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");

        DB::statement("
            INSERT INTO $temp (id, kodeerror, keterangan, modifiedby, created_at, updated_at) 
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
                        if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
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

    public function processStore(array $data): Error
    {
        $error = new Error();
        $error->kodeerror = $data['kodeerror'];
        $error->keterangan = $data['keterangan'];
        $error->modifiedby = auth('api')->user()->user;

        if (!$error->save()) {
            throw new \Exception('Error storing error.');
        }
        // $ip = request()->ip();
        // $location = Location::get($ip);
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($error->getTable()),
            'postingdari' => 'ENTRY ERROR',
            'idtrans' => $error->id,
            'nobuktitrans' => $error->id,
            'aksi' => 'ENTRY',
            'datajson' => $error->toArray(),
            'modifiedby' => $error->modifiedby
        ]);

        return $error;
    }

    public function processUpdate(Error $error, array $data): Error
    {
        $error->kodeerror = $data['kodeerror'];
        $error->keterangan = $data['keterangan'];
        $error->modifiedby = auth('api')->user()->user;

        if (!$error->save()) {
            throw new \Exception('Error updating cabang.');
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($error->getTable()),
            'postingdari' => 'EDIT ERROR',
            'idtrans' => $error->id,
            'nobuktitrans' => $error->id,
            'aksi' => 'EDIT',
            'datajson' => $error->toArray(),
            'modifiedby' => $error->modifiedby
        ]);

        return $error;
    }

    public function processDestroy($id): Error
    {
        $error = new Error();
        $error = $error->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($error->getTable()),
            'postingdari' => 'DELETE ERROR',
            'idtrans' => $error->id,
            'nobuktitrans' => $error->id,
            'aksi' => 'DELETE',
            'datajson' => $error->toArray(),
            'modifiedby' => $error->modifiedby
        ]);

        return $error;
    }
}
