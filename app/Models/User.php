<?php

namespace App\Models;

use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;




class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d-m-Y H:i:s');
    }

    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $value);

        if (is_string($value) && $key !== 'password') {
            return $this->attributes[$key] = strtoupper($value);
        }
    }

    public function setRequestParameters()
    {
        $this->params = [
            'offset' => request()->offset ?? ((request()->page - 1) * request()->limit),
            'limit' => request()->limit ?? 10,
            'filters' => json_decode(request()->filters, true) ?? [],
            'sortIndex' => request()->sortIndex ?? 'id',
            'sortOrder' => request()->sortOrder ?? 'asc',
        ];
    }

    public function findForPassport($username)
    {
        return $this->where('username', $username)->first();
    }

    public function get()
    {
        $this->setRequestParameters();
        $query = DB::table($this->table)
            ->select(
                "$this->table.id",
                "$this->table.user",
                "$this->table.name",
                "$this->table.email",
                "$this->table.dashboard",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "statusakses.id as statusakses",
                "statusakses.memo as statusaksesmemo",
                "customer.id as customerid",
                "customer.nama as customernama",
                "karyawan.id as karyawanid",
                "karyawan.nama as karyawannama",
                "$this->table.modifiedby",
                "$this->table.created_at",
                "$this->table.updated_at"
            )
            ->leftJoin('parameter', 'user.status', '=', 'parameter.id')
            ->leftJoin('parameter as statusakses', 'user.statusakses', '=', 'statusakses.id')
            ->leftJoin('customer', 'user.customerid', '=', 'customer.id')
            ->leftJoin('karyawan', 'user.karyawanid', '=', 'karyawan.id');
        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);

        $data = $query->get();

        return $data;
    }


    public function findAll($id)
    {
        $query = DB::table('user')
            ->select(
                "user.id",
                "user.user",
                "user.name",
                "user.email",
                "user.dashboard",
                "parameter.id as status",
                "parameter.text as statusnama",
                "statusakses.id as statusakses",
                "statusakses.text as statusaksesnama",
                "customer.id as customerid",
                "customer.nama as customernama",
                "karyawan.id as karyawanid",
                "karyawan.nama as karyawannama",
                "user.modifiedby",
                "user.created_at",
                "user.updated_at",
            )
            ->leftJoin('parameter', 'user.status', '=', 'parameter.id')
            ->leftJoin('parameter as statusakses', 'user.statusakses', '=', 'statusakses.id')
            ->leftJoin('customer', 'user.customerid', '=', 'customer.id')
            ->leftJoin('karyawan', 'user.karyawanid', '=', 'karyawan.id')
            ->where('user.id', $id);

        $data = $query->first();


        return $data;
    }

    public function findRoles($id)
    {
        $query = DB::table('userrole')
            ->select(
                "userrole.id",
                "userrole.user_id",
                "userrole.role_id",
                "userrole.modifiedby",
                "userrole.created_at",
                "userrole.updated_at",
            )
            ->leftJoin('user', 'user.id', '=', 'userrole.user_id')
            ->where('user.id', $id);

        $data = $query->first();

      
        return $data;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status BIGINT UNSIGNED NULL,
            statusakses BIGINT UNSIGNED NULL
        )");

        $status = DB::table('parameter')
            ->select('id')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('default', '=', 'YA')
            ->first();
        $iddefaultstatus = $status->id ?? 0;

        $status = DB::table('parameter')
            ->select('id')
            ->where('grp', '=', 'STATUS AKSES')
            ->where('subgrp', '=', 'STATUS AKSES')
            ->where('default', '=', 'YA')
            ->first();
        $iddefaultstatusakses = $status->id ?? 0;
        DB::statement("INSERT INTO $tempdefault (status, statusakses) VALUES ($iddefaultstatus, $iddefaultstatusakses)");
        $query = DB::table($tempdefault)
            ->select(
                'status',
                'statusakses'
            );

        $data = $query->first();
        return $data;
    }

    public function selectColumns($query)
    {
        return $query->select(
            "$this->table.id",
            "$this->table.user",
            "$this->table.name",
            "$this->table.dashboard",
            "parameter.text as status",
            "statusakses.text as statusakses",
            "$this->table.modifiedby",
            "$this->table.created_at",
            "$this->table.updated_at"
        )
            ->leftJoin('parameter', 'user.status', '=', 'parameter.id')
            ->leftJoin('parameter as statusakses', 'user.statusakses', '=', 'parameter.id');
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
            user VARCHAR(255) NULL,
            name VARCHAR(255) NULL,
            password VARCHAR(255) NULL,
            customerid INT NULL,
            karyawanid INT NULL,
            dashboard VARCHAR(255) NULL,
            status INT,
            statusakses INT,
            email VARCHAR(255) NULL,
            tablecustomer VARCHAR(50) NULL,
            tablekaryawan VARCHAR(50) NULL,
            modifiedby VARCHAR(50) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");
        DB::statement("INSERT INTO $temp (id, user, name, password,customerid,karyawanid, dashboard, status, statusakses, email,tablecustomer,tablesupplier, modifiedby, created_at, updated_at)
            $filteredQuery
        ");

        return  $temp;
    }

    public function lockAndDestroy($identifier, string $field = 'id'): Model
    {
        $table = $this->getTable();
        $model = $this->where($field, $identifier)->lockForUpdate()->first();

        if ($model) {
            $isDeleted = $model->where($field, $identifier)->delete();

            if ($isDeleted) {
                return $model;
            }

            throw new Exception("Error deleting '$field' '$identifier' in '$table'");
        }

        throw new ModelNotFoundException("No data found for '$field' '$identifier' in '$table'");
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
                            $query = $query->where('parameter.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'statusakses') {
                            $query = $query->where('statusakses.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'menuparent') {
                            $query = $query->where('menu2.menuname', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'aco_id') {
                            $query = $query->where('acos.nama', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'status') {
                            $query = $query->orWhere('parameter.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'statusakses') {
                            $query = $query->orWhere('statusakses.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'menuparent') {
                            $query = $query->orWhere('menu2.menuname', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'aco_id') {
                            $query = $query->orWhere('acos.nama', 'LIKE', "%$filters[data]%");
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

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'userrole')->withTimestamps();
    }

    public function acls()
    {
        return $this->belongsToMany(Aco::class, 'useracl')
            ->withTimestamps()
            ->select(
                'acos.id',
                'acos.class',
                'acos.method',
                'acos.nama',
                'acos.modifiedby',
                'useracl.created_at',
                'useracl.updated_at'
            );
    }

    public function processStore(array $data): User
    {
        // dump($data);
        $user = new User();
        $user->user = strtoupper($data['user']);
        $user->name = strtoupper($data['name']);
        $user->email = strtoupper($data['email']);
        $user->password = Hash::make($data['password']);
        $user->customerid = $data['customerid'] ?? 0;
        $user->karyawanid = $data['karyawanid'] ?? 0;
        $user->dashboard = strtoupper($data['dashboard']);
        $user->status = $data['status'];
        $user->statusakses = $data['statusakses'];
        $user->tablecustomer = $data['tablecustomer'] ?? '';
        $user->tablekaryawan = $data['tablekaryawan'] ?? '';
        $user->modifiedby = auth('api')->user()->name;

       

        if (!$user->save()) {
            throw new \Exception('Error storing user.');
        }

        if (count($data['roleids'])) {
            (new Role())->processRoleStore([
                'role_ids' => $data['roleids']
            ],$user);
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($user->getTable()),
            'postingdari' => 'ENTRY USER',
            'idtrans' => $user->id,
            'nobuktitrans' => $user->id,
            'aksi' => 'ENTRY',
            'datajson' => $user->makeVisible(['password', 'remember_token'])->toArray(),
            'modifiedby' => $user->modifiedby
        ]);

        // dd($user);

        return $user;
    }

    public function processUpdate(User $user, array $data): User
    {
        $user->user = $data['user'];
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->customerid = $data['customerid'] ?? '';
        $user->karyawanid = $data['karyawanid'] ?? 0;
        $user->dashboard = $data['dashboard'];
        $user->status = $data['status'];
        $user->statusakses = $data['statusakses'];
        $user->modifiedby = auth('api')->user()->user;

        if (!$user->save()) {
            throw new \Exception('Error updating user.');
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($user->getTable()),
            'postingdari' => 'EDIT USER',
            'idtrans' => $user->id,
            'nobuktitrans' => $user->id,
            'aksi' => 'EDIT',
            'datajson' => $user->makeVisible(['password', 'remember_token'])->toArray(),
            'modifiedby' => $user->modifiedby
        ]);

        return $user;
    }

    public function processDestroy($id): User
    {
        $user = new User();
        $user = $user->lockAndDestroy($id);

        $getuserrole = DB::table("UserRole")->from(
            DB::raw("userrole")
        )
            ->select('id')
            ->where('user_id', $id)->get();
        $datadetail = json_decode($getuserrole, true);

        foreach ($datadetail as $item) {
            $userrole = (new UserRole())->processDestroy($item['id']);
        }


        $getuseracl = DB::table("UserAcl")->from(
            DB::raw("useracl")
        )
            ->select('id')
            ->where('user_id', $id)->get();
        $datadetail = json_decode($getuseracl, true);
        foreach ($datadetail as $item) {
            $useracl = (new UserAcl())->processDestroy($item['id']);
        }



        (new LogTrail())->processStore([
            'namatabel' => strtoupper($user->getTable()),
            'postingdari' => 'DELETE USER',
            'idtrans' => $user->id,
            'nobuktitrans' => $user->id,
            'aksi' => 'DELETE',
            'datajson' => $user->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        return $user;
    }

    public function getConfirmUser($username, $password)
    {
        $this->setRequestParameters();
        $query = DB::table($this->table)
            ->select(
                "$this->table.id",
                "$this->table.user",
                "$this->table.password",
            )
            ->where('user', $username)->first();
        if ($query != '') {
            if (Hash::check($password, $query->password)) {
                $data = true;
            }else{
                $data = false;
            }
        } else {
            $data = false;
        }
        return $data;
    }
}
