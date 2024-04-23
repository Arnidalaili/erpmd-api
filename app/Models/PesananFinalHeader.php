<?php

namespace App\Models;

use App\Http\Controllers\Api\ErrorController;
use App\Services\RunningNumberService;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class PesananFinalHeader extends MyModel
{
    use HasFactory;

    protected $table = 'pesananfinalheader';

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
        $query = DB::table($this->table . ' as pesananfinalheader')
            ->select(
                "pesananfinalheader.id",
                "customer.id as customerid",
                "customer.nama as customernama",
                "pesananfinalheader.nobukti",
                "pesananfinalheader.tglbukti",
                "pesananfinalheader.nobuktipenjualan",
                "pesananfinalheader.tglbuktipesanan",
                "pesananfinalheader.tglpengiriman",
                "pesananfinalheader.alamatpengiriman",
                "pesananfinalheader.keterangan",
                DB::raw('IFNULL(pesananfinalheader.servicetax, 0) AS servicetax'),
                DB::raw('IFNULL(pesananfinalheader.tax, 0) AS tax'),
                DB::raw('IFNULL(pesananfinalheader.taxamount, 0) AS taxamount'),
                DB::raw('IFNULL(pesananfinalheader.discount, 0) AS discount'),
                DB::raw('IFNULL(pesananfinalheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(pesananfinalheader.total, 0) AS total'),
                "pesananfinalheader.tglcetak",
                "pesananheader.id as pesananheaderid",
                "pesananheader.nobukti as nobuktipesanan",
                "pesananheader.tglbukti as tglbuktipesanan",
                "pesananheader.alamatpengiriman as alamatpengirimanpesanan",
                "pesananheader.tglpengiriman as tglpengirimanpesanan",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "cekpesanan.id as cekpesanan",
                "cekpesanan.memo as cekpesananmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pesananfinalheader.created_at',
                'pesananfinalheader.updated_at'
            )
            ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananfinalheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as cekpesanan"), 'pesananfinalheader.cekpesanan', 'cekpesanan.id')
            ->leftJoin(DB::raw("pesananheader"), 'pesananfinalheader.pesananid', 'pesananheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananfinalheader.modifiedby', 'modifier.id');
        // ->where("parameter.id", 1);

        // dd($query->get());

        if (request()->periode) {
            $tglpengiriman = date('Y-m-d', strtotime(request()->periode));
            $query->where('pesananfinalheader.tglpengiriman', '=', $tglpengiriman);
            $query->where('pesananfinalheader.tglpengiriman', '=', $tglpengiriman);
        }

       

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);
        $this->filter($query);

        if (!request()->ceklist) {
            $this->paginate($query);
        }
        $data = $query->get();
        return $data;
    }

    public function findAll($id)
    {
        $query = DB::table('pesananfinalheader')
            ->select(
                "pesananfinalheader.id",
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
                DB::raw('IFNULL(pesananfinalheader.servicetax, 0) AS servicetax'),
                DB::raw('IFNULL(pesananfinalheader.tax, 0) AS tax'),
                DB::raw('IFNULL(pesananfinalheader.taxamount, 0) AS taxamount'),
                DB::raw('IFNULL(pesananfinalheader.discount, 0) AS discount'),
                DB::raw('IFNULL(pesananfinalheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(pesananfinalheader.total, 0) AS total'),
                "pesananfinalheader.tglcetak",
                "pesananheader.id as pesananheaderid",
                "pesananheader.nobukti as nobuktipesanan",
                "pesananheader.tglbukti as tglbuktipesanan",
                "pesananheader.alamatpengiriman as alamatpengirimanpesanan",
                "pesananheader.tglpengiriman as tglpengirimanpesanan",
                "parameter.id as status",
                "parameter.text as statusnama",
                "parameter.memo as statusmemo",
                "cekpesanan.id as cekpesanan",
                "cekpesanan.text as cekpesanannama",
                "cekpesanan.memo as cekpesananmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pesananfinalheader.created_at',
                'pesananfinalheader.updated_at'
            )
            ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananfinalheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as cekpesanan"), 'pesananfinalheader.cekpesanan', 'cekpesanan.id')
            ->leftJoin(DB::raw("pesananheader"), 'pesananfinalheader.pesananid', 'pesananheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananfinalheader.modifiedby', 'modifier.id')
            ->where('pesananfinalheader.id', $id);
        $data = $query->first();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pesananfinalheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(pesananfinalheader.nobukti, '/', 1)"), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'customernama') {
            return $query->orderBy('customer.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'cekpesananmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobuktipesanan') {
            return $query->orderBy('pesananheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'modifiedby_name') {
            return $query->orderBy('modifier.name', $this->params['sortOrder']);
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
                        if ($filters['field'] != '') {

                            if ($filters['field'] == 'customernama') {
                                $query = $query->where('customer.nama', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'statusmemo') {
                                $query = $query->where('parameter.text', '=', "$filters[data]");
                            } else if ($filters['field'] == 'nobuktipesanan') {
                                $query = $query->where('pesananheader.nobukti', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'cekpesananmemo') {
                                $query = $query->where('cekpesanan.memo', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'modifiedby_name') {
                                $query = $query->where('modifier.name', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglpengiriman' || $filters['field'] == 'tglbukti') {
                                $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else {
                                // $query = $query->where($this->table . '.' . $filters['field'], 'like', "%$filters[data]%");
                                $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                            }
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] != '') {
                            if ($filters['field'] == 'customernama') {
                                $query = $query->orWhere('customer.nama', 'LIKE', "%$filters[data]%");
                            } else if ($filters['field'] == 'statusmemo') {
                                $query = $query->orWhere('parameter.text', '=', "$filters[data]");
                            } else if ($filters['field'] == 'nobuktipesanan') {
                                $query = $query->orWhere('pesananheader.nobukti', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'cekpesananmemo') {
                                $query = $query->orWhere('cekpesanan.memo', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'modifiedby_name') {
                                $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                            } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglpengiriman' || $filters['field'] == 'tglbukti') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else {
                                // $query = $query->orWhere($this->table . '.' . $filters['field'], 'LIKE', "%$filters[data]%");
                                $query = $query->OrwhereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                            }
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
                $this->table.nobukti,
                $this->table.tglbukti,
                $this->table.nobuktipenjualan,
                $this->table.tglbuktipesanan,
                $this->table.tglpengiriman,
                $this->table.alamatpengiriman,
                $this->table.keterangan,
                $this->table.servicetax,
                $this->table.tax,
                $this->table.taxamount,
                $this->table.discount,
                $this->table.subtotal,
                $this->table.total,
                $this->table.tglcetak,
                pesananheader.id as pesananheaderid,
                pesananheader.nobukti as nobuktipesanan,
                pesananheader.tglbukti as tglbuktipesananheader,
                pesananheader.alamatpengiriman as alamatpengirimanpesananheader,
                pesananheader.tglpengiriman as tglpengirimanpesananheader,
                parameter.id as status,
                parameter.text as statusnama,
                parameter.memo as statusmemo,
                cekpesanan.id as cekpesanan,
                cekpesanan.text as cekpesanannama,
                cekpesanan.memo as cekpesananmemo,
                modifier.id as modifiedby,
                modifier.name as modifiedby_name,
                $this->table.created_at,
                $this->table.updated_at
            ")
            )
            ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'pesananfinalheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as cekpesanan"), 'pesananfinalheader.cekpesanan', 'cekpesanan.id')
            ->leftJoin(DB::raw("pesananheader"), 'pesananfinalheader.pesananid', 'pesananheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'pesananfinalheader.modifiedby', 'modifier.id');
    }


    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->selectColumns($query);
        $query = $this->filter($query);

        if (request()->periode) {
            $tglpengiriman = date('Y-m-d', strtotime(request()->periode));

            $query->where('pesananfinalheader.tglpengiriman', '=', $tglpengiriman);
        }

        $query = $this->sort($query);
        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            customerid INT,
            customernama VARCHAR(100),
            nobukti VARCHAR(100),
            tglbukti DATETIME,
            nobuktipenjualan VARCHAR(100) NULL,
            tglbuktipesanan DATETIME NULL,
            tglpengiriman DATETIME,
            alamatpengiriman VARCHAR(500),
            keterangan VARCHAR(500),
            servicetax VARCHAR(500),
            tax VARCHAR(500),
            taxamount VARCHAR(500),
            discount VARCHAR(500),
            subtotal VARCHAR(500),
            total VARCHAR(500),
            tglcetak DATETIME,
            pesananheaderid INT,
            nobuktipesanan VARCHAR(500),
            tglbuktipesananheader DATETIME,
            alamatpengirimanpesananheader VARCHAR(500),
            tglpengirimanpesananheader VARCHAR(500),
            status INT,
            statusnama VARCHAR(500),
            statusmemo VARCHAR(500),
            cekpesanan INT,
            cekpesanannama VARCHAR(500),
            cekpesananmemo VARCHAR(500),
            modifiedby VARCHAR(255),
            modifiedby_name VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");
        DB::table($temp)->insertUsing(["id", "customerid", "customernama", "nobukti", "tglbukti", "nobuktipenjualan", "tglbuktipesanan", "tglpengiriman", "alamatpengiriman", "keterangan", "servicetax", "tax", "taxamount", "discount", "subtotal", "total", "tglcetak", "pesananheaderid", "nobuktipesanan", "tglbuktipesananheader", "alamatpengirimanpesananheader", "tglpengirimanpesananheader", "status", "statusnama", "statusmemo", "cekpesanan", "cekpesanannama", "cekpesananmemo", "modifiedby", "modifiedby_name", "created_at", "updated_at"], $query);
        return $temp;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status INT NULL,
            statusnama VARCHAR(100),
            tax VARCHAR(100)
        )");

        $status = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        $tax = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'tax')
            ->where('subgrp', '=', 'tax') 
            ->first();


        DB::statement("INSERT INTO $tempdefault (status,statusnama,tax) VALUES (?,?,?)", [$status->id, $status->text, $tax->text]);

        $query = DB::table(
            $tempdefault
        )
            ->select(
                'status',
                'statusnama',
                'tax'
            );

        $data = $query->first();
        return $data;
    }

    public function processStoreCombain($pesananfinalid)
    {
        $pesananfinalheaderData = [];
        foreach ($pesananfinalid as $id) {
            $pesananfinalheader = DB::table('pesananfinalheader')
                ->select(
                    "pesananfinalheader.id",
                    "customer.id as customerid",
                    "customer.nama as customernama",
                    "pesananfinalheader.nobukti",
                    "pesananfinalheader.tglbukti",
                    "pesananfinalheader.nobuktipenjualan",
                    "pesananfinalheader.tglbuktipesanan",
                    "pesananfinalheader.tglpengiriman",
                    "pesananfinalheader.alamatpengiriman",
                    "pesananfinalheader.keterangan",
                    DB::raw('IFNULL(pesananfinalheader.servicetax, 0) AS servicetax'),
                    DB::raw('IFNULL(pesananfinalheader.tax, 0) AS tax'),
                    DB::raw('IFNULL(pesananfinalheader.taxamount, 0) AS taxamount'),
                    DB::raw('IFNULL(pesananfinalheader.discount, 0) AS discount'),
                    DB::raw('IFNULL(pesananfinalheader.subtotal, 0) AS subtotal'),
                    DB::raw('IFNULL(pesananfinalheader.total, 0) AS total'),
                    "pesananfinalheader.tglcetak",
                    "pesananheader.id as pesananheaderid",
                    "pesananheader.nobukti as nobuktipesanan",
                    "pesananheader.tglbukti as tglbuktipesanan",
                    "pesananheader.alamatpengiriman as alamatpengirimanpesanan",
                    "pesananheader.tglpengiriman as tglpengirimanpesanan",
                    "parameter.id as status",
                    "parameter.memo as statusmemo",
                    'modifier.id as modifiedby',
                    'modifier.name as modifiedby_name',
                    'pesananfinalheader.created_at',
                    'pesananfinalheader.updated_at'
                )
                ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
                ->leftJoin(DB::raw("parameter"), 'pesananfinalheader.status', 'parameter.id')
                ->leftJoin(DB::raw("pesananheader"), 'pesananfinalheader.pesananid', 'pesananheader.id')
                ->leftJoin(DB::raw("user as modifier"), 'pesananfinalheader.modifiedby', 'modifier.id')
                ->where('pesananfinalheader.id', $id)
                ->first();

            if ($pesananfinalheader) {
                $customerid = $pesananfinalheader->customerid;
                $pesananfinalheaderData[$customerid][] = $pesananfinalheader;

                $pesananfinaldetail = DB::table('pesananfinaldetail')
                    ->where('pesananfinalid', $id)
                    ->get();

                if ($pesananfinaldetail->isNotEmpty()) {
                    $pesananfinaldetailData[$id] = $pesananfinaldetail;
                }
            }

            $getCombine =  DB::table("parameter")
                ->select('id', 'text')
                ->where('grp', '=', 'STATUS2')
                ->where('subgrp', '=', 'STATUS2')
                ->where('text', '=', 'COMBINE')
                ->where('DEFAULT', '=', 'YA')
                ->first();

            if ($pesananfinalheader->nobuktipesanan) {
                DB::table('pesananheader')
                    ->where('nobukti', $pesananfinalheader->nobuktipesanan)
                    ->update(['status2' => $getCombine->id]);
            }
        }

        if (!empty($pesananfinalheaderData) && !empty($pesananfinaldetailData)) {
            $firstHeader = reset($pesananfinalheaderData);
            $combainHeader = reset($firstHeader);
            $combainDetail = collect();
            foreach ($pesananfinaldetailData as $collection) {
                $combainDetail = $combainDetail->merge($collection);
            }

            $combine = DB::table("parameter")
                ->select('id', 'text')
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'NON AKTIF')
                ->first();

            DB::table('pesananfinalheader')
                ->whereIn('id', $pesananfinalid)
                ->update(['status' => $combine->id]);
        }

        $data = [
            "tglbukti" => $combainHeader->tglbukti,
            "customerid" => $combainHeader->customerid,
            "alamatpengiriman" => $combainHeader->alamatpengiriman,
            "tglpengiriman" => $combainHeader->tglpengiriman,
            "tglbuktipesanan" => $combainHeader->tglbuktipesanan,
            "keterangan" => $combainHeader->keterangan,
            "status" => $combainHeader->status,
            "productid" => [],
            "satuanid" => [],
            "qtyjual" => [],
            "discount" => 0,
            "keterangandetail" => [],
            "hargajual" => []
        ];

        $subTotal = 0;
        foreach ($combainDetail as $detail) {

            $data['productid'][] = strval($detail->productid);
            $data['satuanid'][] = strval($detail->satuanid);
            $data['qtyjual'][] = strval($detail->qtyjual);
            $data['keterangandetail'][] = strval($detail->keterangan);
            $data['hargajual'][] = strval($detail->hargajual);
            $data['totalharga'][] = strval($detail->qtyjual) * strval($detail->hargajual);
            $subTotal += strval($detail->qtyjual) * strval($detail->hargajual);
            $data['subtotal'] = $subTotal;
        }

        $tax = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'tax')
            ->where('subgrp', '=', 'tax')
            ->first();

        $taxText = $tax->text;
        $taxAmount = ($taxText / 100) * $subTotal;
        $total = $taxAmount + $subTotal;
        $data['tax'] = $taxText;
        $data['taxamount'] = $taxAmount;
        $data['total'] = $total;

        // dd($data);
        return $data;
    }

    public function processStore(array $data): PesananFinalHeader
    {
        $pesananFinalHeader = new PesananFinalHeader();

        /*STORE HEADER*/
        $group = 'PESANAN FINAL HEADER BUKTI';
        $subGroup = 'PESANAN FINAL HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();
        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));
        $tglpengiriman = date('Y-m-d', strtotime($data['tglpengiriman']));
        $tglbuktipesanan = date('Y-m-d', strtotime($data['tglbuktipesanan']));

        $pesananFinalHeader->tglbukti = $tglbukti;
        $pesananFinalHeader->customerid = $data['customerid'];
        $pesananFinalHeader->nobuktipenjualan = $data['nobuktipenjualan'] ?? '';
        $pesananFinalHeader->pesananid = $data['pesananid'] ?? 0;
        $pesananFinalHeader->alamatpengiriman = $data['alamatpengiriman'];
        $pesananFinalHeader->tglpengiriman = $tglpengiriman;
        $pesananFinalHeader->cekpesanan = 0;

        if ($tglbuktipesanan && $tglbuktipesanan != "1970-01-01") {
            $pesananFinalHeader->tglbuktipesanan = $tglbuktipesanan;
        } else {
            $pesananFinalHeader->tglbuktipesanan = null;
        }

        $pesananFinalHeader->tglbuktipesanan = $tglbuktipesanan ?? null;
        $pesananFinalHeader->keterangan = $data['keterangan'] ?? '';
        $pesananFinalHeader->discount = $data['discount'] ?? 0;
        $pesananFinalHeader->servicetax = $data['servicetax'] ?? 0;
        $pesananFinalHeader->tax = $data['tax'] ?? 0;
        $pesananFinalHeader->taxamount = $data['taxamount'] ?? 0;
        $pesananFinalHeader->subtotal = $data['subtotal'] ?? 0;
        $pesananFinalHeader->total = $data['total'] ?? 0;
        $pesananFinalHeader->status = $data['status'] ?? 1;
        $pesananFinalHeader->modifiedby = auth('api')->user()->id;

        $pesananFinalHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $pesananFinalHeader->getTable(), date('Y-m-d', strtotime($data['tglbukti'])));

        if (!$pesananFinalHeader->save()) {
            throw new \Exception("Error storing pesanan final header.");
        }

        $pesananFinalHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pesananFinalHeader->getTable()),
            'postingdari' => strtoupper('ENTRY PESANAN FINAL'),
            'idtrans' => $pesananFinalHeader->id,
            'nobuktitrans' => $pesananFinalHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pesananFinalHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);
        $pesananDetails = [];

        for ($i = 0; $i < count($data['productid']); $i++) {

            $getHarga = DB::table("product")
                ->select('hargajual', 'hargabeli')
                ->where('id', '=', $data['productid'][$i])
                ->first();

            $pesananFinalDetail = (new PesananFinalDetail())->processStore($pesananFinalHeader, [
                'pesananfinalid' => $pesananFinalHeader->id,
                'customerid' => $pesananFinalHeader->customerid,
                'productid' => $data['productid'][$i],
                'nobuktipembelian' => $data['nobuktipembelian'][$i] ?? '',
                'keterangan' => $data['keterangandetail'][$i] ?? '',
                'qtyjual' => $data['qtyjual'][$i] ?? 0,
                'qtybeli' => $data['qtybeli'][$i] ?? $data['qtyjual'][$i],
                'qtyreturjual' => $data['qtyreturjual'][$i] ?? 0,
                'qtyreturbeli' => $data['qtyreturbeli'][$i] ?? 0,
                'hargajual' => $data['hargajual'][$i] ?? 0,
                'hargabeli' => $data['hargabeli'][$i] ?? $getHarga->hargabeli,
                'satuanid' => $data['satuanid'][$i] ?? '',
                'cekpesanandetail' => $data['cekpesanandetail'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
            ]);
            $pesananDetails[] = $pesananFinalDetail->toArray();
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($pesananFinalHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY Pesanan Final Detail'),
            'idtrans' =>  $pesananFinalHeaderLogTrail->id,
            'nobuktitrans' => $pesananFinalHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pesananFinalDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);
        return $pesananFinalHeader;
    }

    public function processUpdate(PesananFinalHeader $pesananFinalHeader, array $data): PesananFinalHeader
    {
        $tempTableDefinition = "
            id INT UNSIGNED NULL,
            pesananfinalid INT NULL,
            productid INT NULL,
            nobuktipembelian VARCHAR(500),
            keterangan VARCHAR(500),
            qtyjual FLOAT,
            qtybeli FLOAT,
            qtyreturjual FLOAT,
            qtyreturbeli FLOAT,
            hargajual FLOAT,
            hargabeli FLOAT,
            satuanid INT NULL,
            cekpesanandetail INT NULL,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        ";

        $tempDetail = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
       
        DB::statement("CREATE TEMPORARY TABLE $tempDetail ($tempTableDefinition)");

        $nobuktiOld = $pesananFinalHeader->nobukti;

        $group = 'PESANAN FINAL HEADER BUKTI';
        $subGroup = 'PESANAN FINAL HEADER BUKTI';
        $tglpengiriman = date('Y-m-d', strtotime($data['tglpengiriman']));
        $tglbuktipesanan = date('Y-m-d', strtotime($data['tglbuktipesanan']));

        // get no bukti pesanan
        $getNobuktiPesanan = DB::table("pesananheader")
            ->select('id')
            ->where('nobukti', '=', $data['nobuktipesanan'])
            ->first();

        $getModified =  DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS2')
            ->where('subgrp', '=', 'STATUS2')
            ->where('text', '=', 'MODIFIED')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        if ($data['nobuktipesanan']) {
            DB::table('pesananheader')
                ->where('nobukti', $data['nobuktipesanan'])
                ->update(['status2' => $getModified->id]);
        }

        $getNobuktiPenjualan = DB::table("penjualanheader")
            ->select('nobukti')
            ->where('pesananfinalid', '=', $pesananFinalHeader->id)
            ->first();


        $pesananFinalHeader->customerid = $data['customerid'];
        $pesananFinalHeader->nobuktipenjualan = $getNobuktiPenjualan->nobukti ?? '';
        $pesananFinalHeader->pesananid = $getNobuktiPesanan->id ?? 0;
        $pesananFinalHeader->alamatpengiriman = $data['alamatpengiriman'];
        $pesananFinalHeader->tglpengiriman = $tglpengiriman;
        $pesananFinalHeader->tglbuktipesanan = $tglbuktipesanan ?? '';
        $pesananFinalHeader->keterangan = $data['keterangan'] ?? '';
        $pesananFinalHeader->discount = $data['discount'] ?? 0;
        $pesananFinalHeader->subtotal = $data['subtotal'] ?? 0;
        $pesananFinalHeader->total = $data['total'] ?? 0;
        $pesananFinalHeader->servicetax = $data['servicetax'] ?? 0;
        $pesananFinalHeader->tax = $data['tax'] ?? 0;
        $pesananFinalHeader->taxamount = $data['taxamount'] ?? 0;
        $pesananFinalHeader->status = $data['status'] ?? 1;
        $pesananFinalHeader->cekpesanan = 0;
        $pesananFinalHeader->modifiedby = auth('api')->user()->id;

        if (!$pesananFinalHeader->save()) {
            throw new \Exception("Error updating Pesanan Final Header.");
        }

        $pesananFinalHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pesananFinalHeader->getTable()),
            'postingdari' => strtoupper('EDIT PESANAN FINAL HEADER'),
            'idtrans' => $pesananFinalHeader->id,
            'nobuktitrans' => $pesananFinalHeader->nobukti,
            'aksi' => 'EDIT',
            'datajson' => $pesananFinalHeader->toArray(),
            'modifiedby' => auth('api')->user()->id
        ]);

        for ($i = 0; $i < count($data['productid']); $i++) {
            $getHarga = DB::table("product")
                ->select('hargajual', 'hargabeli')
                ->where('id', '=', $data['productid'][$i])
                ->first();

            DB::table($tempDetail)->insert([
                'id' => $data['id'][$i],
                'pesananfinalid' => $pesananFinalHeader->id,
                'productid' => $data['productid'][$i],
                'keterangan' => $data['keterangandetail'][$i] ?? '',
                'qtyjual' => $data['qtyjual'][$i] ?? 0,
                'qtybeli' => $data['qtybeli'][$i] ?? $data['qtyjual'][$i],
                'qtyreturjual' => $data['qtyreturjual'][$i] ?? 0,
                'qtyreturbeli' => $data['qtyreturbeli'][$i] ?? 0,
                'satuanid' =>   $data['satuanid'][$i] ?? '',
                'hargajual' => $data['hargajual'][$i] ?? 0,
                'hargabeli' => $getHarga->hargabeli,
                'cekpesanandetail' => 0,
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // dd(DB::table($tempDetail)->get());
        // penyesuaian qty
        DB::table("pesananfinaldetail as a")
            ->leftjoin("$tempDetail as b", 'a.id', '=', 'b.id')
            ->where('b.qtyjual', '!=', DB::raw('a.qtyjual'))
            ->update([
                'a.qtyjual' => DB::raw('b.qtyjual'),
                'a.qtybeli' => DB::raw('b.qtyjual')
            ]);

        // update harga beli jika ganti product
        DB::table("pesananfinaldetail as a")
            ->leftjoin("$tempDetail as b", 'a.id', '=', 'b.id')
            ->where('b.productid', '!=', DB::raw('a.productid'))
            ->update([
                'a.hargajual' => DB::raw('b.hargajual'),
                'a.hargabeli' => DB::raw('b.hargabeli')
            ]);

        // update pesanan final detail
        DB::table('pesananfinaldetail as a')
            ->join("pesananfinalheader as b", 'a.pesananfinalid', '=', 'b.id')
            ->join("$tempDetail as c", 'a.id', '=', 'c.id')
            ->update([
                'a.pesananfinalid' => DB::raw('c.pesananfinalid'),
                'a.productid' => DB::raw('c.productid'),
                'a.keterangan' => DB::raw('c.keterangan'),
                'a.qtyjual' => DB::raw('c.qtyjual'),
                'a.qtyreturjual' => DB::raw('c.qtyreturjual'),
                'a.satuanid' => DB::raw('c.satuanid'),
                'a.hargajual' => DB::raw('c.hargajual'),
                'a.modifiedby' => DB::raw('c.modifiedby'),
                'a.created_at' => DB::raw('c.created_at'),
                'a.updated_at' => DB::raw('c.updated_at')
            ]);

        $deletedCekPesanan = DB::table("cekpesanan as a")
            ->leftjoin("$tempDetail as b", 'a.pesananfinaldetailid', '=', 'b.id')
            ->leftjoin("pesananfinaldetail as d", 'a.pesananfinaldetailid', '=', 'd.id')
            ->whereNull('b.id')
            ->where("d.pesananfinalid", '=', $pesananFinalHeader->id)
            ->delete();

        // dd($deletedCekPesanan->get());

        // delete pesanan final detail
        DB::table('pesananfinaldetail as a')
            ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.id')
            ->whereNull('b.id')
            ->where('a.pesananfinalid', "=", $pesananFinalHeader->id)
            ->delete();

        // insert pesanan final detail
        $insertAddRowQuery =  DB::table("$tempDetail as a")
            ->select(
                'a.id',
                'a.pesananfinalid',
                'a.productid',
                DB::raw("IFNULL(a.nobuktipembelian, '') AS nobuktipembelian"),
                'a.keterangan',
                'a.qtyjual',
                'a.qtyjual as qtybeli',
                DB::raw('IFNULL(a.qtyreturjual, 0) AS qtyreturjual'),
                DB::raw('IFNULL(a.qtyreturbeli, 0) AS qtyreturbeli'),
                DB::raw('IFNULL(a.hargajual, 0) AS hargajual'),
                DB::raw('IFNULL(b.hargabeli, 0) AS hargabeli'),
                'a.satuanid',
                'a.cekpesanandetail',
                'a.modifiedby',
                'a.created_at',
                'a.updated_at',
            )
            ->leftjoin("product as b", 'a.productid', '=', 'b.id')
            ->where("a.id", '=', '0');

        DB::table('pesananfinaldetail')->insertUsing(["id", "pesananfinalid", "productid", "nobuktipembelian", "keterangan", "qtyjual", "qtybeli", "qtyreturjual", "qtyreturbeli", "hargajual", "hargabeli", "satuanid", "cekpesanandetail",  "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

        return $pesananFinalHeader;
    }

    public function processDestroy($id, $postingDari = '', $data = ''): PesananFinalHeader
    {
        $pesananFinalHeader = PesananFinalDetail::where('pesananfinalid', '=', $id)->get();
        $dataDetail = $pesananFinalHeader->toArray();

        /*DELETE EXISTING FAKTUR PENJUALAN HEADER*/
        $pesananFinalHeader = new PesananFinalHeader();

        $getModified =  DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS2')
            ->where('subgrp', '=', 'STATUS2')
            ->where('text', '=', 'MODIFIED')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        // dd()
        if ($data['nobuktipesanan']) {
            DB::table('pesananheader')
                ->where('nobukti', $data['nobuktipesanan'])
                ->update(['status2' => $getModified->id]);
        }

        $pesananFinalHeader = $pesananFinalHeader->lockAndDestroy($id);

        $pesananFinalHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $pesananFinalHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $pesananFinalHeader->id,
            'nobuktitrans' => $pesananFinalHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $pesananFinalHeader->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        (new LogTrail())->processStore([
            'namatabel' => 'PESANANFINALDETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $pesananFinalHeaderLogTrail['id'],
            'nobuktitrans' => $pesananFinalHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->name
        ]);
        return $pesananFinalHeader;
    }

    public function getReport($id)
    {
        $this->setRequestParameters();
        $query = DB::table($this->table . ' as pesananfinalheader')
            ->select(
                "pesananfinalheader.id",
                "customer.id as customerid",
                "customer.nama as customernama",
                "pesananfinalheader.nobukti",
                "pesananfinalheader.tglbukti",
                "pesananfinalheader.tglpengiriman",
                "pesananfinalheader.alamatpengiriman",
                "pesananfinalheader.servicetax",
                "pesananfinalheader.tax",
                "pesananfinalheader.taxamount",
                "pesananfinalheader.discount",
                "pesananfinalheader.total",
                "pesananfinalheader.subtotal",
                "pesananfinalheader.tglcetak",
            )
            ->leftJoin(DB::raw("customer"), 'pesananfinalheader.customerid', 'customer.id')
            ->where('pesananfinalheader.id', $id);

        $data = $query->first();

        return $data;
    }

    public function updateTglCetak($data)
    {
        $tgl = date('Y-m-d', strtotime($data['tgldari']));
        $query = DB::table('pesananfinalheader')
            ->where('tglpengiriman', $tgl)
            ->update(['tglcetak' => date('Y-m-d')]);
        return $query;
    }

    public function cekValidasiAksiDelete($tglcetak, $id)
    {
        $pesananfinalheader = DB::table('pesananfinalheader')
            ->select(
                'pesananfinalheader.id',
                'pesananfinalheader.tglcetak',
                'pesananfinalheader.nobuktipenjualan'
            )
            ->where("pesananfinalheader.id", $id);

        if ($pesananfinalheader->first()->nobuktipenjualan != '') {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Pesanan Final Header ' . $pesananfinalheader->first()->nobuktipenjualan,
                'kodeerror' => 'TBDCPJ'
            ];
            goto selesai;
        }
        $data = [
            'kondisi' => false,
            'keterangan' => '',
        ];
        selesai:
        return $data;
    }

    public function cekValidasiAksiEdit($tglcetak, $id)
    {

        // $penjualan = DB::table('pesananfinalheader')
        // ->select('nobuktipenjualan')
        // ->where('id', $id)
        // ->first();

        // if ($penjualan->nobuktipenjualan !== "") {

        //     $piutang = DB::table('piutang')
        //         ->select(
        //             'penjualanid',
        //             'nominalbayar',
        //             'nominalpiutang',
        //             'nominalsisa'
        //         )
        //         ->leftJoin('penjualanheader', 'penjualanheader.id', 'piutang.penjualanid')
        //         ->leftJoin('pesananfinalheader', 'pesananfinalheader.id', 'penjualanheader.pesananfinalid')
        //         ->where("pesananfinalheader.id", $id)
        //         ->first();

        //     if ($piutang->nominalpiutang != $piutang->nominalsisa) {
        //         $data = [
        //             'kondisi' => true,
        //             'keterangan' => 'Pesanan Final Header ' . $piutang->penjualanid,
        //             'kodeerror' => 'TBEPFP'
        //         ];
        //         goto selesai;
        //     }

        //     $pesananfinalheader = DB::table('pesananfinaldetail')
        //         ->select(
        //             'pesananfinalheader.id',
        //             'pesananfinalheader.tglcetak',
        //             'pesananfinaldetail.nobuktipembelian'
        //         )
        //         ->leftJoin(DB::raw("pesananfinalheader"), 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
        //         ->where("pesananfinalheader.id", $id);
        //     if ($pesananfinalheader->first()->nobuktipembelian != '') {
        //         $data = [
        //             'kondisi' => true,
        //             'keterangan' => 'Pesanan Final Header ' . $pesananfinalheader->first()->nobuktipembelian,
        //             'kodeerror' => 'NBPBSA'
        //         ];
        //         goto selesai;
        //     }
        // }

        $pesananfinalheader = DB::table('pesananfinalheader')
            ->select(
                'pesananfinalheader.id',
                'pesananfinalheader.tglcetak',
                'pesananfinalheader.nobuktipenjualan'
            )
            ->where("pesananfinalheader.id", $id);

        if ($pesananfinalheader->first()->nobuktipenjualan != '') {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Pesanan Final Header ' . $pesananfinalheader->first()->nobuktipenjualan,
                'kodeerror' => 'TBECPJ'
            ];
            goto selesai;
        }

        $data = [
            'kondisi' => false,
            'keterangan' => '',
        ];
        selesai:
        return $data;
    }

    public function getAcos($method, $username)
    {
        $queryAcos = DB::table('acos')
            ->select(
                "acos.id",
            )
            ->where('method', $method)->first();
        $acoid = $queryAcos->id;

        $queryUser = DB::table('user')
            ->select(
                "user.id",
            )
            ->where('user', $username)->first();
        $userid = $queryUser->id;

        $queryUserAcl = DB::table('useracl')
            ->select(
                "useracl.id",
                "useracl.aco_id",
                "useracl.user_id",
            )
            ->where('aco_id', $acoid)->where('user_id', $userid)->first();

        if (isset($queryUserAcl)) {
            return true;
        } else {
            return false;
        }
    }

    public function getProductPesanan($data)
    {
        if (!empty($data['id'])) {

            // $tglpengiriman = date('Y-m-d', strtotime($data['tglpengirimanjual']));
            $id = $data['id'];
            $filteredData = DB::table('pesananfinaldetail')
                ->select(
                    'pesananfinaldetail.productid',
                    "product.nama as productnama",
                    DB::raw('MAX(pesananfinaldetail.harga) as hargajual')
                )
                ->leftJoin('product', 'pesananfinaldetail.productid', 'product.id')
                ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
                // ->where('pesananfinalheader.tglpengiriman', $tglpengiriman)
                ->whereIn('pesananfinalheader.id', $id)
                ->groupBy('pesananfinaldetail.productid', "product.nama")
                ->get();
        } else if (!empty($data['tglpengirimanbeli'])) {

            // dd('ghgh');
            $tglpengiriman = date('Y-m-d', strtotime($data['tglpengirimanbeli']));

            $tempQty = 'tempQty' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
            DB::statement("CREATE TEMPORARY TABLE $tempQty (
                productid BIGINT UNSIGNED NULL,
                qty VARCHAR(1000) NULL,
                totalproductqty VARCHAR(100) NULL,
                tglpengiriman DATE NULL
            )");

            $select_tempQty = DB::table('pesananfinaldetail')
                ->join('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', '=', 'pesananfinalheader.id')
                ->select(
                    'productid',
                    DB::raw("REPLACE(GROUP_CONCAT(CONCAT(qtybeli, ' + ')), ',', '') as qty"),
                    DB::raw('SUM(qtybeli) as totalproductqty'),
                    'pesananfinalheader.tglpengiriman'
                )
                ->where('pesananfinalheader.tglpengiriman', '=', $tglpengiriman)
                ->groupBy('productid', 'pesananfinalheader.tglpengiriman')
                ->get();

            // dd($select_tempQty);

            foreach ($select_tempQty as $row) {
                DB::table($tempQty)->insert([
                    'productid' => $row->productid,
                    'qty' => rtrim(trim($row->qty), '+'),
                    'totalproductqty' => $row->totalproductqty,
                    'tglpengiriman' => $tglpengiriman
                ]);
            }

            $data = DB::table("pesananfinalheader")
                ->select('id')
                ->where('tglpengiriman', $tglpengiriman)
                ->pluck('id')
                ->toArray();

            $pembelianHeader = new PembelianHeader();
            $query = $pembelianHeader->approval($data, true);

            function filterData($query, $tempQtyData)
            {
                $filteredData = [];
                foreach ($query as $item) {
                    $supplierId = $item['supplierid'];

                    if (!isset($filteredData[$supplierId])) {
                        $filteredData[$supplierId] = [
                            "supplierid" => $item['supplierid'],
                            "suppliernama" => $item['suppliernama'],
                            "karyawanid" => $item['karyawanid'],
                            "karyawannama" => $item['karyawannama'],
                            "potongan" => $item['potongan'],
                            "productid" => [],
                            "productnama" => [],
                            "harga" => [],
                            "qty" => [],
                            "totalproductqty" => [],
                        ];
                    }

                    foreach ($item['productid'] as $key => $productId) {
                        if (!in_array($productId, $filteredData[$supplierId]['productid'], true)) {
                            $filteredData[$supplierId]['productid'][] = $productId;
                            $filteredData[$supplierId]['productnama'][] = $item['productnama'][$key];
                            $filteredData[$supplierId]['harga'][] = $item['harga'][$key];

                            foreach ($tempQtyData as $tempData) {
                                if ($tempData->productid == $productId) {
                                    $filteredData[$supplierId]['qty'][] = $tempData->qty;
                                    $filteredData[$supplierId]['totalproductqty'][] = $tempData->totalproductqty;
                                    break;
                                }
                            }
                        }
                    }
                }

                $filteredData = array_values($filteredData);
                return $filteredData;
            }
            $tempQtyData = DB::table($tempQty)->get();

            $filteredData = filterData($query, $tempQtyData);
        }
        return $filteredData;
    }

    public function editHargaJual($data)
    {

        // $tglpengiriman = date('Y-m-d', strtotime($data['tglpengiriman']));
        $productIds = $data['productid'];
        $hargaJuals = $data['hargajual'];

        foreach ($productIds as $key => $productId) {
            $hargajual = $hargaJuals[$key];

            if ($hargajual !== null && $hargajual !== 0) {

                $affectedRows = DB::table('pesananfinaldetail')
                    ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', '=', 'pesananfinalheader.id')
                    // ->where('pesananfinalheader.tglpengiriman', $tglpengiriman)
                    ->where('pesananfinaldetail.productid', $productId)
                    ->update(['pesananfinaldetail.harga' => $hargajual]);

                DB::table('product')
                    ->leftJoin('pesananfinaldetail', 'product.id', 'pesananfinaldetail.productid')
                    ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', '=', 'pesananfinalheader.id')
                    // ->where('pesananfinalheader.tglpengiriman', $tglpengiriman)
                    ->where('product.id', $productId)
                    ->update(['product.hargajual' => $hargajual]);
            }
        }
        return $affectedRows;
    }

    public function editHargaBeli($data)
    {
        $productIds = $data['productid'];
        $hargaBelis = $data['hargabeli'];

        foreach ($productIds as $key => $productid) {
            $hargabeli = $hargaBelis[$key];


            if ($hargabeli !== null && $hargabeli !== 0) {

                $affectedRows = DB::table('product')
                    ->where('id', $productid)
                    ->update(['product.hargabeli' => $hargabeli]);
            }
        }
        // dd($affectedRows);
        return $affectedRows;
    }

    public function editingAt($id, $btn)
    {
        $pesananFinalHeader = PesananFinalHeader::find($id);
        $oldUser = $pesananFinalHeader->editingby;
        if ($btn == 'EDIT') {
            $pesananFinalHeader->editingby = auth('api')->user()->name;
            $pesananFinalHeader->editingat = date('Y-m-d H:i:s');
        } else {
            if ($pesananFinalHeader->editingby == auth('api')->user()->name) {
                $pesananFinalHeader->editingby = '';
                $pesananFinalHeader->editingat = null;
            }
        }
        if (!$pesananFinalHeader->save()) {
            throw new \Exception("Error Update pesanan final header.");
        }

        $pesananFinalHeader->oldeditingby = $oldUser;
        return $pesananFinalHeader;
    }

    public function unApproval($tgl)
    {
        $tglpengiriman = date('Y-m-d', strtotime($tgl));
        $query = DB::table('pesananfinalheader')
            ->where('tglpengiriman', $tglpengiriman)
            ->update(['tglcetak' => null]);
        return $query;
    }

    public function cekTglCetak($data)
    {
        $tgl = date('Y-m-d', strtotime($data['tgldari']));
        $query = DB::table('pesananfinalheader')
            ->select('tglcetak')
            ->where('tglpengiriman', $tgl)
            ->get();
        return $query;
    }

    public function processData($data)
    {
        $ids = [];
        $productIds = [];
        $satuanIds = [];
        $qtyJuals = [];
        $keteranganDetails = [];
        $hargaJuals = [];
        foreach ($data as $detail) {
            $ids[] = $detail['id'] ?? null;
            $productIds[] = $detail['productid'];
            $satuanIds[] = $detail['satuanid'];
            $qtyJuals[] = $detail['qtyjual'];
            $keteranganDetails[] = $detail['keterangandetail'];
            $hargaJuals[] = $detail['hargajual'];
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
            "id" =>  $ids,
            "productid" =>  $productIds,
            "satuanid" => $satuanIds,
            "qtyjual" => $qtyJuals,
            "discount" => request()->discount,
            "total" => request()->total,
            "tax" => request()->tax,
            "taxamount" => request()->taxamount,
            "subtotal" => request()->subtotal,
            "keterangandetail" => $keteranganDetails,
            "hargajual" => $hargaJuals,
        ];
        return $data;
    }

    public function cekValidasiPenjualan($nobukti, $id)
    {

        $piutang = DB::table('piutang')
            ->from(
                DB::raw("piutang as a")
            )
            ->select(
                'a.penjualanid',
                'a.nominalpiutang',
                'a.nominalsisa',
                'a.nominalbayar'
            )
            ->where('a.penjualanid', '=', $id)
            ->first();

        $returHeader = ReturJualHeader::from(DB::raw('returjualheader'))->where('penjualanid', $id)->first();
      

        if ($piutang !== null && $piutang->nominalbayar != 0.0) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Penjualan ' . $nobukti,
                'kodeerror' => 'TBDPP'
            ];
            goto selesai;
        }

        if (isset($returHeader)) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Penjualan ' . $returHeader->nobukti,
                'kodeerror' => 'SATL'
            ];
            goto selesai;
        }

        $data = [
            'kondisi' => false,
            'keterangan' => '',
        ];
        selesai:

        // dd($data);

        return $data;
    }

    public function cekValidasiPembelian($tglpengiriman)
    {
        $tglpengiriman = DateTime::createFromFormat('d-m-Y', $tglpengiriman)->format('Y-m-d');

        $query = DB::table('pesananfinalheader as a')
            ->select(
                "a.nobukti",
                "b.nobuktipembelian",
                "c.id as pembelianid",
                "a.status",
            )
            ->leftJoin(DB::raw("pesananfinaldetail as b"), 'a.id', 'b.pesananfinalid')
            ->leftJoin(DB::raw("pembelianheader as c"), 'b.nobuktipembelian', 'c.nobukti')
            ->where('a.status', 1)
            ->where("a.tglpengiriman", $tglpengiriman)
            ->get();

        $pembelianIds = $query->pluck('pembelianid')->toArray();

        foreach ($pembelianIds as $pembelianId) {

            // dd($pembelianId);

            $fetch = DB::table('pembeliandetail')
                ->select(
                    'pembeliandetail.pembelianid',
                    'pesananpembeliandetail.pembeliandetailid',
                    'pesananpembeliandetail.pesananfinalid',
                    'pesananpembeliandetail.pesananfinaldetailid'
                )
                ->leftJoin('pesananpembeliandetail', 'pembeliandetail.id', 'pesananpembeliandetail.pembeliandetailid')
                ->where('pembeliandetail.pembelianid', $pembelianId)
                ->get();

            // dd($fetch);

            foreach ($fetch as $row) {

                // dd($row);
                if ($row !== null && property_exists($row, 'pesananfinalid') && $row->pesananfinalid != 0) {

                    $hutang = DB::table('hutang')
                        ->select('pembelianid', 'nominalhutang', 'nominalsisa', 'nominalbayar')
                        ->where('pembelianid', $row->pembelianid)
                        ->first();

                    $returHeader = $returHeader = ReturBeliHeader::from(DB::raw('returbeliheader'))->where('pembelianid', $row->pembelianid)->first();


                    if ($hutang->nominalbayar != 0.0) {
                        $data = [
                            'kondisi' => true,
                            'kodeerror' => 'TBDPH'
                        ];
                        break;
                    }

                    if (isset($returHeader)) {
                        $data = [
                            'kondisi' => true,
                            'kodeerror' => 'SATL'
                        ];
                        break;
                    }
                }
            }
        }

        if (!isset($data)) {
            $data = [
                'kondisi' => false,
                'keterangan' => '',
            ];
        }

      
     

        return $data;
    }

    public function findAllPembelian()
    {
        $tglpengiriman = date('Y-m-d', strtotime(request()->tglpengirimanbeli));

        $data = DB::table("pesananfinaldetail")
            ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
            ->select('pesananfinaldetail.pesananfinalid')
            ->where('pesananfinalheader.tglpengiriman', $tglpengiriman)
            ->where('pesananfinalheader.status', 1)
            ->where('pesananfinaldetail.nobuktipembelian', '')
            ->groupBy('pesananfinaldetail.pesananfinalid')
            ->pluck('pesananfinalid')
            ->toArray();

        $pembelianHeader = new PembelianHeader();
        $query = array_values($pembelianHeader->approval($data, true));

        // dd($query);

        $result = [];
        foreach ($query as $key => $item) {
            if (!isset($result[$item['supplierid']])) {
                $result[$item['supplierid']] = [
                    "id" => 0,
                    "nobukti" => "",
                    "tglbukti" => $item['tglbukti'],
                    "supplierid" => $item['supplierid'],
                    "suppliernama" => $item['suppliernama'],
                    "karyawanid" => $item['karyawanid'],
                    "karyawannama" => $item['karyawannama'],
                    "tglterima" => $item['tglterima'],
                    "nominalbayar" => 0,
                    "keterangan" => "",
                    "potongan" => $item['potongan'],
                    "subtotal" => $item['subtotal'],
                    "total" => $item['total'],
                    "details" => [],
                ];
            }
            for ($i = 0; $i < count($item['productnama']); $i++) {

                $product = array_keys($item['productid'][$i]);
                $productInfo = reset($product);
                $qtyInfo = reset($item['qty'][$i]);

                $result[$item['supplierid']]['details'][] = [
                    "id" => 0,
                    "pembelianid" => 0,
                    "pembeliannobukti" => "",
                    "productid" => $productInfo,
                    "productnama" => $item['productnama'][$i],
                    "keterangandetail" => $item['keterangandetail'][$i],
                    "qty" => $qtyInfo,
                    "qtyretur" => 0,
                    "qtystok" => 0,
                    "qtypesanan" => $qtyInfo,
                    "harga" => $item['harga'][$i],
                    "totalharga" => $qtyInfo * $item['harga'][$i],
                    "satuanid" => $item['satuanid'][$i],
                    "satuannama" => $item['satuannama'][$i],
                ];

            }
        }

        // dd(array_values($result));

        $result = array_values($result);
        $result = array_combine(array_keys($result), $result);
        // dd($result);
        $pembelianHeader = new PembelianHeader();

        return $result;
    }

    public function findAllPenjualan()
    {
        $tglpengiriman = date('Y-m-d', strtotime(request()->tglpengirimanjual));
        // $namaKaryawan = auth('api')->user()->name;
        $data = DB::table("pesananfinalheader")
            ->select('pesananfinalheader.id')
            ->where('pesananfinalheader.tglpengiriman', $tglpengiriman)
            ->where('pesananfinalheader.status', 1)
            ->where('pesananfinalheader.nobuktipenjualan', '')
            ->pluck('id')
            ->toArray();

        $this->setRequestParameters();
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

        $this->totalRows = $headers->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($headers);
        $this->filter($headers);
        $this->paginate($headers);

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
                "pesananfinaldetail.keterangan as keterangandetail",
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

    public function editAllPembelian($dataPembelian)
    {
        foreach ($dataPembelian as $data) {
            if (empty($data)) {
                continue;
            }

            // dd($data);
            $idToUpdate = $data['id'];
            $pembelianHeader = PembelianHeader::find($idToUpdate);

            // dd($data);

            if ($pembelianHeader) {
                $pembelianHeader->tglbukti = date('Y-m-d', strtotime($data['tglbuktieditall']));
                $pembelianHeader->nobukti = $data['nobukti'];
                $pembelianHeader->supplierid = $data['supplierid'];
                $pembelianHeader->karyawanid = $data['karyawanid'];
                $pembelianHeader->tglterima =  date('Y-m-d', strtotime($data['tglterima']));
                $pembelianHeader->keterangan = $data['keterangan'];
                $pembelianHeader->subtotal = $data['subtotal'] ?? 0;
                $pembelianHeader->potongan = $data['potongan'] ?? 0;
                $pembelianHeader->total = $data['total'] ?? 0;
                $pembelianHeader->save();

                // Log the update in LogTrail
                (new LogTrail())->processStore([
                    'namatabel' => 'pembelianheader',
                    'postingdari' => 'EDIT PEMBELIAN HEADER DARI EDIT ALL PEMBELIAN PESANAN FINAL',
                    'idtrans' => $pembelianHeader->id,
                    'nobuktitrans' => $pembelianHeader->id,
                    'aksi' => 'EDIT',
                    'datajson' => $pembelianHeader->toArray(),
                    'modifiedby' => auth('api')->user()->id,
                ]);

                $returDetails = [];
                $retur = 0;
                // dd($data['details']['productnama[]']);

                foreach ($data['details']['productnama[]'] as $index => $productName) {
                    $detailId = $data['details']['iddetail[]'][$index];
                    $detail = PembelianDetail::find($detailId);

                    if ($detail) {
                        $detail->productid = $data['details']['productid[]'][$index];
                        $detail->qty = $data['details']['qty[]'][$index];
                        $detail->qtyretur = $data['details']['qtyretur[]'][$index];
                        $detail->qtystok = $data['details']['qtystok[]'][$index];
                        $detail->qtypesanan = $data['details']['qtypesanan[]'][$index];
                        $detail->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
                        $detail->qtyretur = $data['details']['qtyretur[]'][$index] ?? 0;
                        $detail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
                        $detail->harga = $data['details']['harga[]'][$index] ?? 0;
                        $detail->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'pembeliandetail',
                            'postingdari' => 'EDIT PEMBELIAN DETAIL DARI EDIT ALL PEMBELIAN PESANAN FINAL',
                            'idtrans' => $detail->id,
                            'nobuktitrans' => $detail->id,
                            'aksi' => 'EDIT',
                            'datajson' => $detail->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }

                    // dd($detail);

                    //Cek Data ReturBeli
                    if ($detail->qtyretur != 0) {
                        $retur++;
                        $returDetail = [
                            'pembeliandetailid' => $detail->id,
                            'productid' => $detail->productid,
                            'pesananfinaldetailid' => $detail->pesananfinaldetailid ?? 0,
                            'keterangan' => $detail->keterangan ?? '',
                            'qty' => $detail->qty ?? 0,
                            'qtyretur' => $detail->qtyretur ?? 0,
                            'satuanid' => $detail->satuanid ?? '',
                            'harga' => $detail->harga ?? 0,
                            'modifiedby' => auth('api')->user()->id,
                        ];
                        $returDetails[] = $returDetail;
                    }


                    // // Update PesananFinalDetail
                    $pesananFinalDetail = PesananFinalDetail::find($data['details']['pesananfinaldetailid[]'][$index]);
                    if ($pesananFinalDetail) {
                        $pesananFinalDetail->qtybeli = $data['details']['qty[]'][$index];
                        $pesananFinalDetail->qtyreturbeli = $data['details']['qtyretur[]'][$index] ?? 0;
                        $pesananFinalDetail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
                        $pesananFinalDetail->hargabeli = $data['details']['harga[]'][$index] ?? 0;

                        $pesananFinalDetail->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'pesananfinaldetail',
                            'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT ALL PEMBELIAN PESANAN FINAL',
                            'idtrans' => $pesananFinalDetail->id,
                            'nobuktitrans' => $pesananFinalDetail->id,
                            'aksi' => 'EDIT',
                            'datajson' => $pesananFinalDetail->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }

                    // update product
                    // $product = Product::find($data['details']['productid[]'][$index]);
                    // if ($product) {
                    //     $product->hargabeli = $data['details']['harga[]'][$index];
                    //     $product->save();

                    //     // Log the update in LogTrail
                    //     (new LogTrail())->processStore([
                    //         'namatabel' => 'product',
                    //         'postingdari' => 'EDIT PRODUCT DARI EDIT ALL PEMBELIAN PESANAN FINAL',
                    //         'idtrans' => $product->id,
                    //         'nobuktitrans' => $product->id,
                    //         'aksi' => 'EDIT',
                    //         'datajson' => $product->toArray(),
                    //         'modifiedby' => auth('api')->user()->id,
                    //     ]);
                    // }
                }

                //Update Hutang
                $hutang = Hutang::where('pembelianid', $data['id'])->first();
                if ($hutang) {
                    $hutang->nominalhutang = $data['total'];
                    $hutang->nominalsisa = $data['total'];

                    $hutang->save();

                    // Log the update in LogTrail
                    (new LogTrail())->processStore([
                        'namatabel' => 'hutang',
                        'postingdari' => 'EDIT HUTANG DARI EDIT ALL PEMBELIAN PESANAN FINAL',
                        'idtrans' => $hutang->id,
                        'nobuktitrans' => $hutang->id,
                        'aksi' => 'EDIT',
                        'datajson' => $hutang->toArray(),
                        'modifiedby' => auth('api')->user()->id,
                    ]);
                }

                //Create ReturBeli
                if ($retur > 0) {
                    $totalRetur = 0;
                    $details = [];

                    foreach ($returDetails as $detail) {
                        $details['pesananfinaldetailid'][] = $detail['pesananfinaldetailid'];
                        $details['pembeliandetailid'][] = $detail['pembeliandetailid'];
                        $details['productid'][] = $detail['productid'];
                        $details['satuanid'][] = $detail['satuanid'];
                        $details['keterangan'][] = $detail['keterangan'];
                        $details['qty'][] = $detail['qtyretur'];
                        $details['harga'][] = $detail['harga'];
                        $details['modifiedby'][] = $detail['modifiedby'];
                        $totalRetur += $detail['harga'] * $detail['qtyretur'];
                    }

                    $returHeader = [
                        'tglbukti' =>  now(),
                        'pembelianid' => $pembelianHeader->id,
                        'pembeliannobukti' => $pembelianHeader->nobukti,
                        'supplierid' => $pembelianHeader->supplierid,
                        'total' => $totalRetur
                    ];

                    $result = array_merge($returHeader, $details);
                    $returJual = (new ReturBeliHeader())->processStore($result);
                }
            } else {
                // dd($data['details']['productnama[]']);
                foreach ($data['details']['productnama[]'] as $index => $productName) {

                    $detailPesananFinal = DB::table('pesananfinaldetail')
                        ->select('pesananfinaldetail.id',)
                        ->leftJoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
                        ->where('pesananfinaldetail.productid', $data['details']['productid[]'][$index])
                        ->where('pesananfinalheader.tglpengiriman', date('Y-m-d', strtotime($data['tglpengiriman'])))
                        ->get();

                    if ($detailPesananFinal) {

                        $detail = PesananFinalDetail::whereIn('id', $detailPesananFinal->pluck('id')->toArray())->get();
                        foreach ($detail as $item) {
                            // dd($item);
                            $item->hargabeli = $data['details']['harga[]'][$index] ?? 0;
                            $item->save();

                            (new LogTrail())->processStore([
                                'namatabel' => 'pesananfinaldetail',
                                'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT ALL PEMBELIAN PESANAN FINAL',
                                'idtrans' => $item->id,
                                'nobuktitrans' => $item->id,
                                'aksi' => 'EDIT',
                                'datajson' => $item->toArray(),
                                'modifiedby' => auth('api')->user()->id,
                            ]);
                            // dump($item);
                        }
                        // die;
                    }
                    // die;



                    // // update product
                    // $product = Product::find($data['details']['productid[]'][$index]);
                    // if ($product) {
                    //     $product->hargabeli = $data['details']['harga[]'][$index];

                    //     $product->save();

                    //     // Log the update in LogTrail
                    //     (new LogTrail())->processStore([
                    //         'namatabel' => 'product',
                    //         'postingdari' => 'EDIT PRODUCT DARI EDIT ALL PEMBELIAN PESANAN FINAL',
                    //         'idtrans' => $product->id,
                    //         'nobuktitrans' => $product->id,
                    //         'aksi' => 'EDIT',
                    //         'datajson' => $product->toArray(),
                    //         'modifiedby' => auth('api')->user()->id,
                    //     ]);
                    // }
                }
            }
        }
        return $dataPembelian;
    }

    public function editAllPenjualanOld($dataPenjualan)
    {
        foreach ($dataPenjualan as $data) {
            if (empty($data)) {
                continue;
            }

            $penjualanHeader = PenjualanHeader::find($data['id']);

            if ($penjualanHeader) {
                $penjualanHeader->tglbukti = date('Y-m-d', strtotime($data['tglbuktieditall']));
                $penjualanHeader->nobukti = $data['nobukti'];
                $penjualanHeader->customerid = $data['customerid'];
                $penjualanHeader->alamatpengiriman = $data['alamatpengiriman'];
                $penjualanHeader->tglpengiriman =  date('Y-m-d', strtotime($data['tglpengirimaneditall']));
                $penjualanHeader->keterangan = $data['keterangan'];
                $penjualanHeader->subtotal = $data['subtotal'] ?? 0;
                $penjualanHeader->tax = $data['tax'] ?? 0;
                $penjualanHeader->taxamount = $data['taxamount'] ?? 0;
                $penjualanHeader->discount = $data['discount'] ?? 0;
                $penjualanHeader->total = $data['total'] ?? 0;
                $penjualanHeader->save();

                // Log the update in LogTrail
                (new LogTrail())->processStore([
                    'namatabel' => 'penjualanheader',
                    'postingdari' => 'EDIT PPENJUALAN HEADER DARI EDIT ALL PENJUALAN PESANAN FINAL',
                    'idtrans' => $penjualanHeader->id,
                    'nobuktitrans' => $penjualanHeader->id,
                    'aksi' => 'EDIT',
                    'datajson' => $penjualanHeader->toArray(),
                    'modifiedby' => auth('api')->user()->id,
                ]);

                $returDetails = [];
                $retur = 0;

                foreach ($data['details']['productnama[]'] as $index => $productName) {
                    $detailId = $data['details']['iddetail[]'][$index];
                    $detail = PenjualanDetail::find($detailId);
                    // $pesananFinalDetail = PesananFinalDetail::find($detail->pesananfinaldetailid);

                    if ($detail) {
                        $detail->productid = $data['details']['productid[]'][$index];
                        $detail->qty = $data['details']['qty[]'][$index];
                        $detail->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
                        $detail->qtyretur = $data['details']['qtyretur[]'][$index] ?? 0;
                        $detail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
                        $detail->harga = $data['details']['harga[]'][$index] ?? 0;
                        $detail->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'penjualandetail',
                            'postingdari' => 'EDIT PENJUALAN DETAIL DARI EDIT ALL PENJUALAN PESANAN FINAL',
                            'idtrans' => $detail->id,
                            'nobuktitrans' => $detail->id,
                            'aksi' => 'EDIT',
                            'datajson' => $detail->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }

                    //Cek Data ReturJual
                    if ($detail->qtyretur != 0) {
                        $retur++;
                        $returDetail = [
                            'penjualandetailid' => $detail->id,
                            'productid' => $detail->productid,
                            'pesananfinaldetailid' => $detail->pesananfinaldetailid ?? 0,
                            'keterangan' => $detail->keterangan ?? '',
                            'qty' => $detail->qty ?? 0,
                            'qtyretur' => $detail->qtyretur ?? 0,
                            'satuanid' => $detail->satuanid ?? '',
                            'harga' => $detail->harga ?? 0,
                            'modifiedby' => auth('api')->user()->id,
                        ];
                        $returDetails[] = $returDetail;
                    }

                    // Update PesananFinalDetail
                    $pesananFinalDetail = PesananFinalDetail::find($data['details']['pesananfinaldetailid[]'][$index]);
                    if ($pesananFinalDetail) {


                        $pesananFinalDetail->productid = $data['details']['productid[]'][$index];
                        $pesananFinalDetail->qtyjual = $data['details']['qty[]'][$index];
                        $pesananFinalDetail->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
                        $pesananFinalDetail->qtyreturbeli = $data['details']['qtyretur[]'][$index] ?? 0;
                        $pesananFinalDetail->qtyreturjual = $data['details']['qtyretur[]'][$index] ?? 0;
                        $pesananFinalDetail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
                        $pesananFinalDetail->hargajual = $data['details']['harga[]'][$index] ?? 0;

                        $pesananFinalDetail->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'pesananfinaldetail',
                            'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT ALL PENJUALAN PESANAN FINAL',
                            'idtrans' => $pesananFinalDetail->id,
                            'nobuktitrans' => $pesananFinalDetail->id,
                            'aksi' => 'EDIT',
                            'datajson' => $pesananFinalDetail->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }

                    // update product
                    $product = Product::find($data['details']['productid[]'][$index]);

                    if ($product) {
                        $product->hargajual = $data['details']['harga[]'][$index];
                        $product->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'product',
                            'postingdari' => 'EDIT PRODUCT DARI EDIT ALL PENJUALAN PESANAN FINAL',
                            'idtrans' => $product->id,
                            'nobuktitrans' => $product->id,
                            'aksi' => 'EDIT',
                            'datajson' => $product->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }
                }
                // Update PesananFinalHeader
                $pesananFinalHeaderId = $data['pesananfinalid'];
                $pesananFinalHeader = PesananFinalHeader::find($pesananFinalHeaderId);

                if ($pesananFinalHeader) {
                    $pesananFinalHeader->customerid = $data['customerid'];
                    $pesananFinalHeader->alamatpengiriman = $data['alamatpengiriman'];
                    $pesananFinalHeader->tglpengiriman =  date('Y-m-d', strtotime($data['tglpengirimaneditall']));
                    $pesananFinalHeader->keterangan = $data['keterangan'];
                    $pesananFinalHeader->subtotal = $data['subtotal'] ?? 0;
                    $pesananFinalHeader->tax = $data['tax'] ?? 0;
                    $pesananFinalHeader->taxamount = $data['taxamount'] ?? 0;
                    $pesananFinalHeader->discount = $data['discount'] ?? 0;
                    $pesananFinalHeader->total = $data['total'] ?? 0;

                    $pesananFinalHeader->save();

                    // Log the update in LogTrail
                    (new LogTrail())->processStore([
                        'namatabel' => 'pesananfinalheader',
                        'postingdari' => 'EDIT PESANAN FINAL HEADER DARI EDIT ALL PENJUALAN PESANAN FINAL',
                        'idtrans' => $pesananFinalHeader->id,
                        'nobuktitrans' => $pesananFinalHeader->id,
                        'aksi' => 'EDIT',
                        'datajson' => $pesananFinalHeader->toArray(),
                        'modifiedby' => auth('api')->user()->id,
                    ]);
                }

                $piutang = Piutang::where('penjualanid', $data['id'])->first();

                if ($piutang) {
                    $piutang->nominalpiutang = $data['total'];
                    $piutang->nominalsisa = $data['total'];

                    $piutang->save();

                    // Log the update in LogTrail
                    (new LogTrail())->processStore([
                        'namatabel' => 'piutang',
                        'postingdari' => 'EDIT PIUTANG DARI EDIT ALL PENJUALAN PESANAN FINAL',
                        'idtrans' => $piutang->id,
                        'nobuktitrans' => $piutang->id,
                        'aksi' => 'EDIT',
                        'datajson' => $piutang->toArray(),
                        'modifiedby' => auth('api')->user()->id,
                    ]);
                }
                //Create ReturJual
                if ($retur > 0) {
                    $totalRetur = 0;
                    $details = [];

                    foreach ($returDetails as $detail) {
                        $details['pesananfinaldetailid'][] = $detail['pesananfinaldetailid'];
                        $details['penjualandetailid'][] = $detail['penjualandetailid'];
                        $details['productid'][] = $detail['productid'];
                        $details['satuanid'][] = $detail['satuanid'];
                        $details['keterangan'][] = $detail['keterangan'];
                        $details['qty'][] = $detail['qtyretur'];
                        $details['harga'][] = $detail['harga'];
                        $details['modifiedby'][] = $detail['modifiedby'];
                        $totalRetur += $detail['harga'] * $detail['qtyretur'];
                    }

                    $returHeader = [
                        'tglbukti' =>  now(),
                        'penjualanid' => $penjualanHeader->id,
                        'penjualannobukti' => $penjualanHeader->nobukti,
                        'customerid' => $penjualanHeader->customerid,
                        'total' => $totalRetur
                    ];

                    $result = array_merge($returHeader, $details);
                    $returJual = (new ReturJualHeader())->processStore($result);
                }
            } else {
                // foreach ($dataPenjualan as $invoiceData) {
                $pesananFinalHeader = PesananFinalHeader::find($data['pesananfinalid']);

                if ($pesananFinalHeader != null) {
                    // dd($invoiceData['nobukti']);
                    $pesananFinalHeader->customerid = $data['customerid'];
                    $pesananFinalHeader->alamatpengiriman = $data['alamatpengiriman'];
                    $pesananFinalHeader->tglpengiriman =  date('Y-m-d', strtotime($data['tglpengirimaneditall']));
                    $pesananFinalHeader->keterangan = $data['keterangan'];
                    $pesananFinalHeader->subtotal = $data['subtotal'] ?? 0;
                    $pesananFinalHeader->tax = $data['tax'] ?? 0;
                    $pesananFinalHeader->taxamount = $data['taxamount'] ?? 0;
                    $pesananFinalHeader->discount = $data['discount'] ?? 0;
                    $pesananFinalHeader->total = $data['total'] ?? 0;
                    $pesananFinalHeader->save();

                    // Log the update in LogTrail
                    (new LogTrail())->processStore([
                        'namatabel' => 'pesananFinalHeader',
                        'postingdari' => 'EDIT PPENJUALAN HEADER DARI EDIT ALL PENJUALAN PESANAN FINAL',
                        'idtrans' => $pesananFinalHeader->id,
                        'nobuktitrans' => $pesananFinalHeader->id,
                        'aksi' => 'EDIT',
                        'datajson' => $pesananFinalHeader->toArray(),
                        'modifiedby' => auth('api')->user()->id,
                    ]);

                    foreach ($data['details']['productnama[]'] as $index => $productName) {
                        // $detailId = $data['details']['iddetail[]'][$index];
                        // $detailPesananFinal = PenjualanDetail::find($detailId);

                        // if ($detailPesananFinal) {
                        //     $detailPesananFinal->productid = $data['details']['productid[]'][$index];
                        //     $detailPesananFinal->qty = $data['details']['qty[]'][$index];
                        //     $detailPesananFinal->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
                        //     $detailPesananFinal->qtyretur = $data['details']['qtyretur[]'][$index] ?? 0;
                        //     $detailPesananFinal->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
                        //     $detailPesananFinal->harga = $data['details']['harga[]'][$index] ?? 0;
                        //     $detailPesananFinal->save();

                        //     // Log the update in LogTrail
                        //     (new LogTrail())->processStore([
                        //         'namatabel' => 'pesananfinaldetail',
                        //         'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT ALL PENJUALAN PESANAN FINAL',
                        //         'idtrans' => $detailPesananFinal->id,
                        //         'nobuktitrans' => $detailPesananFinal->id,
                        //         'aksi' => 'EDIT',
                        //         'datajson' => $detailPesananFinal->toArray(),
                        //         'modifiedby' => auth('api')->user()->id,
                        //     ]);
                        // }

                        // Update PesananFinalDetail
                        $pesananFinalDetail = PesananFinalDetail::find($data['details']['pesananfinaldetailid[]'][$index]);
                        if ($pesananFinalDetail) {
                            for ($i = 0; $i < count($data['details']['productid[]']); $i++) {

                                $pesananfinalid = $data['details']['pesananfinaldetailid[]'][$i];
                                if ($pesananfinalid) {
                                    $detailQty = DB::table("pesananfinaldetail")
                                        ->where('id', '=', $pesananfinalid)
                                        ->first();

                                    if ($detailQty->qtyjual == $data['details']['qty[]'][$index]) {
                                        $data['details']['qtybeli[]'][$index] = $detailQty->qtybeli;
                                    }
                                }
                            }


                            $pesananFinalDetail->productid = $data['details']['productid[]'][$index];
                            $pesananFinalDetail->qtyjual = $data['details']['qty[]'][$index];
                            $pesananFinalDetail->qtybeli = $data['details']['qtybeli[]'][$index] ?? $data['details']['qty[]'][$index];
                            $pesananFinalDetail->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
                            $pesananFinalDetail->qtyreturbeli = $data['details']['qtyretur[]'][$index] ?? 0;
                            $pesananFinalDetail->qtyreturjual = $data['details']['qtyretur[]'][$index] ?? 0;
                            $pesananFinalDetail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
                            $pesananFinalDetail->hargajual = $data['details']['harga[]'][$index] ?? 0;



                            $pesananFinalDetail->save();

                            // Log the update in LogTrail
                            (new LogTrail())->processStore([
                                'namatabel' => 'pesananfinaldetail',
                                'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT ALL PENJUALAN PESANAN FINAL',
                                'idtrans' => $pesananFinalDetail->id,
                                'nobuktitrans' => $pesananFinalDetail->id,
                                'aksi' => 'EDIT',
                                'datajson' => $pesananFinalDetail->toArray(),
                                'modifiedby' => auth('api')->user()->id,
                            ]);
                        }

                        // // update product
                        // $product = Product::find($data['details']['productid[]'][$index]);
                        // if ($product) {
                        //     $product->hargajual = $data['details']['harga[]'][$index];

                        //     $product->save();

                        //     // Log the update in LogTrail
                        //     (new LogTrail())->processStore([
                        //         'namatabel' => 'product',
                        //         'postingdari' => 'EDIT PRODUCT DARI EDIT ALL PENJUALAN PESANAN FINAL',
                        //         'idtrans' => $product->id,
                        //         'nobuktitrans' => $product->id,
                        //         'aksi' => 'EDIT',
                        //         'datajson' => $product->toArray(),
                        //         'modifiedby' => auth('api')->user()->id,
                        //     ]);
                        // }
                    }

                    // Update PesananFinalHeader
                    // $pesananFinalHeaderId = $data['pesananfinalid'];
                    // $pesananFinalHeader = PesananFinalHeader::find($pesananFinalHeaderId);

                    // if ($pesananFinalHeader) {
                    //     $pesananFinalHeader->customerid = $data['customerid'];
                    //     $pesananFinalHeader->alamatpengiriman = $data['alamatpengiriman'];
                    //     $pesananFinalHeader->tglpengiriman =  date('Y-m-d', strtotime($data['tglpengirimaneditall']));
                    //     $pesananFinalHeader->keterangan = $data['keterangan'];
                    //     $pesananFinalHeader->subtotal = $data['subtotal'] ?? 0;
                    //     $pesananFinalHeader->tax = $data['tax'] ?? 0;
                    //     $pesananFinalHeader->taxamount = $data['taxamount'] ?? 0;
                    //     $pesananFinalHeader->discount = $data['discount'] ?? 0;
                    //     $pesananFinalHeader->total = $data['total'] ?? 0;

                    //     $pesananFinalHeader->save();

                    //     // Log the update in LogTrail
                    //     (new LogTrail())->processStore([
                    //         'namatabel' => 'pesananfinalheader',
                    //         'postingdari' => 'EDIT PESANAN FINAL HEADER DARI EDIT ALL PENJUALAN PESANAN FINAL',
                    //         'idtrans' => $pesananFinalHeader->id,
                    //         'nobuktitrans' => $pesananFinalHeader->id,
                    //         'aksi' => 'EDIT',
                    //         'datajson' => $pesananFinalHeader->toArray(),
                    //         'modifiedby' => auth('api')->user()->id,
                    //     ]);
                    // }

                    //Update Piutang
                    $piutang = Piutang::where('penjualanid', $data['id'])->first();
                    if ($piutang) {
                        $piutang->nominalpiutang = $data['total'];
                        $piutang->nominalsisa = $data['total'];
                        $piutang->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'piutang',
                            'postingdari' => 'EDIT PIUTANG DARI EDIT ALL PENJUALAN PESANAN FINAL',
                            'idtrans' => $piutang->id,
                            'nobuktitrans' => $piutang->id,
                            'aksi' => 'EDIT',
                            'datajson' => $piutang->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }
                }
            }
        }
        return $dataPenjualan;
    }

    public function editAllPenjualan($dataPenjualan)
    {

        // dd($dataPenjualan);
        $tempHeader = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempHeader (
            id INT UNSIGNED NULL,
            customerid INT,
            customernama VARCHAR(100),
            nobukti VARCHAR(100),
            tglbuktieditall DATE,
            pesananfinalid INT NULL,
            alamatpengiriman VARCHAR(500),
            tglpengirimaneditall DATE,
            keterangan VARCHAR(500),
            tax VARCHAR(500),
            taxamount VARCHAR(500),
            subtotal VARCHAR(500),
            total VARCHAR(500),
            discount VARCHAR(500),
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        )");

        $tempDetail = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempDetail (
            id INT UNSIGNED NULL,
            pesananfinalid INT NULL,
            productid INT NULL,
            nobuktipembelian VARCHAR(500),
            keterangan VARCHAR(500),
            qtyjual FLOAT,
            qtybeli FLOAT,
            qtyreturjual FLOAT,
            qtyreturbeli FLOAT,
            hargajual FLOAT,
            hargabeli FLOAT,
            satuanid INT NULL,
            cekpesanandetail INT NULL,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        
        )");


        foreach ($dataPenjualan as $data) {
            if (empty($data)) {
                continue;
            }

            $penjualanHeader = PenjualanHeader::find($data['id']);

            if ($penjualanHeader) {
                $penjualanHeader->tglbukti = date('Y-m-d', strtotime($data['tglbuktieditall']));
                $penjualanHeader->nobukti = $data['nobukti'];
                $penjualanHeader->customerid = $data['customerid'];
                $penjualanHeader->alamatpengiriman = $data['alamatpengiriman'];
                $penjualanHeader->tglpengiriman =  date('Y-m-d', strtotime($data['tglpengirimaneditall']));
                $penjualanHeader->keterangan = $data['keterangan'];
                $penjualanHeader->subtotal = $data['subtotal'] ?? 0;
                $penjualanHeader->tax = $data['tax'] ?? 0;
                $penjualanHeader->taxamount = $data['taxamount'] ?? 0;
                $penjualanHeader->discount = $data['discount'] ?? 0;
                $penjualanHeader->total = $data['total'] ?? 0;
                $penjualanHeader->save();

                // Log the update in LogTrail
                (new LogTrail())->processStore([
                    'namatabel' => 'penjualanheader',
                    'postingdari' => 'EDIT PPENJUALAN HEADER DARI EDIT ALL PENJUALAN PESANAN FINAL',
                    'idtrans' => $penjualanHeader->id,
                    'nobuktitrans' => $penjualanHeader->id,
                    'aksi' => 'EDIT',
                    'datajson' => $penjualanHeader->toArray(),
                    'modifiedby' => auth('api')->user()->id,
                ]);

                $returDetails = [];
                $retur = 0;

                foreach ($data['details']['productnama[]'] as $index => $productName) {
                    $detailId = $data['details']['iddetail[]'][$index];
                    $detail = PenjualanDetail::find($detailId);
                    // $pesananFinalDetail = PesananFinalDetail::find($detail->pesananfinaldetailid);

                    if ($detail) {
                        $detail->productid = $data['details']['productid[]'][$index];
                        $detail->qty = $data['details']['qty[]'][$index];
                        $detail->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
                        $detail->qtyretur = $data['details']['qtyretur[]'][$index] ?? 0;
                        $detail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
                        $detail->harga = $data['details']['harga[]'][$index] ?? 0;
                        $detail->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'penjualandetail',
                            'postingdari' => 'EDIT PENJUALAN DETAIL DARI EDIT ALL PENJUALAN PESANAN FINAL',
                            'idtrans' => $detail->id,
                            'nobuktitrans' => $detail->id,
                            'aksi' => 'EDIT',
                            'datajson' => $detail->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }

                    //Cek Data ReturJual
                    if ($detail->qtyretur != 0) {
                        $retur++;
                        $returDetail = [
                            'penjualandetailid' => $detail->id,
                            'productid' => $detail->productid,
                            'pesananfinaldetailid' => $detail->pesananfinaldetailid ?? 0,
                            'keterangan' => $detail->keterangan ?? '',
                            'qty' => $detail->qty ?? 0,
                            'qtyretur' => $detail->qtyretur ?? 0,
                            'satuanid' => $detail->satuanid ?? '',
                            'harga' => $detail->harga ?? 0,
                            'modifiedby' => auth('api')->user()->id,
                        ];
                        $returDetails[] = $returDetail;
                    }

                    // Update PesananFinalDetail
                    $pesananFinalDetail = PesananFinalDetail::find($data['details']['pesananfinaldetailid[]'][$index]);
                    if ($pesananFinalDetail) {


                        $pesananFinalDetail->productid = $data['details']['productid[]'][$index];
                        $pesananFinalDetail->qtyjual = $data['details']['qty[]'][$index];
                        $pesananFinalDetail->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
                        $pesananFinalDetail->qtyreturbeli = $data['details']['qtyretur[]'][$index] ?? 0;
                        $pesananFinalDetail->qtyreturjual = $data['details']['qtyretur[]'][$index] ?? 0;
                        $pesananFinalDetail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
                        $pesananFinalDetail->hargajual = $data['details']['harga[]'][$index] ?? 0;

                        $pesananFinalDetail->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'pesananfinaldetail',
                            'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT ALL PENJUALAN PESANAN FINAL',
                            'idtrans' => $pesananFinalDetail->id,
                            'nobuktitrans' => $pesananFinalDetail->id,
                            'aksi' => 'EDIT',
                            'datajson' => $pesananFinalDetail->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }

                    // update product
                    $product = Product::find($data['details']['productid[]'][$index]);

                    if ($product) {
                        $product->hargajual = $data['details']['harga[]'][$index];
                        $product->save();

                        // Log the update in LogTrail
                        (new LogTrail())->processStore([
                            'namatabel' => 'product',
                            'postingdari' => 'EDIT PRODUCT DARI EDIT ALL PENJUALAN PESANAN FINAL',
                            'idtrans' => $product->id,
                            'nobuktitrans' => $product->id,
                            'aksi' => 'EDIT',
                            'datajson' => $product->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }
                }
                // Update PesananFinalHeader
                $pesananFinalHeaderId = $data['pesananfinalid'];
                $pesananFinalHeader = PesananFinalHeader::find($pesananFinalHeaderId);

                if ($pesananFinalHeader) {
                    $pesananFinalHeader->customerid = $data['customerid'];
                    $pesananFinalHeader->alamatpengiriman = $data['alamatpengiriman'];
                    $pesananFinalHeader->tglpengiriman =  date('Y-m-d', strtotime($data['tglpengirimaneditall']));
                    $pesananFinalHeader->keterangan = $data['keterangan'];
                    $pesananFinalHeader->subtotal = $data['subtotal'] ?? 0;
                    $pesananFinalHeader->tax = $data['tax'] ?? 0;
                    $pesananFinalHeader->taxamount = $data['taxamount'] ?? 0;
                    $pesananFinalHeader->discount = $data['discount'] ?? 0;
                    $pesananFinalHeader->total = $data['total'] ?? 0;

                    $pesananFinalHeader->save();

                    // Log the update in LogTrail
                    (new LogTrail())->processStore([
                        'namatabel' => 'pesananfinalheader',
                        'postingdari' => 'EDIT PESANAN FINAL HEADER DARI EDIT ALL PENJUALAN PESANAN FINAL',
                        'idtrans' => $pesananFinalHeader->id,
                        'nobuktitrans' => $pesananFinalHeader->id,
                        'aksi' => 'EDIT',
                        'datajson' => $pesananFinalHeader->toArray(),
                        'modifiedby' => auth('api')->user()->id,
                    ]);
                }

                $piutang = Piutang::where('penjualanid', $data['id'])->first();

                if ($piutang) {
                    $piutang->nominalpiutang = $data['total'];
                    $piutang->nominalsisa = $data['total'];

                    $piutang->save();

                    // Log the update in LogTrail
                    (new LogTrail())->processStore([
                        'namatabel' => 'piutang',
                        'postingdari' => 'EDIT PIUTANG DARI EDIT ALL PENJUALAN PESANAN FINAL',
                        'idtrans' => $piutang->id,
                        'nobuktitrans' => $piutang->id,
                        'aksi' => 'EDIT',
                        'datajson' => $piutang->toArray(),
                        'modifiedby' => auth('api')->user()->id,
                    ]);
                }
                //Create ReturJual
                if ($retur > 0) {
                    $totalRetur = 0;
                    $details = [];

                    foreach ($returDetails as $detail) {
                        $details['pesananfinaldetailid'][] = $detail['pesananfinaldetailid'];
                        $details['penjualandetailid'][] = $detail['penjualandetailid'];
                        $details['productid'][] = $detail['productid'];
                        $details['satuanid'][] = $detail['satuanid'];
                        $details['keterangan'][] = $detail['keterangan'];
                        $details['qty'][] = $detail['qtyretur'];
                        $details['harga'][] = $detail['harga'];
                        $details['modifiedby'][] = $detail['modifiedby'];
                        $totalRetur += $detail['harga'] * $detail['qtyretur'];
                    }

                    $returHeader = [
                        'tglbukti' =>  now(),
                        'penjualanid' => $penjualanHeader->id,
                        'penjualannobukti' => $penjualanHeader->nobukti,
                        'customerid' => $penjualanHeader->customerid,
                        'total' => $totalRetur
                    ];

                    $result = array_merge($returHeader, $details);
                    $returJual = (new ReturJualHeader())->processStore($result);
                }
            } else {
                DB::table($tempHeader)->insert([
                    'id' => $data['pesananfinalid'],
                    'customerid' => $data['customerid'],
                    'customernama' => $data['customernama'],
                    'nobukti' => $data['nobukti'],
                    'tglbuktieditall' => date('Y-m-d', strtotime($data['tglbuktieditall'])),
                    'pesananfinalid' => $data['pesananfinalid'] ?? 0,
                    'alamatpengiriman' => $data['alamatpengiriman'],
                    'tglpengirimaneditall' => date('Y-m-d', strtotime($data['tglpengirimaneditall'])),
                    'keterangan' => $data['keterangan'],
                    'tax' => $data['tax'],
                    'taxamount' => $data['taxamount'],
                    'subtotal' => $data['subtotal'],
                    'total' => $data['total'],
                    'discount' => $data['discount'],
                    'modifiedby' => auth('api')->user()->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                foreach ($data['details']['productnama[]'] as $index => $productName) {
                    DB::table($tempDetail)->insert([
                        'id' => $data['details']['pesananfinaldetailid[]'][$index],
                        'pesananfinalid' => $data['details']['idheader[]'][$index],
                        'productid' => $data['details']['productid[]'][$index],
                        'keterangan' => $data['details']['keterangandetail[]'][$index] ?? "",
                        'qtyjual' => $data['details']['qty[]'][$index],
                        'qtyreturjual' => $data['details']['qtyretur[]'][$index],
                        'satuanid' => $data['details']['satuanid[]'][$index],
                        'hargajual' => $data['details']['harga[]'][$index],
                        'cekpesanandetail' => 0,
                        'modifiedby' => auth('api')->user()->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }


        DB::table("pesananfinaldetail as a")
            ->leftjoin("$tempDetail as b", 'a.id', '=', 'b.id')
            ->where('b.qtyjual', '!=', DB::raw('a.qtyjual'))
            ->update([
                'a.qtyjual' => DB::raw('b.qtyjual'),
                'a.qtybeli' => DB::raw('b.qtyjual')
            ]);

        // update pesananfinalheader
        DB::table('pesananfinalheader as a')
            ->join("$tempHeader as b", 'a.id', '=', 'b.id')
            ->update([
                'a.customerid' => DB::raw('b.customerid'),
                'a.alamatpengiriman' => DB::raw('b.alamatpengiriman'),
                'a.tglpengiriman' => DB::raw('b.tglpengirimaneditall'),
                'a.keterangan' => DB::raw('b.keterangan'),
                'a.tax' => DB::raw('b.tax'),
                'a.taxamount' => DB::raw('b.taxamount'),
                'a.subtotal' => DB::raw('b.subtotal'),
                'a.total' => DB::raw('b.total'),
                'a.discount' => DB::raw('b.discount'),
                'a.modifiedby' => DB::raw('b.modifiedby'),
                'a.created_at' => DB::raw('b.created_at'),
                'a.updated_at' => DB::raw('b.updated_at')
            ]);

        // update pesananfinaldetail
        DB::table('pesananfinaldetail as a')
            ->join("pesananfinalheader as b", 'a.pesananfinalid', '=', 'b.id')
            ->join("$tempDetail as c", 'a.id', '=', 'c.id')
            ->update([
                'a.pesananfinalid' => DB::raw('c.pesananfinalid'),
                'a.productid' => DB::raw('c.productid'),
                'a.keterangan' => DB::raw('c.keterangan'),
                'a.qtyjual' => DB::raw('c.qtyjual'),
                'a.qtyreturjual' => DB::raw('c.qtyreturjual'),
                'a.satuanid' => DB::raw('c.satuanid'),
                'a.hargajual' => DB::raw('c.hargajual'),
                'a.modifiedby' => DB::raw('c.modifiedby'),
                'a.created_at' => DB::raw('c.created_at'),
                'a.updated_at' => DB::raw('c.updated_at')
            ]);


        DB::table('pesananfinaldetail as a')
            ->join("$tempHeader as b", 'a.pesananfinalid', '=', 'b.id')
            ->leftJoin("$tempDetail as c", 'a.id', '=', 'c.id')
            ->whereNull('c.id')
            ->delete();

        // dd($query->get());

        $insertAddRowQuery =  DB::table("$tempDetail as a")
            ->select(
                'a.id',
                'a.pesananfinalid',
                'a.productid',
                DB::raw("IFNULL(a.nobuktipembelian, '') AS nobuktipembelian"),
                'a.keterangan',
                'a.qtyjual',
                'a.qtyjual as qtybeli',
                DB::raw('IFNULL(a.qtyreturjual, 0) AS qtyreturjual'),
                DB::raw('IFNULL(a.qtyreturbeli, 0) AS qtyreturbeli'),
                DB::raw('IFNULL(a.hargajual, 0) AS hargajual'),
                DB::raw('IFNULL(b.hargabeli, 0) AS hargabeli'),
                'a.satuanid',
                'a.cekpesanandetail',
                'a.modifiedby',
                'a.created_at',
                'a.updated_at',
            )
            ->leftjoin("product as b", 'a.productid', '=', 'b.id')
            ->where("a.id", '=', '0');

        DB::table('pesananfinaldetail')->insertUsing(["id", "pesananfinalid", "productid", "nobuktipembelian", "keterangan", "qtyjual", "qtybeli", "qtyreturjual", "qtyreturbeli", "hargajual", "hargabeli", "satuanid", "cekpesanandetail",  "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);



        return $dataPenjualan;
    }
}
