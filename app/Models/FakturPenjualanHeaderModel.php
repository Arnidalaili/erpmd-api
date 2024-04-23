<?php

namespace App\Models;

use App\Services\RunningNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FakturPenjualanHeaderModel extends MyModel
{
    use HasFactory;

    protected $table = 'fakturpenjualanheader';

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
       
        $query = DB::table($this->table .' as fakturpenjualanheader')
            ->select(
                "fakturpenjualanheader.id",
                "customers.id as customer_id",
                "customers.name as customer_name",
                "sales.id as sales_id",
                "sales.name as sales_name",
                "fakturpenjualanheader.nopo",
                "fakturpenjualanheader.noinvoice",
                "fakturpenjualanheader.invoicedate",
                "fakturpenjualanheader.shipto",
                "fakturpenjualanheader.rate",
                "fakturpenjualanheader.fob",
                "fakturpenjualanheader.terms",
                "fakturpenjualanheader.fiscalrate",
                "fakturpenjualanheader.shipdate",
                "fakturpenjualanheader.shipvia",
                "fakturpenjualanheader.receivableacoount",
               
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'fakturpenjualanheader.created_at',
                'fakturpenjualanheader.updated_at'

            )
            ->leftJoin(DB::raw("customers"), 'fakturpenjualanheader.customer_id', 'customers.id')
            ->leftJoin(DB::raw("customers as sales"), 'fakturpenjualanheader.sales_id', 'sales.id')
            ->leftJoin(DB::raw("user as modifier"), 'fakturpenjualanheader.modifiedby', 'modifier.id');


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

        $query = DB::table('fakturpenjualanheader')
            ->select(
                "fakturpenjualanheader.id",
                "customers.id as customer_id",
                "customers.name as customer_name",
                "fakturpenjualanheader.nopo",
                "fakturpenjualanheader.noinvoice",
                "fakturpenjualanheader.invoicedate",
                "fakturpenjualanheader.shipto",
                "fakturpenjualanheader.rate",
                "fakturpenjualanheader.fob",
                "fakturpenjualanheader.terms",
                "fakturpenjualanheader.fiscalrate",
                "fakturpenjualanheader.shipdate",
                "fakturpenjualanheader.shipvia",
                "fakturpenjualanheader.receivableacoount",
                "sales.id as sales_id",
                "sales.name as sales_name",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'fakturpenjualanheader.created_at',
                'fakturpenjualanheader.updated_at'

            )
            ->leftJoin(DB::raw("customers"), 'fakturpenjualanheader.customer_id', 'customers.id')
            ->leftJoin(DB::raw("customers as sales"), 'fakturpenjualanheader.sales_id', 'sales.id')
            ->leftJoin(DB::raw("user as modifier"), 'fakturpenjualanheader.modifiedby', 'modifier.id')
            ->where('fakturpenjualanheader.id', $id);
        $data = $query->first();
        return $data;
    }

    public function processStore(array $data): FakturPenjualanHeaderModel
    {
      
        $fakturPenjualanHeader = new FakturPenjualanHeaderModel();

         /*STORE HEADER*/
         $group = 'FAKTUR PENJUALAN BUKTI';
         $subGroup = 'FAKTUR PENJUALAN BUKTI';
 
         $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();


        $invoicedate = date('Y-m-d', strtotime($data['invoicedate']));
        $shipdate = date('Y-m-d', strtotime($data['shipdate']));

        
        $fakturPenjualanHeader->invoicedate = $invoicedate;
        $fakturPenjualanHeader->customer_id = $data['customer_id'];
        $fakturPenjualanHeader->nopo = $data['nopo'];
        $fakturPenjualanHeader->shipto = $data['shipto'];
        $fakturPenjualanHeader->rate = $data['rate'];
        $fakturPenjualanHeader->fob = $data['fob'];
        $fakturPenjualanHeader->terms = $data['terms'];
        $fakturPenjualanHeader->fiscalrate = $data['fiscalrate'];
        $fakturPenjualanHeader->shipdate = $shipdate;
        $fakturPenjualanHeader->shipvia = $data['shipvia'];
        $fakturPenjualanHeader->receivableacoount = $data['receivableacoount'];
        $fakturPenjualanHeader->sales_id = $data['sales_id'];
        
    
        $fakturPenjualanHeader->modifiedby = auth('api')->user()->id;
        $fakturPenjualanHeader->noinvoice = (new RunningNumberService)->get($group, $subGroup, $fakturPenjualanHeader->getTable(), date('Y-m-d', strtotime($data['invoicedate'])));      
        
        if (!$fakturPenjualanHeader->save()) {
            throw new \Exception("Error storing faktur penjualan header.");
        }
        $fakturPenjualanHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($fakturPenjualanHeader->getTable()),
            'postingdari' => strtoupper('ENTRY FAKTUR PENJUALAN HEADER'),
            'idtrans' => $fakturPenjualanHeader->id,
            'nobuktitrans' => $fakturPenjualanHeader->noinvoice,
            'aksi' => 'ENTRY',
            'datajson' => $fakturPenjualanHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

       

        $fakturpenjualanDetails = [];
        $itemIdDetails = [];
        $descriptionDetail = [];
        $qtyDetail = [];
        $hargaSatuanDetail = [];
        $amountDetail = [];

     

        for ($i = 0; $i < count($data['item_id']); $i++) {
            $fakturpenjualanDetail = (new FakturPenjualanDetailModel())->processStore($fakturPenjualanHeader, [
                'fakturpenjualan_id' => $fakturPenjualanHeader->id,
                'item_id' => $data['item_id'][$i],
                'description' => $data['description'][$i] ?? '',
                'qty' => $data['qty'][$i] ?? '',
                'hargasatuan' => $data['hargasatuan'][$i] ?? 0,
                'amount' => $data['amount'][$i] ?? 0,
                'modifiedby' => $fakturPenjualanHeader->modifiedby,
            ]);


            $fakturpenjualanDetails[] = $fakturpenjualanDetail->toArray();

         
        }
      
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($fakturPenjualanHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY faktur penjualan Header'),
            'idtrans' =>  $fakturPenjualanHeaderLogTrail->id,
            'nobuktitrans' => $fakturPenjualanHeader->noinvoice,
            'aksi' => 'ENTRY',
            'datajson' => $fakturpenjualanDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

       

        return $fakturPenjualanHeader;
    }

    public function processUpdate(FakturPenjualanHeaderModel $fakturPenjualanHeader, array $data): FakturPenjualanHeaderModel
    {
        $nobuktiOld = $fakturPenjualanHeader->noinvoice;

        $group = 'FAKTUR PENJUALAN BUKTI';
        $subGroup = 'FAKTUR PENJUALAN BUKTI';
        // dd($data);
      
        $fakturPenjualanHeader->customer_id = $data['customer_id'];
        $fakturPenjualanHeader->nopo = $data['nopo'];
        $fakturPenjualanHeader->shipto = $data['shipto'];
        $fakturPenjualanHeader->rate = $data['rate'];
        $fakturPenjualanHeader->fob = $data['fob'];
        $fakturPenjualanHeader->terms = $data['terms'];
        $fakturPenjualanHeader->fiscalrate = $data['fiscalrate'];
        $fakturPenjualanHeader->shipdate =  date('Y-m-d', strtotime($data['shipdate']));
        $fakturPenjualanHeader->shipvia = $data['shipvia'];
        $fakturPenjualanHeader->receivableacoount = $data['receivableacoount'];
        $fakturPenjualanHeader->sales_id = $data['sales_id'];
        $fakturPenjualanHeader->modifiedby = auth('api')->user()->id;


        if (!$fakturPenjualanHeader->save()) {
            throw new \Exception("Error storing Faktur Penjualan Header.");
        }

        $fakturPenjualanHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($fakturPenjualanHeader->getTable()),
            'postingdari' => strtoupper('EDIT FAKTUR PENJUALAN HEADER'),
            'idtrans' => $fakturPenjualanHeader->id,
            'nobuktitrans' => $fakturPenjualanHeader->noinvoice,
            'aksi' => 'EDIT',
            'datajson' => $fakturPenjualanHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        /*DELETE EXISTING HUTANG*/
        $hutangDetail = FakturPenjualanDetailModel::where('fakturpenjualan_id', $fakturPenjualanHeader->id)->lockForUpdate()->delete();

        /* Store detail */
        $fakturpenjualanDetails = [];
        $itemIdDetails = [];
        $descriptionDetail = [];
        $qtyDetail = [];
        $hargaSatuanDetail = [];
        $amountDetail = [];

        for ($i = 0; $i < count($data['item_id']); $i++) {
            $fakturpenjualanDetail = (new FakturPenjualanDetailModel())->processStore($fakturPenjualanHeader, [
                'fakturpenjualan_id' => $fakturPenjualanHeader->id,
                'item_id' => $data['item_id'][$i],
                'description' => $data['description'][$i] ?? '',
                'qty' => $data['qty'][$i] ?? 0,
                'hargasatuan' => $data['hargasatuan'][$i] ?? 0,
                'amount' => $data['amount'][$i] ?? 0,
                'modifiedby' => $fakturPenjualanHeader->modifiedby,
            ]);


            $fakturpenjualanDetails[] = $fakturpenjualanDetail->toArray();

         
        }
      
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($fakturPenjualanHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY faktur penjualan Header'),
            'idtrans' =>  $fakturPenjualanHeaderLogTrail->id,
            'nobuktitrans' => $fakturPenjualanHeader->noinvoice,
            'aksi' => 'ENTRY',
            'datajson' => $fakturpenjualanDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

       

        return $fakturPenjualanHeader;
    }

    public function processDestroy($id, $postingDari = ''): FakturPenjualanHeaderModel
    {
        $fakturPenjualanHeader = FakturPenjualanDetailModel::where('fakturpenjualan_id', '=', $id)->get();
        $dataDetail = $fakturPenjualanHeader->toArray();

        /*DELETE EXISTING FAKTUR PENJUALAN HEADER*/

        $fakturPenjualanHeader = new FakturPenjualanHeaderModel();
        $fakturPenjualanHeader = $fakturPenjualanHeader->lockAndDestroy($id);

        $fakturPenjualanHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $fakturPenjualanHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $fakturPenjualanHeader->id,
            'nobuktitrans' => $fakturPenjualanHeader->noinvoice,
            'aksi' => 'DELETE',
            'datajson' => $fakturPenjualanHeader->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        (new LogTrail())->processStore([
            'namatabel' => 'FAKTURPENJUALANDETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $fakturPenjualanHeaderLogTrail['id'],
            'nobuktitrans' => $fakturPenjualanHeader->noinvoice,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->name
        ]);
        return $fakturPenjualanHeader;
    }


    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('fakturpenjualanheader.noinvoice', $this->params['sortOrder']);
        } else {
            return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
        }
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'customer_name' ) {
                            $query = $query->where('customers.name', 'like', "%$filters[data]%");
                        } else if($filters['field'] == 'sales_name'){
                            $query = $query->where('sales.name', 'like', "%$filters[data]%");
                            
                        }else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            // $query = $query->where($this->table . '.' . $filters['field'], 'like', "%$filters[data]%");
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'customer_name') {
                            $query = $query->orWhere('customers.name', 'LIKE', "%$filters[data]%");

                        } else if($filters['field'] == 'sales_name'){
                            $query = $query->orWhere('sales.name', 'LIKE', "%$filters[data]%");
                            
                        } else if($filters['field'] == 'modifiedby_name'){
                            $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                            
                        }else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            // $query = $query->orWhere($this->table . '.' . $filters['field'], 'LIKE', "%$filters[data]%");
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

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->filter($query);
        $query = $this->sort($query);
        $filteredQuery = $query->toSql();

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE ".$temp." (
            id INT UNSIGNED,
            customer_id INT UNSIGNED,
            customer_name VARCHAR(100),
            nopo VARCHAR(100),
            noinvoice VARCHAR(100),
            invoicedate DATETIME,
            shipto VARCHAR(100),
            rate VARCHAR(500),
            fob VARCHAR(500),
            terms VARCHAR(500),
            fiscalrate VARCHAR(500),
            shipdate DATETIME,
            shipvia VARCHAR(500),
            receivableaccount VARCHAR(500),
            sales_id INT UNSIGNED,
            sales_name VARCHAR(500),
            warehouse_id INT,
            modified_by_id INT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");
        
        DB::statement("INSERT INTO $temp (id, customer_id,nopo,noinvoice,invoicedate,shipto,rate,fob,terms,fiscalrate,shipdate,shipvia,receivableaccount,sales_id, modifiedby, created_at, updated_at)
            $filteredQuery
        ");

        return $temp;
    }

    public function getReport($id)
    {
        $this->setRequestParameters();
        $getJudul = DB::table('parameter')->from(DB::raw("parameter"))
            ->select('text')
            ->where('grp', 'JUDULAN LAPORAN')
            ->where('subgrp', 'JUDULAN LAPORAN')
            ->first();
        
            $query = DB::table('fakturpenjualanheader')
            ->select(
                "fakturpenjualanheader.id",
                "customers.id as customer_id",
                "customers.name as customer_name",
                "fakturpenjualanheader.nopo",
                "fakturpenjualanheader.noinvoice",
                "fakturpenjualanheader.invoicedate",
                "fakturpenjualanheader.shipto",
                "fakturpenjualanheader.rate",
                "fakturpenjualanheader.fob",
                "fakturpenjualanheader.terms",
                "fakturpenjualanheader.fiscalrate",
                "fakturpenjualanheader.shipdate",
                "fakturpenjualanheader.shipvia",
                "fakturpenjualanheader.receivableacoount",
                "sales.id as sales_id",
                "sales.name as sales_name",
                DB::raw("'Cetak Faktur' as judulLaporan"),
                DB::raw("'" . $getJudul->text . "' as judul"),
                DB::raw(" 'User :" . auth('api')->user()->name . "' as usercetak")
            )
            ->leftJoin(DB::raw("customers"), 'fakturpenjualanheader.customer_id', 'customers.id')
            ->leftJoin(DB::raw("customers as sales"), 'fakturpenjualanheader.sales_id', 'sales.id')
            ->leftJoin(DB::raw("user as modifier"), 'fakturpenjualanheader.modifiedby', 'modifier.id')
            ->where('fakturpenjualanheader.id', $id);
        $data = $query->first();
        return $data;
    }
}
