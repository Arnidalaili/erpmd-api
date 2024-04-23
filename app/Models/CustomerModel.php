<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CustomerModel extends MyModel
{
    use HasFactory;

    protected $table = 'customer';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ]; 
    
    public function get($page)
    {
      
        $this->setRequestParameters();

        $query = DB::table('customer')
        ->select(
            'customer.id',
            'customer.nama as nama',
            'customer.nama2',
            'customer.telepon',
            'customer.alamat',
            'customer.keterangan',
            'owner.id as ownerid',
            'owner.nama as ownernama',
            'product.id as productid',
            'product.nama as productnama',
            'customer.productid',
            'customer.groupid',
            'parameter.id as status',
            'parameter.text as statusnama',
            'modifier.id as modifiedby',
            'modifier.name as modifiedby_name',
            'customer.created_at',
            'customer.updated_at',
           
        )
        ->leftJoin(DB::raw("user as modifier"), 'customer.modifiedby', 'modifier.id')
        ->leftJoin(DB::raw("owner"), 'customer.ownerid', 'owner.id')
        ->leftJoin(DB::raw("product"), 'customer.productid', 'product.id')
        ->leftJoin(DB::raw("parameter"), 'customer.status', 'parameter.id');
      

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

      

        $this->filter($query);

        if (request()->offset) {
            $query->orderBy('customer.name', 'asc');

            $perPage = 20;

            $total_count = $query->count();
            $total_pages = ceil($total_count / $perPage);

            $data = $query->offset(($page - 1) * $perPage)
                    ->limit($perPage)
                    ->get(); 

        }else{
            $this->sort($query);
            $this->paginate($query);
        }


        $data = $query->get();

       

        return $data;
    }

    public function sort($query)
    {
        // if ($this->params['sortIndex'] == 'id') {
        //     return $query->orderBy('customer.name', $this->params['sortOrder']);
        // } else {
          
            return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
        // }
    }

    public function filter($query, $relationFields = [])
    {
       
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'type') {
                            $query = $query->where('customer.name', 'like', "%$filters[data]%");
                        }else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("format(" . $this->table . "." . $filters['field'] . ", 'dd-MM-yyyy HH:mm:ss') LIKE '%$filters[data]%'");
                        } else if($filters['field'] == 'text'){
                            $query = $query->where('customer.name', 'like', "%$filters[data]%");
                            
                        }else {
                            // $query = $query->whereRaw($this->table . ".[" .  $filters['field'] . "] LIKE '%" . escapeLike($filters['data']) . "%' escape '|'");
                            // $query = $query->where($this->table . '.' . $filters['field'], 'like', "%$filters[data]%");
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");

                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'type') {
                            $query = $query->orWhere('customer.name', 'LIKE', "%$filters[data]%");
                        }else if($filters['field'] == 'status_aktif'){
                            $query = $query->where('parameter.text', 'like', "%$filters[data]%");
                        }else if($filters['field'] == 'modified_by'){
                            $query = $query->where('modifier.name', 'like', "%$filters[data]%");
                            
                        }else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
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
}
