<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;


class UserAcl extends MyModel
{
    use HasFactory;

    protected $table = 'useracl';

    protected $casts = [
        'created_at' => 'date:d-m-Y H:i:s',
        'updated_at' => 'date:d-m-Y H:i:s'
    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function get($query)
    {
        $load = request()->load ?? '';

        
        $this->setRequestParameters();
        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        if ($load == '') {
           
            $this->sort($query);
            $this->filter($query);
            $this->paginate($query);
        }

      
        return $query->get();
    }

    

    public function sort($query)
    {
        return $query->orderBy($this->params['sortIndex'], $this->params['sortOrder']);
    }


    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field']) {
                            if (in_array($filters['field'], ['modifiedby', 'created_at', 'updated_at'])) {
                                $query = $query->where('useracl.' . $filters['field'], 'LIKE', "%$filters[data]%");
                            } else {
                                $query = $query->where($filters['field'], 'LIKE', "%$filters[data]%");
                            }
                        }
                    }
                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if (in_array($filters['field'], ['modifiedby', 'created_at', 'updated_at'])) {
                            $query = $query->orWhere('useracl.' . $filters['field'], 'LIKE', "%$filters[data]%");
                        } else {
                            $query = $query->orWhere($filters['field'], 'LIKE', "%$filters[data]%");
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
    
    public function processDestroy($id): UserAcl
    {
        $userAcl = new UserAcl();
        $userAcl = $userAcl->lockAndDestroy($id);
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($userAcl->getTable()),
            'postingdari' => 'DELETE PARAMETER',
            'idtrans' => $userAcl->id,
            'nobuktitrans' => $userAcl->id,
            'aksi' => 'DELETE',
            'datajson' => $userAcl->toArray(),
            'modifiedby' => $userAcl->modifiedby
        ]);
        return $userAcl;
    }
}
