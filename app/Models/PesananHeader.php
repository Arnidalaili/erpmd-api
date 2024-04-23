<?php

namespace App\Models;

use App\Services\RunningNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PesananHeader extends MyModel
{
    use HasFactory;

    protected $table = 'pesananheader';

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
        $query = DB::table($this->table . ' as pesananheader')
            ->select(
                "pesananheader.id",
                "customer.id as customerid",
                "customer.nama as customernama",
                "pesananheader.alamatpengiriman",
                "pesananheader.tglpengiriman",
                "pesananheader.keterangan",
                "parameter.id as status",
                "parameter.text as statusnama",
                "parameter.memo as statusmemo",
                "status2.text as status2nama",
                "status2.memo as status2memo",
                "pesananheader.nobukti",
                "pesananheader.tglbukti",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pesananheader.created_at',
                'pesananheader.updated_at'
            )
            ->leftJoin(DB::raw("customer"), 'pesananheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as status2"), 'pesananheader.status2', 'status2.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananheader.modifiedby', 'modifier.id')
            ->where("customer.id", auth('api')->user()->customerid);

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

        $query = DB::table('pesananheader')
            ->select(
                "pesananheader.id",
                "customer.id as customerid",
                "customer.nama as customernama",
                "customer.nama2 as customernama2",
                "pesananheader.alamatpengiriman",
                "pesananheader.tglpengiriman",
                "pesananheader.keterangan",
                "parameter.id as status",
                "parameter.text as statusnama",
                "parameter.memo as statusmemo",
                "status2.text as status2nama",
                "status2.memo as status2memo",
                "pesananheader.nobukti",
                "pesananheader.tglbukti",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pesananheader.created_at',
                'pesananheader.updated_at'

            )
            ->leftJoin(DB::raw("customer"), 'pesananheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as status2"), 'pesananheader.status2', 'status2.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananheader.modifiedby', 'modifier.id')
            ->where('pesananheader.id', $id);
        $data = $query->first();


        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pesananheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananheader.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(pesananheader.nobukti, '/', 1)"), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'customernama') {
            return $query->orderBy('customer.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'status2memo') {
            return $query->orderBy('status2.memo', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'customernama') {
                            $query = $query->where('customer.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'sales_name') {
                            $query = $query->where('sales.name', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'status2memo') {
                            $query = $query->where('status2.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglpengiriman' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'customernama') {
                            $query = $query->orWhere('customer.nama', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'sales_name') {
                            $query = $query->orWhere('sales.name', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->orWhere('parameter.memo', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'status2memo') {
                            $query = $query->orWhere('status2.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglpengiriman' || $filters['field'] == 'tglbukti') {
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

    public function selectColumns($query)
    {
        return $query->from(
            DB::raw($this->table)
        )
            ->select(
                DB::raw("
                $this->table.id,
                customer.id as customerid,
                customer.nama as customernama,
                $this->table.alamatpengiriman,
                $this->table.tglpengiriman,
                $this->table.keterangan,
                parameter.id as status,
                parameter.text as statusnama,
                parameter.memo as statusmemo,
                status2.text as status2nama,
                status2.memo as status2memo,
                $this->table.nobukti,
                $this->table.tglbukti,
                modifier.id as modifiedby,
                modifier.name as modifiedby_name,
                $this->table.created_at,
                $this->table.updated_at
            ")
            )
            ->leftJoin(DB::raw("customer"), 'pesananheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as status2"), 'pesananheader.status2', 'status2.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananheader.modifiedby', 'modifier.id')
            ->where("customer.id", auth('api')->user()->customerid);
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
            id INT UNSIGNED,
            customerid INT,
            customernama VARCHAR(100),
            alamatpengiriman VARCHAR(500),
            tglpengiriman DATETIME,
            keterangan VARCHAR(500),
            status INT,
            statusnama VARCHAR(500),
            statusmemo VARCHAR(500),
            status2nama VARCHAR(500),
            status2memo VARCHAR(500),
            nobukti VARCHAR(100),
            tglbukti DATETIME,
            modifiedby INT,
            modifiedby_name VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME,
            position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");
        DB::table($temp)->insertUsing(["id", "customerid", "customernama", "alamatpengiriman", "tglpengiriman", "keterangan", "status", "statusnama", "statusmemo", "status2nama", "status2memo", "nobukti", "tglbukti", "modifiedby", "modifiedby_name", "created_at", "updated_at"], $query);
        return $temp;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status INT NULL,
            statusnama VARCHAR(100),
            customerid INT NULL,
            customernama VARCHAR(100),
            alamatpengiriman VARCHAR(500)
        )");

        $status = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        $customer = DB::table("customer")
            ->select('id', 'nama', 'alamat')
            ->where('id', '=', auth('api')->user()->customerid)
            ->first();


        DB::statement("INSERT INTO $tempdefault (status,statusnama, customerid, customernama, alamatpengiriman) VALUES (?,?, ?, ?, ?)", [
            $status->id,
            $status->text,
            $customer->id ?? 1,
            $customer->nama ?? 'ADMIN',
            $customer->alamat ?? 'PUSAT'
        ]);



        $query = DB::table(
            $tempdefault
        )
            ->select(
                'status',
                'statusnama',
                'customerid',
                'customernama',
                'alamatpengiriman'
            );

        $data = $query->first();

        return $data;
    }

    public function processStore(array $data): PesananHeader
    {
        $pesananHeader = new PesananHeader();

        /*STORE HEADER*/
        $group = 'PESANAN HEADER BUKTI';
        $subGroup = 'PESANAN HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();


        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));
        $tglpengiriman = date('Y-m-d', strtotime($data['tglpengiriman']));


        $pesananHeader->tglbukti = $tglbukti;
        $pesananHeader->customerid = $data['customerid'];
        $pesananHeader->alamatpengiriman = $data['alamatpengiriman'];
        $pesananHeader->tglpengiriman = $tglpengiriman;
        $pesananHeader->keterangan = $data['keterangan'];
        $pesananHeader->status = $data['status'] ?? 1;

        $pesananHeader->modifiedby = auth('api')->user()->id;
        $pesananHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $pesananHeader->getTable(), date('Y-m-d', strtotime($tglbukti)));

        if (!$pesananHeader->save()) {
            throw new \Exception("Error storing faktur penjualan header.");
        }

        $pesananHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pesananHeader->getTable()),
            'postingdari' => strtoupper('ENTRY PESANAN HEADER'),
            'idtrans' => $pesananHeader->id,
            'nobuktitrans' => $pesananHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pesananHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        $pesananDetails = [];
        $productIdDetails = [];
        $keteranganDetail = [];
        $qtyDetail = [];
        $satuanDetail = [];
        $hargaDetail = [];
        $totalHarga = [];
        $subTotal = 0;

        for ($i = 0; $i < count($data['productid']); $i++) {
            $getHarga = DB::table("product")
                ->select('hargajual', 'hargabeli')
                ->where('id', '=', $data['productid'][$i])
                ->first();

            if (!$data['keterangandetail']) {
                $data['keterangandetail'][$i] = '';
            }

            $pesananDetail = (new PesananDetail())->processStore($pesananHeader, [
                'pesananid' => $pesananHeader->id,
                'productid' => $data['productid'][$i] ?? 0,
                'keterangan' => $data['keterangandetail'][$i] ?? '',
                'qty' => $data['qty'][$i] ?? 0,
                'satuanid' => $data['satuanid'][$i] ?? '',
                'status' => $data['status'][$i] ?? '',
                'modifiedby' => auth('api')->user()->id,
            ]);

            $fakturpenjualanDetails[] = $pesananDetail->toArray();
            $productIdDetails[] = $data['productid'][$i];
            $keteranganDetail[] = $data['keterangandetail'][$i] ?? '';
            $qtyDetail[] = $data['qty'][$i];
            $satuanDetail[] = $data['satuanid'][$i];
            $hargaJualDetail[] = $getHarga->hargajual;
            $hargaBeliDetail[] = $getHarga->hargabeli;
            $totalHargaJual[] = $data['qty'][$i] * $getHarga->hargajual;
            $totalHargaBeli[] = $data['qty'][$i] * $getHarga->hargabeli;
            $subTotal += $data['qty'][$i] * $getHarga->hargajual;
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($pesananHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY faktur penjualan Header'),
            'idtrans' =>  $pesananHeaderLogTrail->id,
            'nobuktitrans' => $pesananHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pesananDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

        $tax = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'tax')
            ->where('subgrp', '=', 'tax')
            ->first();

        $taxText = $tax->text;

        $taxAmount = ($taxText / 100) * $subTotal;

        $total = $taxAmount + $subTotal;

        /*STORE PESANAN FINAL*/
        $pesananFinalRequest = [
            "tglbukti" => $tglbukti,
            "customerid" => $data['customerid'],
            "nobuktipenjualan" => '',
            "nobuktipembelian" => '',
            "pesananid" => $pesananHeader->id,
            "alamatpengiriman" => $data['alamatpengiriman'],
            "tglpengiriman" => $tglpengiriman,
            "tglbuktipesanan" => $tglbukti,
            "keterangan" => $data['keterangan'],
            "total" => $total,
            "tax" => $tax->text,
            "totalharga" => $totalHarga,
            "taxamount" => $taxAmount,
            "subtotal" => $subTotal,
            "status" => $data['status'],
            "productid" => $productIdDetails,
            "satuanid" => $satuanDetail,
            "qtyjual" => $qtyDetail,
            "qtybeli" => $qtyDetail,
            'hargajual' => $hargaJualDetail,
            'hargabeli' => $hargaBeliDetail,
            "keterangandetail" => $keteranganDetail,
        ];

        // dd($pesananFinalRequest);

        $pesananFinalHeader = (new PesananFinalHeader())->processStore($pesananFinalRequest);

        return $pesananHeader;
    }

    // public function processUpdate(PesananHeader $pesananHeader, array $data): PesananHeader
    // {
    //     $nobuktiOld = $pesananHeader->nobukti;

    //     $group = 'FAKTUR PENJUALAN BUKTI';
    //     $subGroup = 'FAKTUR PENJUALAN BUKTI';
    //     // dd($data);

    //     $pesananHeader->customer_id = $data['customer_id'];
    //     $pesananHeader->nopo = $data['nopo'];
    //     $pesananHeader->shipto = $data['shipto'];
    //     $pesananHeader->rate = $data['rate'];
    //     $pesananHeader->fob = $data['fob'];
    //     $pesananHeader->terms = $data['terms'];
    //     $pesananHeader->fiscalrate = $data['fiscalrate'];
    //     $pesananHeader->shipdate =  date('Y-m-d', strtotime($data['shipdate']));
    //     $pesananHeader->shipvia = $data['shipvia'];
    //     $pesananHeader->receivableacoount = $data['receivableacoount'];
    //     $pesananHeader->sales_id = $data['sales_id'];
    //     $pesananHeader->modifiedby = auth('api')->user()->id;


    //     if (!$fakturPenjualanHeader->save()) {
    //         throw new \Exception("Error storing Faktur Penjualan Header.");
    //     }

    //     $fakturPenjualanHeaderLogTrail = (new LogTrail())->processStore([
    //         'namatabel' => strtoupper($fakturPenjualanHeader->getTable()),
    //         'postingdari' => strtoupper('EDIT FAKTUR PENJUALAN HEADER'),
    //         'idtrans' => $fakturPenjualanHeader->id,
    //         'nobuktitrans' => $fakturPenjualanHeader->noinvoice,
    //         'aksi' => 'EDIT',
    //         'datajson' => $fakturPenjualanHeader->toArray(),
    //         'modifiedby' => auth('api')->user()->user
    //     ]);

    //     /*DELETE EXISTING HUTANG*/
    //     $hutangDetail = FakturPenjualanDetailModel::where('fakturpenjualan_id', $fakturPenjualanHeader->id)->lockForUpdate()->delete();

    //     /* Store detail */
    //     $fakturpenjualanDetails = [];
    //     $itemIdDetails = [];
    //     $descriptionDetail = [];
    //     $qtyDetail = [];
    //     $hargaSatuanDetail = [];
    //     $amountDetail = [];

    //     for ($i = 0; $i < count($data['item_id']); $i++) {
    //         $fakturpenjualanDetail = (new FakturPenjualanDetailModel())->processStore($fakturPenjualanHeader, [
    //             'fakturpenjualan_id' => $fakturPenjualanHeader->id,
    //             'item_id' => $data['item_id'][$i],
    //             'description' => $data['description'][$i] ?? '',
    //             'qty' => $data['qty'][$i] ?? 0,
    //             'hargasatuan' => $data['hargasatuan'][$i] ?? 0,
    //             'amount' => $data['amount'][$i] ?? 0,
    //             'modifiedby' => $fakturPenjualanHeader->modifiedby,
    //         ]);


    //         $fakturpenjualanDetails[] = $fakturpenjualanDetail->toArray();


    //     }

    //     (new LogTrail())->processStore([
    //         'namatabel' => strtoupper($fakturPenjualanHeaderLogTrail->getTable()),
    //         'postingdari' =>  strtoupper('ENTRY faktur penjualan Header'),
    //         'idtrans' =>  $fakturPenjualanHeaderLogTrail->id,
    //         'nobuktitrans' => $fakturPenjualanHeader->noinvoice,
    //         'aksi' => 'ENTRY',
    //         'datajson' => $fakturpenjualanDetail,
    //         'modifiedby' => auth('api')->user()->user,
    //     ]);



    //     return $fakturPenjualanHeader;
    // }

    public function getReport($id)
    {
        $this->setRequestParameters();

        $query = DB::table('pesananheader')
            ->select(
                "pesananheader.id",
                "customer.id as customerid",
                "customer.nama as customernama",
                "customer.nama2 as customernama2",
                "pesananheader.alamatpengiriman",
                "pesananheader.tglpengiriman",
                "pesananheader.keterangan",
                "parameter.id as status",
                "parameter.text as statusnama",
                "pesananheader.nobukti",
                "pesananheader.tglbukti",

                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pesananheader.created_at',
                'pesananheader.updated_at',
                DB::raw("'Cetak Faktur' as judulLaporan"),
                DB::raw("'PT. TRANSPORINDO AGUNG SEJAHTERA' as judul"),
                DB::raw(" 'User :" . auth('api')->user()->name . "' as usercetak")
            )
            ->leftJoin(DB::raw("customer"), 'pesananheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananheader.modifiedby', 'modifier.id')
            ->where('pesananheader.id', $id);
        $data = $query->first();


        return $data;
    }

    public function editingAt($id, $btn)
    {
        $pesananHeader = PesananHeader::find($id);
        $oldUser = $pesananHeader->editing_by;
        if ($btn == 'EDIT') {
            $pesananHeader->editing_by = auth('api')->user()->name;
            $pesananHeader->editing_at = date('Y-m-d H:i:s');
        } else {
            if ($pesananHeader->editing_by == auth('api')->user()->name) {
                $pesananHeader->editing_by = '';
                $pesananHeader->editing_at = null;
            }
        }
        if (!$pesananHeader->save()) {
            throw new \Exception("Error Update penerimaan giro header.");
        }

        $pesananHeader->oldeditingby = $oldUser;
        return $pesananHeader;
    }

    public function processData($data)
    {
        $productIds = [];
        $satuanIds = [];
        $qtys = [];
        $keteranganDetails = [];
        foreach ($data as $detail) {
            $productIds[] = $detail['productid'];
            $satuanIds[] = $detail['satuanid'];
            $qtys[] = $detail['qty'];
            $keteranganDetails[] = $detail['keterangandetail'];
        }


        $data = [
            "tglbukti" => request()->tglbukti,
            "nobuktipesanan" => request()->nobuktipesanan,
            "customerid" => request()->customerid,
            "alamatpengiriman" => request()->alamatpengiriman,
            "tglpengiriman" => request()->tglpengiriman,
            "tglbuktipesanan" => request()->tglbuktipesanan ?? '',
            "keterangan" => request()->keterangan,
            "status" => request()->status,
            "productid" =>  $productIds,
            "satuanid" => $satuanIds,
            "qty" => $qtys,
            "keterangandetail" => $keteranganDetails,
        ];

        return $data;
    }
}
