<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class Aco extends MyModel
{
    use HasFactory;

    protected $table = 'acos';

    public function get()
    {

      
        $this->setRequestParameters();
        $proses = request()->proses ?? 'reload';
        $load = request()->load ?? '';
        $user = auth('api')->user()->name;
        $class = 'AcoController';
        if ($proses == 'reload') {
            $temtabel = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true)) . request()->nd ?? 0;
            $querydata = DB::table('listtemporarytabel')->from(
                DB::raw("listtemporarytabel a")
            )
                ->select(
                    'id',
                    'class',
                    'namatabel',
                )
                ->where('class', '=', $class)
                ->where('modifiedby', '=', $user)
                ->first();
            if (isset($querydata)) {
                Schema::dropIfExists($querydata->namatabel);
                DB::table('listtemporarytabel')->where('id', $querydata->id)->delete();
            }
            DB::table('listtemporarytabel')->insert(
                [
                    'class' => $class,
                    'namatabel' => $temtabel,
                    'modifiedby' => $user,
                    'created_at' => date('Y/m/d H:i:s'),
                    'updated_at' => date('Y/m/d H:i:s'),
                ]
            );
            DB::statement("
                CREATE TABLE $temtabel (
                    id INT NULL,
                    class VARCHAR(1000) NULL,
                    method VARCHAR(1000) NULL,
                    nama VARCHAR(1000) NULL,
                    modifiedby VARCHAR(100) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            ");
            DB::statement(
                " INSERT INTO $temtabel 
                (id, class, method, nama, modifiedby, created_at, updated_at)
                " . $this->getdata()
            );

          
        } else {
         
           
            $querydata = DB::table('listtemporarytabel')->from(
                DB::raw("listtemporarytabel")
            )
                ->select(
                    'namatabel',
                )
                ->where('class', '=', $class)
                ->where('modifiedby', '=', $user)
                ->first();
            $temtabel = $querydata->namatabel;
        }
        $query = db::table($temtabel)->from(
            db::raw($temtabel . " a")
        )
            ->select(
                'a.id',
                'a.class',
                'a.method',
                'a.nama',
                'a.modifiedby',
                'a.created_at',
                'a.updated_at',
            );
        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        // $this->selectColumns($query);
        if ($load == '') {
            $this->sort($query);
            $this->filter($query);
            $this->paginate($query);
        }
       
        $data = $query->get();
        return $data;
    }

    public function getdata()
    {
        $this->tempmenu = 'tempmenu' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $this->tempmenu (
            aco_id INT NULL,
            menu VARCHAR(1000) NULL
        )");
        $param1 = 0;
        $querymenu = "
            SELECT
                a.aco_id,
                CONCAT_WS(
                    '->', 
                    NULLIF(b.menuname, ''), 
                    NULLIF(c.menuname, ''), 
                    NULLIF(d.menuname, ''), 
                    NULLIF(e.menuname, ''), 
                    NULLIF(f.menuname, ''), 
                    NULLIF(g.menuname, ''), 
                    NULLIF(a.menuname, '')
                ) as menu
            FROM menu as a
            LEFT JOIN menu b ON SUBSTRING(a.menukode, 1, 1) = b.menukode AND IFNULL(b.aco_id, 0) = $param1
            LEFT JOIN menu c ON SUBSTRING(a.menukode, 1, 2) = c.menukode AND IFNULL(c.aco_id, 0) = $param1
            LEFT JOIN menu d ON SUBSTRING(a.menukode, 1, 3) = d.menukode AND IFNULL(d.aco_id, 0) = $param1
            LEFT JOIN menu e ON SUBSTRING(a.menukode, 1, 4) = e.menukode AND IFNULL(e.aco_id, 0) = $param1
            LEFT JOIN menu f ON SUBSTRING(a.menukode, 1, 5) = f.menukode AND IFNULL(f.aco_id, 0) = $param1
            LEFT JOIN menu g ON SUBSTRING(a.menukode, 1, 6) = g.menukode AND IFNULL(g.aco_id, 0) = $param1
            WHERE a.aco_id <> 0
        ";
        DB::statement("
            INSERT INTO $this->tempmenu (aco_id, menu)
            $querymenu
        ");

        $this->tempacos = 'tempacos' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $this->tempacos (
            id INT NULL,
            idindex INT NULL
        )");
        $param1 = 'index';
        $queryacos = "
            SELECT
                a.id,
                b.id as idindex
            FROM acos a
            LEFT JOIN acos b ON a.class = b.class AND b.method = '$param1'
        ";
        DB::statement("
            INSERT INTO $this->tempacos (id, idindex)
            $queryacos
        ");

        $this->tempacos2 = 'tempacos2' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $this->tempacos2 (
            id INT NULL,
            class VARCHAR(1000) NULL,
            method VARCHAR(1000) NULL,
            nama VARCHAR(1000) NULL,
            modifiedby VARCHAR(50) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )");
        $queryacos2 = "
            SELECT
                a.id,
                COALESCE(c.menu) as class,
                a.method,
                a.nama,
                a.modifiedby,
                a.created_at,
                a.updated_at
            FROM acos a
            LEFT JOIN $this->tempacos b ON a.id = b.id
            LEFT JOIN $this->tempmenu c ON b.idindex = c.aco_id
            WHERE COALESCE(c.menu) <> ''
        ";
        DB::statement("
            INSERT INTO $this->tempacos2 (id, class, method, nama, modifiedby, created_at, updated_at) 
            $queryacos2
        ");
        $queryacos2 = "
            SELECT
                a.id,
                COALESCE(COALESCE(c1.menu, '')) as class,
                a.method,
                a.nama,
                a.modifiedby,
                a.created_at,
                a.updated_at
            FROM acos a
            LEFT JOIN $this->tempacos b1 ON a.idheader = b1.id
            LEFT JOIN $this->tempmenu c1 ON b1.idindex = c1.aco_id
            WHERE COALESCE(COALESCE(c1.menu, '')) <> ''
        ";
        DB::statement("
            INSERT INTO $this->tempacos2 (id, class, method, nama, modifiedby, created_at, updated_at) 
            $queryacos2
        ");

        $query = "
            SELECT
                a.id,
                a.class,
                COALESCE(b.keterangan, a.method) as method,
                a.nama,
                a.modifiedby,
                a.created_at,
                a.updated_at
            FROM $this->tempacos2 a
            LEFT JOIN method b ON a.method COLLATE utf8mb4_unicode_ci = b.method COLLATE utf8mb4_unicode_ci
            ORDER BY a.id ASC
        ";

        return $query;
    }

    public function selectColumns($query)
    {
        return $query->select(
            "$this->table.id",
            "$this->table.class",
            "$this->table.method",
            "$this->table.nama",
            "$this->table.modifiedby",
            "$this->table.created_at",
            "$this->table.updated_at",
        );
    }
    public function sort($query)
    {
        return $query->orderBy('a.' . $this->params['sortIndex'], $this->params['sortOrder']);
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field']) {
                            $query = $query->where('a.' . $filters['field'], 'LIKE', "%$filters[data]%");
                        }
                    }
                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        $query = $query->orWhere('a.' . $filters['field'], 'LIKE', "%$filters[data]%");
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
        // dd('test');
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }
}
