<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class CekPesanan extends MyModel
{
    use HasFactory;

    protected $table = 'cekpesanan';

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
        // dd('test');
        // $tglpengiriman = date('Y-m-d', strtotime('+1 day'));
        $tglpengiriman = date('Y-m-d', strtotime(request()->periode));
        $this->setRequestParameters();
        $query = DB::table($this->table . ' as cekpesanan');
        $karyawanid = auth('api')->user()->karyawanid;
        // dd($tglpengiriman, $namaKaryawan, request()->periode);

        $query->select(
            "cekpesanan.id",
            "cekpesanan.pesananfinalid",
            "cekpesanan.pesananfinaldetailid",
            "pesananfinalheader.nobukti as pesananfinalnobukti",
            "cekpesanan.productid",
            "product.nama as productnama",
            "cekpesanan.customerid",
            "customer.nama as customernama",
            "cekpesanan.keterangan",
            DB::raw('IFNULL(cekpesanan.qty, 0) AS qty'),
            "cekpesanan.satuanid",
            "satuan.nama as satuannama",
            "parameter.id as cekpesanandetail",
            "parameter.memo as cekpesanandetailmemo",
            "parameter.text as cekpesanandetailnama",
        )
            ->leftJoin(DB::raw("parameter"), 'cekpesanan.cekpesanandetail', 'parameter.id')
            ->leftJoin(DB::raw("pesananfinalheader"), 'cekpesanan.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("customer"), 'cekpesanan.customerid', 'customer.id')
            ->leftJoin(DB::raw("product"), 'cekpesanan.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'cekpesanan.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("supplier"), 'product.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("karyawan"), 'supplier.karyawanid', 'karyawan.id')
            ->where("pesananfinalheader.tglpengiriman", $tglpengiriman)
            // ->where("karyawan.id", $karyawanid)
            ->where("pesananfinalheader.status", 1);

        // dd($query->get());
        if (request()->pesananfinalid != '') {
            // dd('masuk'); 
            $query->where('cekpesanan.pesananfinalid', '=', request()->pesananfinalid);
            // $query->where('pesananfinalheader.tglpengiriman', '=', $tglpengiriman);
        }


        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);
        $this->filter($query);

        $data = $query->get();

        // dd($data);

        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('cekpesanan.pesananfinalid', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'cekpesanandetailmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'customernama') {
            return $query->orderBy('customer.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'productnama') {
            return $query->orderBy('product.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'satuannama') {
            return $query->orderBy('satuan.nama', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'type') {
                            $query = $query->where('B.grp', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->where('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'customernama') {
                            $query = $query->where('customer.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->where('satuan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'cekpesanandetailmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("format(" . $this->table . "." . $filters['field'] . ", 'dd-MM-yyyy HH:mm:ss') LIKE '%$filters[data]%'");
                        } else {
                            // $query = $query->where($this->table . '.' . $filters['field'], 'like', "%$filters[data]%");
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'type') {
                            $query = $query->orWhere('B.grp', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->orWhereRaw("format(" . $this->table . "." . $filters['field'] . ", 'dd-MM-yyyy HH:mm:ss') LIKE '%$filters[data]%'");
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->orWhere('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'customernama') {
                            $query = $query->orWhere('customer.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'cekpesanandetailmemo') {
                            $query = $query->orWhere('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->orWhere('satuan.nama', 'like', "%$filters[data]%");
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

    public function processStore(array $data): CekPesanan
    {
        $cekPesanan = new CekPesanan();
        $cekPesanan->pesananfinalid = $data['pesananfinalid'];
        $cekPesanan->pesananfinaldetailid = $data['pesananfinaldetailid'];
        $cekPesanan->productid = $data['productid'];
        $cekPesanan->customerid = $data['customerid'];
        $cekPesanan->qty = $data['qty'];
        $cekPesanan->satuanid = $data['satuanid'];
        $cekPesanan->keterangan = $data['keterangan'] ?? '';
        $cekPesanan->cekpesanandetail = $data['cekpesanandetail'] ?? 0;
        $cekPesanan->modifiedby = $data['modifiedby'];
        $cekPesanan->save();

        if (!$cekPesanan->save()) {
            throw new \Exception("Error storing Cek Pesanan.");
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper('CEK PESANAN'),
            'postingdari' =>  strtoupper('ENTRY CEK PESANAN'),
            'idtrans' =>  $cekPesanan['id'],
            'nobuktitrans' => '',
            'aksi' => 'ENTRY',
            'datajson' => $data,
            'modifiedby' => auth('api')->user()->user,
        ]);

        return $cekPesanan;
    }

    public function processUpdateOLd(array $data)
    {
        $getCekDetailParams =  DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'CEK PESANAN DETAIL')
            ->where('subgrp', '=', 'CEK PESANAN DETAIL')
            ->where('DEFAULT', '=', 'YA')
            ->first();


        $updateData = [];
        if (!empty(array_filter($data))) {
            $updateData = [
                'id' => $data['id'],
                'pesananfinalid' => $data['pesananfinalid'],
                'pesananfinaldetailid' => $data['pesananfinaldetailid'],
                'customerid' => $data['customerid'],
                'productid' => $data['productid'],
                'qty' => $data['qty'],
                'satuanid' => $data['satuanid'],
                'keterangan' => $data['keterangan'],
                'cekpesanandetail' => $getCekDetailParams->id
            ];

            $cekPesananDetail =  DB::table("parameter")
                ->select('id', 'text')
                ->where('grp', '=', 'CEK PESANAN DETAIL')
                ->where('subgrp', '=', 'CEK PESANAN DETAIL')
                ->first();


            for ($i = 0; $i < count($updateData['id']); $i++) {
                $updateStatus = DB::table('pesananfinaldetail')
                    ->where('id', $updateData['pesananfinaldetailid'][$i])
                    ->update([
                        'cekpesanandetail' =>  $cekPesananDetail->id,
                    ]);

                DB::table('cekpesanan')
                    ->where('id', $updateData['id'][$i])
                    ->update([
                        'pesananfinalid' => $updateData['pesananfinalid'][$i],
                        'customerid' => $updateData['customerid'][$i],
                        'productid' => $updateData['productid'][$i],
                        'qty' => $updateData['qty'][$i],
                        'satuanid' => $updateData['satuanid'][$i],
                        'keterangan' => $updateData['keterangan'][$i],
                        'cekpesanandetail' => $updateData['cekpesanandetail']
                    ]);
            }
            // die;
        } else {
           
            DB::table('cekpesanan')
                ->update(['cekpesanandetail' => 0]);
        }
        $ids = $data['id'];

        $cekPesanan = new CekPesanan();
        $cekPesanan = $cekPesanan->get();

        $id = $cekPesanan->pluck('id');

        $idArray = $id->map(function ($value) {
            return (int)$value;
        })->toArray();

        if ($ids != null) {
            $ids = array_map('intval', $ids);
         
            $missingIds = array_diff($idArray, $ids);
        }

        if (!empty($missingIds)) {
            $query = DB::table('cekpesanan')
                ->whereIn('id', $missingIds)
                ->update(['cekpesanandetail' => 0]);
        }
        return $updateData;
    }

    public function processUpdateOld2(array $data)
    {
    //    dd($data);
        $getCekDetailParams =  DB::table("parameter")
        ->select('id', 'text')
        ->where('grp', '=', 'CEK PESANAN DETAIL')
        ->where('subgrp', '=', 'CEK PESANAN DETAIL')
        ->where('DEFAULT', '=', 'YA')
        ->first();

        $tempHeader = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempHeader (
            id INT UNSIGNED NULL,
            pesananfinalid INT,
            pesananfinaldetailid INT,
            productid INT,
            customerid INT,
            qty VARCHAR(255),
            satuanid INT,
            keterangan VARCHAR(500),
            cekpesanandetail INT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        )");

        $updateStatus = true;

        $cekPesananDetail =  DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'CEK PESANAN DETAIL')
            ->where('subgrp', '=', 'CEK PESANAN DETAIL')
            ->first();

        for ($i = 0; $i < count($data['productid']); $i++) {
           DB::table($tempHeader)->insert([
                'id' => $data['id'][$i],
                'pesananfinalid' => $data['pesananfinalid'][$i],
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
                'productid' => $data['productid'][$i],
                'customerid' => $data['customerid'][$i],
                'qty' => $data['qty'][$i],
                'satuanid' => $data['satuanid'][$i],
                'keterangan' => $data['keterangan'][$i],
                'cekpesanandetail' =>  $getCekDetailParams->id,
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

        }
        // update status
        $updateStatus = DB::table('pesananfinaldetail as a')
                        ->join("$tempHeader as b", 'a.id', '=', 'b.pesananfinaldetailid')
                        ->update([
                            'a.cekpesanandetail' =>  $cekPesananDetail->id,
                        ]);

        $deleteStatus = DB::table('pesananfinaldetail as a')
                        ->leftJoin("$tempHeader as b", 'a.id', '=', 'b.pesananfinaldetailid')
                        ->whereNull('b.id')
                        ->update([
                            'a.cekpesanandetail' =>  0,
                        ]);

        // dd(DB::table($tempHeader)->get());

        // update status cek pesanan
        $queryUpdate = DB::table('cekpesanan as a')
        ->join("$tempHeader as b", 'a.pesananfinaldetailid', '=', 'b.pesananfinaldetailid')
        ->update([
            'a.pesananfinalid' => DB::raw('b.pesananfinalid'),
            'a.productid' => DB::raw('b.productid'),
            'a.customerid' => DB::raw('b.customerid'),
            'a.qty' => DB::raw('b.qty'),
            'a.satuanid' => DB::raw('b.satuanid'),
            'a.keterangan' => DB::raw('b.keterangan'),
            'a.cekpesanandetail' => DB::raw('b.cekpesanandetail'),
            'a.modifiedby' => DB::raw('b.modifiedby'),
            'a.created_at' => DB::raw('b.created_at'),
            'a.updated_at' => DB::raw('b.updated_at')
        ]);

        // dd($queryUpdate);

        $queryInsert = DB::table("$tempHeader as a")
                ->select(
                    'a.pesananfinalid',
                    'a.pesananfinaldetailid',
                    'a.productid',
                    'a.customerid',
                    'a.qty',
                    'a.satuanid',
                    'a.keterangan',
                    'a.cekpesanandetail',
                    'a.modifiedby',
                    'a.created_at',
                    'a.updated_at'
                )
                ->leftJoin("pesananfinaldetail as b", 'b.id', '=', 'a.pesananfinaldetailid')
                ->leftJoin("cekpesanan as c", 'c.pesananfinaldetailid', '=', 'a.pesananfinaldetailid')
                ->whereNull('c.pesananfinaldetailid');

        // dd($queryInsert->get());

        $insert = DB::table('cekpesanan')->insertUsing(["pesananfinalid", "pesananfinaldetailid","productid", "customerid", "qty",  "satuanid", "keterangan","cekpesanandetail",  "modifiedby", "created_at", "updated_at"], $queryInsert);

        // dd($insert);

        $delete = DB::table('cekpesanan as a')
        ->leftJoin("$tempHeader as b", 'a.pesananfinaldetailid', '=', 'b.pesananfinaldetailid')
        ->whereNull('b.id')->delete();
        // ->update([
        //     'a.cekpesanandetail' => DB::raw('b.cekpesanandetail'),
        // ]);

        // dd($delete);


        return $data;
    }

    public function processUpdate(array $data)
    {
        $getCekDetailParams =  DB::table("parameter")
        ->select('id', 'text')
        ->where('grp', '=', 'CEK PESANAN DETAIL')
        ->where('subgrp', '=', 'CEK PESANAN DETAIL')
        ->where('DEFAULT', '=', 'YA')
        ->first();

        $tempHeader = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempHeader (
            id INT UNSIGNED NULL,
            pesananfinalid INT,
            pesananfinaldetailid INT,
            productid INT,
            customerid INT,
            qty VARCHAR(255),
            satuanid INT,
            keterangan VARCHAR(500),
            cekpesanandetail INT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        )");

        $updateStatus = true;

        $cekPesananDetail =  DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'CEK PESANAN DETAIL')
            ->where('subgrp', '=', 'CEK PESANAN DETAIL')
            ->first();

        for ($i = 0; $i < count($data['productid']); $i++) {
           DB::table($tempHeader)->insert([
                'id' => $data['id'][$i],
                'pesananfinalid' => $data['pesananfinalid'][$i],
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
                'productid' => $data['productid'][$i],
                'customerid' => $data['customerid'][$i],
                'qty' => $data['qty'][$i],
                'satuanid' => $data['satuanid'][$i],
                'keterangan' => $data['keterangan'][$i],
                'cekpesanandetail' =>  $getCekDetailParams->id,
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

        }
        
        $updateStatus = DB::table('pesananfinaldetail as a')
            ->join("$tempHeader as b", 'a.id', '=', 'b.pesananfinaldetailid')
            ->update([
                'a.keterangan' =>  DB::raw('b.keterangan'),
                'a.cekpesanandetail' =>  $cekPesananDetail->id,
            ]);

        // dd($updateStatus);

        $deleteStatus = DB::table('pesananfinaldetail as a')
                ->leftJoin("$tempHeader as b", 'a.id', '=', 'b.pesananfinaldetailid')
                ->whereNull('b.id')
                ->update([
                    'a.keterangan' =>  DB::raw('b.keterangan'),
                    'a.cekpesanandetail' =>  0,
                ]);

        return $data;
    }

    public function processData($data)
    {
        $pesananFinalIds = [];
        $pesananfinaldetailids = [];
        $customerIds = [];
        $productIds = [];
        $qtys = [];
        $satuanIds = [];
        $keterangans = [];
        $ids = [];
        $cekpesanandetail = [];
        $getId = DB::table("cekpesanan")->select("id");
        foreach ($data as $detail) {
            $ids[] = $detail['id'];
            $pesananFinalIds[] = $detail['pesananfinalid'];
            $pesananfinaldetailids[] = $detail['pesananfinaldetailid'];
            $customerIds[] = $detail['customerid'];
            $productIds[] = $detail['productid'];
            $qtys[] = $detail['qty'];
            $satuanIds[] = $detail['satuanid'];
            $keterangans[] = $detail['keterangan'];
            $cekpesanandetail[] = $detail['cekpesanandetail'];
        }

        $data = [
            "id" => $ids,
            "pesananfinalid" =>  $pesananFinalIds,
            "pesananfinaldetailid" => $pesananfinaldetailids,
            "customerid" =>  $customerIds,
            "productid" =>  $productIds,
            "qty" =>  $qtys,
            "satuanid" =>  $satuanIds,
            "keterangan" => $keterangans,
            "cekpesanandetail" => $cekpesanandetail,
        ];

        return $data;
    }

    public function findPenjualan()
    {
        $pesananfinalHeader = new PesananFinalHeader();
        $tglpengiriman = date('Y-m-d', strtotime(request()->tglpengirimanjual));
        // $namaKaryawan = auth('api')->user()->name;
        $data = DB::table("pesananfinalheader")
            ->select('pesananfinalheader.id')
            ->where('pesananfinalheader.tglpengiriman', $tglpengiriman)
            ->where('pesananfinalheader.status', 1)
            // ->where('pesananfinalheader.nobuktipenjualan', '')
            ->pluck('id')
            ->toArray();

        $pesananfinalHeader->setRequestParameters();
        $headers = DB::table('pesananfinalheader')
            ->select(
                "pesananfinalheader.id as id",
                "pesananfinalheader.nobukti as pesananfinalnobukti",
                "customer.id as customerid",
                "customer.nama as customernama",
                "customer.nama2 as customernama2",
                "pesananfinalheader.nobukti",
                "pesananfinalheader.tglbukti",
                "pesananfinalheader.nobuktipenjualan",
                "pesananfinalheader.tglbuktipesanan",
                "pesananfinalheader.tglpengiriman",
                "pesananfinalheader.alamatpengiriman",
                "pesananfinalheader.keterangan",
                "pesananheader.nobukti as pesanannobukti",
                DB::raw('IFNULL(pesananfinalheader.servicetax, 0) AS servicetax'),
                DB::raw('IFNULL(pesananfinalheader.tax, 0) AS tax'),
                DB::raw('IFNULL(pesananfinalheader.taxamount, 0) AS taxamount'),
                DB::raw('IFNULL(pesananfinalheader.discount, 0) AS discount'),
                DB::raw('IFNULL(pesananfinalheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(pesananfinalheader.total, 0) AS total'),
                "parameter.id as status",
                "parameter.text as statusnama",
                "cekpesanan.id as cekpesanan",
                "cekpesanan.text as cekpesanannama",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pesananfinalheader.created_at',
                'pesananfinalheader.updated_at',
                "pesananheader.id as pesananid",
            )
            ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananfinalheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as cekpesanan"), 'pesananfinalheader.cekpesanan', 'cekpesanan.id')
            ->leftJoin(DB::raw("pesananheader"), 'pesananfinalheader.pesananid', 'pesananheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananfinalheader.modifiedby', 'modifier.id')
            ->whereIn('pesananfinalheader.id', $data);

        $pesananfinalHeader->totalRows = $headers->count();
        $pesananfinalHeader->totalPages = request()->limit > 0 ? ceil($pesananfinalHeader->totalRows / request()->limit) : 1;

        $pesananfinalHeader->sort($headers);
        $pesananfinalHeader->filter($headers);
        $pesananfinalHeader->paginate($headers);

        $headers = $headers->get();

        // dd($headers);

        $details = DB::table('pesananfinaldetail')
            ->select(
                "pesananfinaldetail.id as pesananfinaldetailid",
                "pesananfinalheader.id as pesananfinalid",
                "pesananfinalheader.nobukti as pesananfinalheadernobukti",
                "product.id as productid",
                "product.nama as productnama",
                "pesananfinaldetail.nobuktipembelian",
                DB::raw('IFNULL(pesananfinaldetail.keterangan, "") AS keterangandetail'),
                DB::raw('IFNULL(cekpesanan.keterangan, "") AS keterangancekpesanan'),
                "pesananfinaldetail.qtyjual as qty",
                "pesananfinaldetail.qtyreturjual as qtyretur",
                "pesananfinaldetail.hargajual as harga",
                DB::raw('(pesananfinaldetail.qtyjual * pesananfinaldetail.hargajual) AS totalhargajual'),
                "pesananfinaldetail.nobuktipembelian",
                "pesananfinaldetail.cekpesanandetail",
                "satuan.nama as satuannama",
                "satuan.id as satuanid",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "pesananfinaldetail.created_at",
                "pesananfinaldetail.updated_at",
            )
            ->leftJoin(DB::raw("pesananfinalheader"), 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("product"), 'pesananfinaldetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'pesananfinaldetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("cekpesanan"), 'pesananfinaldetail.id', 'cekpesanan.pesananfinaldetailid')
            ->leftJoin(DB::raw("user as modifier"), 'pesananfinaldetail.modifiedby', 'modifier.id')
            ->whereIn('pesananfinaldetail.pesananfinalid', $data)
            ->orderBy('productnama', 'asc')
            ->get();

        // dd($details);
        // Menambahkan kondisi where jika karyawanid tidak kosong
        // if (request()->karyawannama != '') {

        //     $details->where('karyawan.nama', $namaKaryawan);
        // }

        // Mengambil hasil query dan menetapkannya kembali ke variabel $details
        // $details = $details->get();

        $groupedDetails = $details->groupBy('pesananfinalid');


        $result = $headers->map(function ($header) use ($groupedDetails) {
            return [
                'id' => 0,
                'nobukti' => "",
                'tglbukti' => $header->tglbukti,
                'customerid' => $header->customerid,
                'customernama' => $header->customernama,
                'alamatpengiriman' => $header->alamatpengiriman,
                'tglpengiriman' => $header->tglpengiriman,
                'nominalbayar' => 0,
                'keterangan' => $header->keterangan,
                'servicetax' => $header->servicetax,
                'tax' => $header->tax,
                'taxamount' => $header->taxamount,
                'discount' => $header->discount,
                'subtotal' => $header->subtotal,
                'total' => $header->total,
                'tglcetak' => "",
                'pesananfinalid' => $header->id,
                'nobuktipesananfinal' => $header->pesananfinalnobukti,
                'nobuktipesananheader' => $header->pesanannobukti,
                'statusnama' => $header->statusnama,
                'modifiedby' => $header->modifiedby,
                'modifiedby_name' => $header->modifiedby_name,
                'details' => $groupedDetails->get($header->id, []),
            ];
        });
        $data = $result->toArray();

        // dd($data);

        return $data;
    }

    public function findpesanandetail()
    {
        $pesananFinaldetail = new PesananFinalDetail();
        $pesananFinaldetail->setRequestParameters();

        $tglpengiriman = date('Y-m-d', strtotime(request()->periode));

        $query = DB::table('pesananfinaldetail')
            ->select(
                "pesananfinaldetail.id",
                "pesananfinalheader.id as pesananfinalid",
                "pesananfinalheader.nobukti as pesananfinalheadernobukti",
                "product.id as productid",
                "product.nama as productnama",
                "customer.id as customerid",
                "customer.nama as customernama",
                "pesananfinaldetail.nobuktipembelian",
                "pesananfinaldetail.keterangan as keterangandetail",
                // "tablecekpesanan.keterangan as keterangandetail",
                DB::raw('IFNULL(pesananfinaldetail.qtybeli, 0) AS qtybeli'),
                DB::raw('IFNULL(pesananfinaldetail.qtyjual, 0) AS qtyjual'),
                DB::raw('IFNULL(pesananfinaldetail.qtyreturjual, 0) AS qtyreturjual'),
                DB::raw('IFNULL(pesananfinaldetail.qtyreturbeli, 0) AS qtyreturbeli'),
                "pesananfinaldetail.hargajual",
                "pesananfinaldetail.hargabeli",
                DB::raw('(pesananfinaldetail.qtybeli * pesananfinaldetail.hargabeli) AS totalhargabeli'),
                DB::raw('(pesananfinaldetail.qtyjual * pesananfinaldetail.hargajual) AS totalhargajual'),
                "satuan.id as satuanid",
                "satuan.nama as satuannama",
                "cekpesanan.id as cekpesananid",
                "cekpesanan.memo as cekpesananmemo",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "pesananfinaldetail.created_at",
                "pesananfinaldetail.updated_at",
            )
            ->leftJoin(DB::raw("pesananfinalheader"), 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("product"), 'pesananfinaldetail.productid', 'product.id')
            ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("satuan"), 'pesananfinaldetail.satuanid', 'satuan.id')
            // ->leftJoin(DB::raw("cekpesanan as tablecekpesanan"), 'pesananfinaldetail.id', 'tablecekpesanan.pesananfinaldetailid')
            ->leftJoin(DB::raw("parameter as cekpesanan"), 'pesananfinaldetail.cekpesanandetail', 'cekpesanan.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananfinaldetail.modifiedby', 'modifier.id')
            ->where("pesananfinalheader.status", 1)
            ->where("pesananfinalheader.tglpengiriman", $tglpengiriman);
            

            if (request()->pesananfinalid != '') {
                $query->where("pesananfinaldetail.pesananfinalid", "=", request()->pesananfinalid);


                $data= $query->get();

                return $data;
            }

            $pesananFinaldetail->totalRows = $query->count();
            $pesananFinaldetail->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

           
            $pesananFinaldetail->sort($query);
           
            $pesananFinaldetail->filter($query);
            $pesananFinaldetail->paginate($query);


            $data = $query->get();

           

            return $data;
      
    }
}
