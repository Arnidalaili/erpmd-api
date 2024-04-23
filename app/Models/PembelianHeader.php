<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use stdClass;

class PembelianHeader extends MyModel
{
    use HasFactory;

    protected $table = 'pembelianheader';

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
        $query = DB::table($this->table . ' as pembelianheader')
            ->select(
                "pembelianheader.id",
                "pembelianheader.tglbukti",
                "pembelianheader.nobukti",
                "pembelianheader.supplierid",
                "supplier.nama as suppliernama",
                "pembelianheader.karyawanid",
                "karyawan.nama as karyawannama",
                "pembelianheader.keterangan",
                "pembelianheader.tglterima",
                "pembelianheader.subtotal",
                "pembelianheader.potongan",
                // DB::raw('(pembelianheader.subtotal + pembelianheader.potongan) AS subtotal'),
                DB::raw('(pembelianheader.subtotal - pembelianheader.potongan) AS grandtotal'),
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "pembelianheader.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pembelianheader.created_at',
                'pembelianheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'pembelianheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pembelianheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("karyawan"), 'pembelianheader.karyawanid', 'karyawan.id');

        // dd(request()->tgldari, request()->tglsampai);

        if (request()->tgldari && request()->tglsampai) {
            $query->whereBetween($this->table . '.tglbukti', [date('Y-m-d', strtotime(request()->tgldari)), date('Y-m-d', strtotime(request()->tglsampai))]);
            // dd($query->get());
        }


        if (request()->karyawanid != '') {

            $query->where("karyawan.id", request()->karyawanid);
        }

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
        $query = DB::table('pembelianheader')
            ->select(
                "pembelianheader.id",
                "pembelianheader.tglbukti",
                "pembelianheader.nobukti",
                "pembelianheader.supplierid",
                "supplier.nama as suppliernama",
                "pembelianheader.top",
                "top.text as topnama",
                "pembelianheader.karyawanid",
                "karyawan.nama as karyawannama",
                "pembelianheader.keterangan",
                "pembelianheader.tglterima",
                "pembelianheader.subtotal",
                "pembelianheader.potongan",
                DB::raw('(pembelianheader.subtotal - pembelianheader.potongan) AS grandtotal'),
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "parameter.text as statusnama",
                "pembelianheader.tglcetak",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pembelianheader.created_at',
                'pembelianheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'pembelianheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as top"), 'pembelianheader.top', 'top.id')
            ->leftJoin(DB::raw("user as modifier"), 'pembelianheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("karyawan"), 'pembelianheader.karyawanid', 'karyawan.id')
            ->where('pembelianheader.id', $id);

        // dd($query->first());

        $data = $query->first();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pembelianheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(pembelianheader.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(pembelianheader.nobukti, '/', 1)"), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'suppliernama') {
            return $query->orderBy('supplier.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'karyawannama') {
            return $query->orderBy('karyawan.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'modifiedby_name') {
            return $query->orderBy('modifier.name', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'grandtotal') {
            return $query->orderBy(DB::raw('(pembelianheader.subtotal - pembelianheader.potongan)'), $this->params['sortOrder']);
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
                        if ($filters['field'] == 'suppliernama') {
                            $query = $query->where('supplier.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'karyawannama') {
                            $query = $query->where('karyawan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->where('modifier.name', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'grandtotal') {
                            $query->whereRaw('(pembelianheader.subtotal - pembelianheader.potongan) LIKE ?', ["%$filters[data]%"]);
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'suppliernama') {
                            $query = $query->where('supplier.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'karyawannama') {
                            $query = $query->where('karyawan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->orWhere('parameter.memo', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'aksi') {
                        } else if ($filters['field'] == 'grandtotal') {
                            $query->OrwhereRaw('(pembelianheader.subtotal - pembelianheader.potongan) LIKE ?', ["%$filters[data]%"]);
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
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
            $this->table.tglbukti,
            $this->table.nobukti,
            $this->table.supplierid,
            supplier.nama as suppliernama,
            $this->table.karyawanid,
            karyawan.nama as karyawannama,
            $this->table.keterangan,
            $this->table.tglterima,
            $this->table.subtotal,
            $this->table.potongan,
            parameter.id as status,
            parameter.text as statusnama,
            parameter.memo as statusmemo,
            $this->table.tglcetak,
            modifier.id as modifiedby,
            modifier.name as modifiedby_name,
            $this->table.created_at,
            $this->table.updated_at
        ")
            )
            ->leftJoin(DB::raw("parameter"), 'pembelianheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'pembelianheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("karyawan"), 'pembelianheader.karyawanid', 'karyawan.id');
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
            tglbukti DATETIME,
            nobukti VARCHAR(100),
            supplierid INT,
            suppliernama VARCHAR(100),
            karyawanid INT,
            karyawannama VARCHAR(100),
            keterangan VARCHAR(500),
            tglterima DATETIME,
            subtotal FLOAT,
            potongan FLOAT,
            status INT,
            statusnama VARCHAR(500),
            statusmemo VARCHAR(500),
            tglcetak DATETIME,
            modifiedby INT,
            modifiedby_name VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");

        DB::table($temp)->insertUsing([
            "id", "tglbukti", "nobukti", "supplierid", "suppliernama", "karyawanid", "karyawannama",
            "keterangan", "tglterima", "subtotal", "potongan", "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
            "created_at", "updated_at"
        ], $query);
        return $temp;
    }

    public function default()
    {
        $tempdefault = 'tempdefault' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempdefault (
            status INT NULL,
            statusnama VARCHAR(100)
        )");

        $status = DB::table("parameter")
            ->select('id', 'text')
            ->where('grp', '=', 'STATUS')
            ->where('subgrp', '=', 'STATUS')
            ->where('DEFAULT', '=', 'YA')
            ->first();

        DB::statement("INSERT INTO $tempdefault (status,statusnama) VALUES (?,?)", [$status->id, $status->text]);

        $query = DB::table(
            $tempdefault
        )
            ->select(
                'status',
                'statusnama'
            );

        $data = $query->first();
        return $data;
    }

    public function getfilterTglPengiriman($tglpengiriman)
    {
        $this->setRequestParameters();
        $query = DB::table('pesananfinalheader')
            ->select(
                "pesananfinalheader.id"
            )->whereDate('pesananfinalheader.tglpengiriman', $tglpengiriman)
            ->where('pesananfinalheader.status', 1);
        $data = $query->get();
        $resultIds = $data->map(function ($item) {
            return $item->id;
        })->toArray();
        $this->approval($resultIds, '');

        return $resultIds;
    }

    public function approval($pesananFinalHeader, $edithargabeli)
    {
        $pembelianHeaderData = [];
        $tglbukti = date('Y-m-d');
        $tglterima = date('Y-m-d');

        $tempSub = 'tempSub' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempSub (
            supplierid BIGINT UNSIGNED NULL,
            subtotal BIGINT UNSIGNED NULL
        )");

        foreach ($pesananFinalHeader as $index => $id) {

            $tempTotal = 'tempTotal' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
            DB::statement("CREATE TEMPORARY TABLE $tempTotal (
                supplierid BIGINT UNSIGNED NULL,
                subtotal BIGINT UNSIGNED NULL
            )");

            $pesananfinalquery = DB::table('pesananfinaldetail')
                ->leftjoin('product', 'pesananfinaldetail.productid', 'product.id')
                ->select(
                    'product.supplierid',
                    DB::raw("SUM((pesananfinaldetail.qtybeli * pesananfinaldetail.hargabeli)) as subtotal")
                )
                ->where('pesananfinalid', '=', $id)
                ->groupBy('product.supplierid')
                ->get();

            foreach ($pesananfinalquery as $row) {
                DB::table($tempTotal)->insert([
                    'supplierid' => $row->supplierid,
                    'subtotal' => $row->subtotal
                ]);
                DB::table($tempSub)->insert([
                    'supplierid' => $row->supplierid,
                    'subtotal' => $row->subtotal
                ]);
            }

            $pesananFinalDetail = DB::table('pesananfinaldetail')
                ->leftjoin('product', 'pesananfinaldetail.productid', 'product.id')
                ->leftjoin('supplier', 'product.supplierid', 'supplier.id')
                ->leftjoin('karyawan', 'supplier.karyawanid', 'karyawan.id')
                ->leftJoin($tempTotal . ' as sub', 'product.supplierid', 'sub.supplierid')
                ->select(
                    DB::raw("'$tglbukti' as tglbukti"),
                    DB::raw("'$tglterima' as tglterima"),
                    DB::raw("max(supplier.id) as supplierid"),
                    DB::raw("max(supplier.nama) as suppliernama"),
                    DB::raw("max(supplier.telepon) as suppliertelepon"),
                    DB::raw("max(supplier.potongan) as potongan"),
                    DB::raw("max(supplier.top) as top"),
                    DB::raw("max(karyawan.id) as karyawanid"),
                    DB::raw("max(karyawan.nama) as karyawannama"),
                    DB::raw("max(karyawan.nama) as karyawannama"),
                    'sub.subtotal',
                    DB::raw('IFNULL(sub.subtotal, 0) - IFNULL(MAX(supplier.potongan), 0) AS total')
                )
                ->where('pesananfinalid', '=', $id)
                ->groupBy('product.supplierid', 'sub.subtotal')
                ->get();

            foreach ($pesananFinalDetail as $row) {
                $pesananfinaldetail = DB::table('pesananfinaldetail')
                    ->leftjoin('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', '=', 'pesananfinalheader.id')
                    ->leftjoin('product', 'pesananfinaldetail.productid', 'product.id')
                    ->leftjoin('satuan', 'pesananfinaldetail.satuanid', 'satuan.id')
                    ->leftjoin('supplier', 'product.supplierid', 'supplier.id')
                    ->leftjoin('karyawan', 'supplier.karyawanid', 'karyawan.id')
                    ->select(
                        'pesananfinaldetail.pesananfinalid',
                        'pesananfinaldetail.id as pesananfinaldetailid',
                        'pesananfinaldetail.productid as productid',
                        'pesananfinaldetail.satuanid as satuanid',
                        'pesananfinaldetail.keterangan as keterangan',
                        'pesananfinaldetail.qtybeli as qty',
                        'product.nama as productnama',
                        'pesananfinaldetail.hargabeli as harga',
                        'satuan.nama as satuannama',
                    )
                    ->where('pesananfinaldetail.pesananfinalid', '=', $id)
                    ->where('supplier.id', '=', $row->supplierid)
                    ->get();

                if (array_key_exists($row->supplierid, $pembelianHeaderData)) {

                    $pembelianHeaderData[$row->supplierid]['subtotal'] += $row->subtotal;
                    $pembelianHeaderData[$row->supplierid]['total'] += $row->total;

                    foreach ($pesananfinaldetail as $detail) {

                        $productIndex = null;
                        foreach ($pembelianHeaderData[$row->supplierid]['productid'] as $index => $product) {
                            if (is_array($product) && isset($product[$detail->productid])) {
                                $productIndex = $index;
                                break;
                            }
                        }

                        if ($productIndex !== null) {
                            $pembelianHeaderData[$row->supplierid]['productid'][$productIndex][$detail->productid]['pesananfinalid'][] = $detail->pesananfinalid;
                            $pembelianHeaderData[$row->supplierid]['productid'][$productIndex][$detail->productid]['pesananfinaldetailid'][] = $detail->pesananfinaldetailid;
                            $pembelianHeaderData[$row->supplierid]['qty'][$productIndex][$detail->productid] += $detail->qty;
                        } else {
                            $pembelianHeaderData[$row->supplierid]['satuanid'][] = $detail->satuanid;
                            $pembelianHeaderData[$row->supplierid]['satuannama'][] = $detail->satuannama;
                            $pembelianHeaderData[$row->supplierid]['productid'][][$detail->productid] = [
                                'pesananfinalid' => [$detail->pesananfinalid],
                                'pesananfinaldetailid' => [$detail->pesananfinaldetailid],
                            ];
                            $pembelianHeaderData[$row->supplierid]['qty'][][$detail->productid] = $detail->qty;
                            $pembelianHeaderData[$row->supplierid]['productnama'][] = $detail->productnama;
                            $pembelianHeaderData[$row->supplierid]['harga'][] = $detail->harga;
                        }
                    }
                } else {
                    $header = (array) $row;
                    $pembelianHeaderData[$row->supplierid] = $header;
                    foreach ($pesananfinaldetail as $detail) {
                        $pembelianHeaderData[$row->supplierid]['satuanid'][] = $detail->satuanid;
                        $pembelianHeaderData[$row->supplierid]['satuannama'][] = $detail->satuannama;
                        $pembelianHeaderData[$row->supplierid]['productid'][][$detail->productid] = [
                            'pesananfinalid' => [$detail->pesananfinalid],
                            'pesananfinaldetailid' => [$detail->pesananfinaldetailid],
                        ];
                        $pembelianHeaderData[$row->supplierid]['qty'][][$detail->productid] = $detail->qty;
                        $pembelianHeaderData[$row->supplierid]['productnama'][] = $detail->productnama;
                        $pembelianHeaderData[$row->supplierid]['harga'][] = $detail->harga;
                    }
                }
            }
        }

        if ($pesananFinalHeader) {

            $header = PesananFinalHeader::where('id', $pesananFinalHeader[0])->first();

            $tempQty = 'tempQty' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
            DB::statement("CREATE TEMPORARY TABLE $tempQty (
                    pesananfinaldetailid BIGINT UNSIGNED NULL,
                    productid BIGINT UNSIGNED NULL,
                    keterangandetail VARCHAR(1000) NULL,
                    totalproductqty VARCHAR(100) NULL,
                    productnama VARCHAR(100) NULL,
                    tglpengiriman DATE NULL
                )");

            $select_tempQty = DB::table('pesananfinaldetail')
                ->join('pesananfinalheader', 'pesananfinaldetail.pesananfinalid', '=', 'pesananfinalheader.id')
                ->select(
                    DB::raw("MIN(pesananfinaldetail.id) as pesananfinaldetailid"),
                    'productid',
                    DB::raw("REPLACE(GROUP_CONCAT(CONCAT(qtybeli, ' + ')), ',', '') as qty"),
                    DB::raw('SUM(qtybeli) as totalproductqty'),
                    'product.nama as productnama',
                    'pesananfinalheader.tglpengiriman',
                )
                ->where('pesananfinalheader.tglpengiriman', '=', $header->tglpengiriman)
                ->where('pesananfinalheader.status', 1)
                ->leftJoin('product', 'pesananfinaldetail.productid', 'product.id')
                ->groupBy('productid', 'pesananfinalheader.tglpengiriman', 'product.nama')
                ->get();

            foreach ($select_tempQty as $row) {
                DB::table($tempQty)->insert([
                    'pesananfinaldetailid' => $row->pesananfinaldetailid,
                    'productid' => $row->productid,
                    'keterangandetail' => rtrim(trim($row->qty), '+'),
                    'totalproductqty' => $row->totalproductqty,
                    'productnama' => $row->productnama,
                    'tglpengiriman' => $header->tglpengiriman,
                ]);
            }
            $temp = DB::table($tempQty)->get();

            foreach ($temp as $item) {
                $productName = $item->productnama;
                $keteranganDetail = $item->keterangandetail;
                foreach ($pembelianHeaderData as &$pembelianItem) {
                    $index = array_search($productName, $pembelianItem['productnama']);

                    if ($index !== false) {
                        if (!isset($pembelianItem['keterangandetail'][$index])) {
                            $pembelianItem['keterangandetail'][$index] = '';
                        }
                        $pembelianItem['keterangandetail'][$index] = $keteranganDetail;
                        ksort($pembelianItem['keterangandetail']);
                    }
                }
            }
            unset($pembelianItem);
        }

        if (!$edithargabeli) {
            foreach ($pembelianHeaderData as $row) {

                /*STORE HEADER*/
                $pembelianHeader = new PembelianHeader();
                $group = 'PEMBELIAN HEADER BUKTI';
                $subGroup = 'PEMBELIAN HEADER BUKTI';
                $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();
                $tglbukti = date('Y-m-d', strtotime($row['tglbukti']));
                $tglterima = date('Y-m-d', strtotime($row['tglterima']));

                $pembelianHeader->tglbukti = $tglbukti ?? '';
                $pembelianHeader->supplierid = $row['supplierid'] ?? 0;
                $pembelianHeader->top = $row['top'] ?? 0;
                $pembelianHeader->karyawanid = $row['karyawanid'] ?? 0;
                $pembelianHeader->keterangan = $row['keterangan'] ?? '';
                $pembelianHeader->tglterima = $tglterima ?? '';
                $pembelianHeader->potongan = $row['potongan'] ?? 0;
                $pembelianHeader->subtotal = $row['subtotal'] ?? 0;
                $pembelianHeader->total = $row['total'] ?? 0;
                $pembelianHeader->status = $row['status'] ?? 1;
                $pembelianHeader->modifiedby = auth('api')->user()->id;

                $pembelianHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $pembelianHeader->getTable(), date('Y-m-d', strtotime($row['tglbukti'])));

                $isHeader = true;
                $pembelianDetails = [];

                for ($i = 0; $i < count($row['productid']); $i++) {
                    $product = array_keys($row['productid'][$i]);
                    $productInfo = reset($product);
                    $qtyInfo = reset($row['qty'][$i]);
                    $productid = $row['productid'][$i];
                    $qty = 0;
                    $kartuStok = KartuStok::where('productid', $productInfo)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($kartuStok) {
                        if ($qtyInfo <= $kartuStok->qtysaldo) {
                            $qty = 0;
                        } else {
                            $qty = $qtyInfo - $kartuStok->qtysaldo;
                            $qty = $qty;
                        }
                    } else {
                        $qty = $qtyInfo;
                    }

                    if ($kartuStok) {
                        if ($qty == 0) {
                            $query = DB::table('penjualandetail')
                                ->select(
                                    'penjualanheader.id as pengeluaranid',
                                    'penjualanheader.tglbukti',
                                    "penjualanheader.nobukti as pengeluarannobukti",
                                    "penjualandetail.id as pengeluarandetailid",
                                    "penjualandetail.productid",
                                    "penjualandetail.qty as pengeluaranqty",
                                    "penjualandetail.harga as pengeluaranhargahpp",
                                    "product.hargabeli as pengeluaranharga",
                                    DB::raw('penjualandetail.qty * penjualandetail.harga as pengeluarantotalhpp'),
                                    DB::raw('penjualandetail.qty * product.hargabeli as pengeluarantotal'),
                                    "pesananfinaldetail.id as pesananfinaldetailid"
                                )
                                ->leftJoin("penjualanheader", "penjualandetail.penjualanid", "penjualanheader.id")
                                ->leftJoin("pesananfinaldetail", "penjualandetail.pesananfinaldetailid", "pesananfinaldetail.id")
                                ->leftJoin("pesananfinalheader", "pesananfinalheader.id", "pesananfinaldetail.pesananfinalid")
                                ->leftJoin("product", "penjualandetail.productid", "product.id")
                                ->where("penjualandetail.productid", $productInfo)
                                ->where("penjualanheader.tglpengiriman", $tglbukti)
                                ->where("penjualanheader.pesananfinalid", '!=', 0)
                                ->get();

                            foreach ($query as $fetch) {

                                $dataHpp = [
                                    "pengeluaranid" => $fetch->pengeluaranid,
                                    "tglbukti" => $fetch->tglbukti,
                                    "pengeluarannobukti" => $fetch->pengeluarannobukti,
                                    "pengeluarandetailid" => $fetch->pengeluarandetailid,
                                    "pengeluarannobukti" => $fetch->pengeluarannobukti,
                                    "productid" => $fetch->productid,
                                    "qtypengeluaran" => $fetch->pengeluaranqty,
                                    "hargapengeluaranhpp" => $fetch->pengeluaranhargahpp,
                                    "hargapengeluaran" => $fetch->pengeluaranharga,
                                    "totalpengeluaranhpp" => $fetch->pengeluarantotalhpp,
                                    "totalpengeluaran" => $fetch->pengeluarantotal,
                                    "flag" => 'PJ',
                                    "flagkartustok" => 'J',
                                    "seqno" => 2,
                                ];
                                $hpp = (new HPP())->processStore($dataHpp);
                                $pembelianheader = PembelianHeader::where('id', $hpp->penerimaanid)->first();
                                $pesananfinaldetail = PesananFinalDetail::where('id', $fetch->pesananfinaldetailid)->first();
                                if ($pesananfinaldetail) {
                                    $pesananfinaldetail->nobuktipembelian = $pembelianheader->nobukti;
                                    $pesananfinaldetail->save();
                                }
                            }
                        } else {
                            if ($isHeader) {
                                if (!$pembelianHeader->save()) {
                                    throw new \Exception("Error storing pembelian header.");
                                }
                                $pembelianHeaderLogTrail = (new LogTrail())->processStore([
                                    'namatabel' => strtoupper($pembelianHeader->getTable()),
                                    'postingdari' => strtoupper('ENTRY PEMBELIAN HEADER'),
                                    'idtrans' => $pembelianHeader->id,
                                    'nobuktitrans' => $pembelianHeader->nobukti,
                                    'aksi' => 'ENTRY',
                                    'datajson' => $pembelianHeader->toArray(),
                                    'modifiedby' => auth('api')->user()->user
                                ]);

                                // STORE HUTANG
                                $this->setRequestParameters();
                                $query = DB::table($this->table . ' as pembelianheader')
                                    ->select(
                                        "pembelianheader.supplierid",
                                        "pembelianheader.subtotal",
                                        "pembelianheader.potongan",
                                        "supplier.top",
                                        DB::raw('(pembelianheader.subtotal - pembelianheader.potongan) AS grandtotal'),
                                        "parameter.text as toptext",
                                    )
                                    ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
                                    ->leftJoin(DB::raw("parameter"), 'supplier.top', 'parameter.id')
                                    ->where("pembelianheader.id", $pembelianHeader->id)
                                    ->first();

                                $grandtotal = $query->grandtotal;
                                $top = $query->toptext;

                                if ($top == "CREDIT") {
                                    $tgljatuhtempo = date("Y-m-d", strtotime("+1 month", strtotime($row['tglbukti'])));
                                    $dataHutang = [
                                        "tglbukti" => $tglbukti,
                                        "pembelianid" => $pembelianHeader->id,
                                        "tglbuktipembelian" => $tglbukti,
                                        "tgljatuhtempo" => $tgljatuhtempo,
                                        "supplierid" => $row['supplierid'],
                                        "keterangan" => $row['keterangan'] ?? '',
                                        "nominalhutang" => $grandtotal,
                                        "nominalsisa" => $grandtotal,
                                        "tglcetak" => '2023-11-11',
                                        "status" => $row['status'] ?? 1,
                                    ];
                                } else {
                                    $tgljatuhtempo = $tglbukti;
                                    $dataHutang = [
                                        "tglbukti" => $tglbukti,
                                        "pembelianid" => $pembelianHeader->id,
                                        "tglbuktipembelian" => $tglbukti,
                                        "tgljatuhtempo" => $tgljatuhtempo,
                                        "supplierid" => $row['supplierid'],
                                        "keterangan" => $row['keterangan'] ?? '',
                                        "nominalhutang" => $grandtotal,
                                        "nominalbayar" => $grandtotal,
                                        "tglcetak" => '2023-11-11',
                                        "status" => $row['status'] ?? 1,
                                    ];
                                }
                                $hutang = (new Hutang())->processStore($dataHutang);

                                // if ($top == "CASH") {
                                //     //STORE TRANSAKSI BELANJA
                                //     $query = DB::table('hutang')
                                //         ->select(
                                //             "hutang.tglbukti",
                                //             "hutang.id",
                                //             "hutang.pembelianid",
                                //             "pembelianheader.nobukti",
                                //             "hutang.supplierid",
                                //             "supplier.karyawanid",
                                //             "hutang.nominalhutang"
                                //         )
                                //         ->leftJoin("supplier", "hutang.supplierid", "supplier.id")
                                //         ->leftJoin("pembelianheader", "hutang.pembelianid", "pembelianheader.id")
                                //         ->where("hutang.id", $hutang['id'])
                                //         ->where("hutang.tglbukti", $tglbukti);

                                //     $queryResult = $query->first();

                                //     $supplier = DB::table('supplier')
                                //         ->select('id', 'nama')
                                //         ->where('id', $pembelianHeader->supplierid)
                                //         ->first();
                                //     $keterangan = "nobukti: " . $pembelianHeader->nobukti . ", supplier: " . $supplier->nama;

                                //     if ($queryResult) {
                                //         $dataTrBelanja = (new TransaksiBelanja())->processStore([
                                //             "perkiraanid" => 1,
                                //             "tglbukti" => $queryResult->tglbukti,
                                //             "karyawanid" => $queryResult->karyawanid,
                                //             "pembelianid" => $queryResult->pembelianid,
                                //             "nominal" => $queryResult->nominalhutang,
                                //             "keterangan" => $keterangan
                                //         ]);
                                //     }
                                // }

                                $isHeader = false;
                            }

                            $pembelianDetail = (new PembelianDetail())->processStore($pembelianHeader, [
                                'pembelianid' => $pembelianHeader->id,
                                'productid' => $productid,
                                'satuanid' => $row['satuanid'][$i],
                                'keterangan' => $row['keterangandetail'][$i] ?? '',
                                'qtystok' => $kartuStok->qtysaldo ?? 0,
                                'qty' => $qty ?? 0,
                                'qtyretur' => $row['qtyretur'][$i] ?? 0,
                                'qtypesanan' => $qtyInfo ?? 0,
                                'qtyterpakai' => $row['qtyterpakai'][$i] ?? 0,
                                'harga' => $row['harga'][$i] ?? 0,
                                'modifiedby' => auth('api')->user()->id,
                            ]);
                            $pembelianDetails[] = $pembelianDetail->toArray();

                            (new LogTrail())->processStore([
                                'namatabel' => strtoupper($pembelianHeaderLogTrail->getTable()),
                                'postingdari' =>  strtoupper('ENTRY PEMBELIAN DETAIL'),
                                'idtrans' =>  $pembelianHeaderLogTrail->id,
                                'nobuktitrans' => $pembelianHeader->nobukti,
                                'aksi' => 'ENTRY',
                                'datajson' => $pembelianDetail,
                                'modifiedby' => auth('api')->user()->user,
                            ]);

                            $totalPembelianDetail = $qty * $row['harga'][$i];
                            $pembelianHeader->subtotal += $totalPembelianDetail;

                            //STORE KARTU STOK
                            $kartuStok = (new KartuStok())->processStore([
                                "tglbukti" => $tglbukti,
                                "penerimaandetailid" => $pembelianDetail->id,
                                "pengeluarandetailid" => 0,
                                "nobukti" => $pembelianHeader->nobukti,
                                "productid" => $pembelianDetail['productid'],
                                "qtypenerimaan" =>  $pembelianDetail['qty'],
                                "totalpenerimaan" =>  $pembelianDetail['qty'] * $pembelianDetail['harga'],
                                "qtypengeluaran" => 0,
                                "totalpengeluaran" => 0,
                                "flag" => 'B',
                                "seqno" => 1
                            ]);

                            $query = DB::table('penjualandetail')
                                ->select(
                                    'penjualanheader.pesananfinalid',
                                    'penjualanheader.tglbukti',
                                    'penjualanheader.id as pengeluaranid',
                                    "penjualanheader.nobukti as pengeluarannobukti",
                                    "penjualandetail.id as pengeluarandetailid",
                                    "penjualandetail.productid",
                                    "penjualandetail.qty as pengeluaranqty",
                                    "penjualandetail.harga as pengeluaranhargahpp",
                                    "pesananfinaldetail.hargabeli as pengeluaranharga",
                                    DB::raw('penjualandetail.qty * penjualandetail.harga as pengeluarantotalhpp'),
                                    DB::raw('penjualandetail.qty * pesananfinaldetail.hargabeli as pengeluarantotal'),
                                )
                                ->leftJoin("penjualanheader", "penjualandetail.penjualanid", "penjualanheader.id")
                                ->leftJoin("pesananfinaldetail", "penjualandetail.pesananfinaldetailid", "pesananfinaldetail.id")
                                ->leftJoin("pesananfinalheader", "pesananfinalheader.id", "pesananfinaldetail.pesananfinalid")
                                ->leftJoin("product", "penjualandetail.productid", "product.id")
                                ->where("penjualandetail.productid", $productInfo)
                                ->where("penjualanheader.tglpengiriman", $tglbukti)
                                ->where("penjualanheader.pesananfinalid", '!=', 0)
                                ->get();

                            foreach ($query as $fetch) {

                                $dataHpp = [
                                    "pengeluaranid" => $fetch->pengeluaranid,
                                    "tglbukti" => $fetch->tglbukti,
                                    "pengeluarannobukti" => $fetch->pengeluarannobukti,
                                    "pengeluarandetailid" => $fetch->pengeluarandetailid,
                                    "pengeluarannobukti" => $fetch->pengeluarannobukti,
                                    "productid" => $fetch->productid,
                                    "qtypengeluaran" => $fetch->pengeluaranqty,
                                    "hargapengeluaranhpp" => $fetch->pengeluaranhargahpp,
                                    "hargapengeluaran" => $fetch->pengeluaranharga,
                                    "totalpengeluaranhpp" => $fetch->pengeluarantotalhpp,
                                    "totalpengeluaran" => $fetch->pengeluarantotal,
                                    "flag" => 'PJ',
                                    "flagkartustok" => 'J',
                                    "seqno" => 2,
                                ];
                                $hpp = (new HPP())->processStore($dataHpp);
                            }
                        }
                    } else {
                        if ($isHeader) {
                            if (!$pembelianHeader->save()) {
                                throw new \Exception("Error storing pembelian header.");
                            }
                            $pembelianHeaderLogTrail = (new LogTrail())->processStore([
                                'namatabel' => strtoupper($pembelianHeader->getTable()),
                                'postingdari' => strtoupper('ENTRY PEMBELIAN HEADER'),
                                'idtrans' => $pembelianHeader->id,
                                'nobuktitrans' => $pembelianHeader->nobukti,
                                'aksi' => 'ENTRY',
                                'datajson' => $pembelianHeader->toArray(),
                                'modifiedby' => auth('api')->user()->user
                            ]);

                            // STORE HUTANG
                            $this->setRequestParameters();
                            $query = DB::table($this->table . ' as pembelianheader')
                                ->select(
                                    "pembelianheader.supplierid",
                                    "pembelianheader.subtotal",
                                    "pembelianheader.potongan",
                                    "supplier.top",
                                    DB::raw('(pembelianheader.subtotal - pembelianheader.potongan) AS grandtotal'),
                                    "parameter.text as toptext",
                                )
                                ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
                                ->leftJoin(DB::raw("parameter"), 'supplier.top', 'parameter.id')
                                ->where("pembelianheader.id", $pembelianHeader->id)
                                ->first();

                            $grandtotal = $query->grandtotal;
                            $top = $query->toptext;

                            if ($top == "CREDIT") {
                                $tgljatuhtempo = date("Y-m-d", strtotime("+1 month", strtotime($row['tglbukti'])));
                                $dataHutang = [
                                    "tglbukti" => $tglbukti,
                                    "pembelianid" => $pembelianHeader->id,
                                    "tglbuktipembelian" => $tglbukti,
                                    "tgljatuhtempo" => $tgljatuhtempo,
                                    "supplierid" => $row['supplierid'],
                                    "keterangan" => $row['keterangan'] ?? '',
                                    "nominalhutang" => $grandtotal,
                                    "nominalsisa" => $grandtotal,
                                    "tglcetak" => '2023-11-11',
                                    "status" => $row['status'] ?? 1,
                                ];
                            } else {
                                $tgljatuhtempo = $tglbukti;
                                $dataHutang = [
                                    "tglbukti" => $tglbukti,
                                    "pembelianid" => $pembelianHeader->id,
                                    "tglbuktipembelian" => $tglbukti,
                                    "tgljatuhtempo" => $tgljatuhtempo,
                                    "supplierid" => $row['supplierid'],
                                    "keterangan" => $row['keterangan'] ?? '',
                                    "nominalhutang" => $grandtotal,
                                    "nominalbayar" => $grandtotal,
                                    "tglcetak" => '2023-11-11',
                                    "status" => $row['status'] ?? 1,
                                ];
                            }
                            $hutang = (new Hutang())->processStore($dataHutang);

                            // if ($top == "CASH") {
                            //     //STORE TRANSAKSI BELANJA
                            //     $query = DB::table('hutang')
                            //         ->select(
                            //             "hutang.tglbukti",
                            //             "hutang.id",
                            //             "hutang.pembelianid",
                            //             "pembelianheader.nobukti",
                            //             "hutang.supplierid",
                            //             "supplier.karyawanid",
                            //             "hutang.nominalhutang"
                            //         )
                            //         ->leftJoin("supplier", "hutang.supplierid", "supplier.id")
                            //         ->leftJoin("pembelianheader", "hutang.pembelianid", "pembelianheader.id")
                            //         ->where("hutang.id", $hutang['id'])
                            //         ->where("hutang.tglbukti", $tglbukti);

                            //     $queryResult = $query->first();

                            //     $supplier = DB::table('supplier')
                            //         ->select('id', 'nama')
                            //         ->where('id', $pembelianHeader->supplierid)
                            //         ->first();
                            //     $keterangan = "nobukti: " . $pembelianHeader->nobukti . ", supplier: " . $supplier->nama;

                            //     if ($queryResult) {
                            //         $dataTrBelanja = (new TransaksiBelanja())->processStore([
                            //             "perkiraanid" => 1,
                            //             "tglbukti" => $queryResult->tglbukti,
                            //             "karyawanid" => $queryResult->karyawanid,
                            //             "pembelianid" => $queryResult->pembelianid,
                            //             "nominal" => $queryResult->nominalhutang,
                            //             "keterangan" => $keterangan
                            //         ]);
                            //     }
                            // }

                            $isHeader = false;
                        }

                        $pembelianDetail = (new PembelianDetail())->processStore($pembelianHeader, [
                            'pembelianid' => $pembelianHeader->id,
                            'productid' => $productid,
                            'satuanid' => $row['satuanid'][$i],
                            'keterangan' => $row['keterangandetail'][$i] ?? '',
                            'qtystok' => $kartuStok->qtysaldo ?? 0,
                            'qty' => $qty ?? 0,
                            'qtyretur' => $row['qtyretur'][$i] ?? 0,
                            'qtypesanan' => $qtyInfo ?? 0,
                            'qtyterpakai' => $row['qtyterpakai'][$i] ?? 0,
                            'harga' => $row['harga'][$i] ?? 0,
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                        $pembelianDetails[] = $pembelianDetail->toArray();

                        (new LogTrail())->processStore([
                            'namatabel' => strtoupper($pembelianHeaderLogTrail->getTable()),
                            'postingdari' =>  strtoupper('ENTRY PEMBELIAN DETAIL'),
                            'idtrans' =>  $pembelianHeaderLogTrail->id,
                            'nobuktitrans' => $pembelianHeader->nobukti,
                            'aksi' => 'ENTRY',
                            'datajson' => $pembelianDetail,
                            'modifiedby' => auth('api')->user()->user,
                        ]);

                        $totalPembelianDetail = $qty * $row['harga'][$i];
                        $pembelianHeader->subtotal += $totalPembelianDetail;

                        //STORE KARTU STOK
                        $kartuStok = (new KartuStok())->processStore([
                            "tglbukti" => $tglbukti,
                            "penerimaandetailid" => $pembelianDetail->id,
                            "pengeluarandetailid" => 0,
                            "nobukti" => $pembelianHeader->nobukti,
                            "productid" => $pembelianDetail['productid'],
                            "qtypenerimaan" =>  $pembelianDetail['qty'],
                            "totalpenerimaan" =>  $pembelianDetail['qty'] * $pembelianDetail['harga'],
                            "qtypengeluaran" => 0,
                            "totalpengeluaran" => 0,
                            "flag" => 'B',
                            "seqno" => 1
                        ]);

                        $query = DB::table('penjualandetail')
                            ->select(
                                'penjualanheader.pesananfinalid',
                                'penjualanheader.id as pengeluaranid',
                                'penjualanheader.tglbukti',
                                "penjualanheader.nobukti as pengeluarannobukti",
                                "penjualandetail.id as pengeluarandetailid",
                                "penjualandetail.productid",
                                "penjualandetail.qty as pengeluaranqty",
                                "penjualandetail.harga as pengeluaranhargahpp",
                                "pesananfinaldetail.hargabeli as pengeluaranharga",
                                DB::raw('penjualandetail.qty * penjualandetail.harga as pengeluarantotalhpp'),
                                DB::raw('penjualandetail.qty * pesananfinaldetail.hargabeli as pengeluarantotal'),
                            )
                            ->leftJoin("penjualanheader", "penjualandetail.penjualanid", "penjualanheader.id")
                            ->leftJoin("pesananfinaldetail", "penjualandetail.pesananfinaldetailid", "pesananfinaldetail.id")
                            ->leftJoin("pesananfinalheader", "pesananfinalheader.id", "pesananfinaldetail.pesananfinalid")
                            ->leftJoin("product", "penjualandetail.productid", "product.id")
                            ->where("penjualandetail.productid", $productInfo)
                            ->where("penjualanheader.tglpengiriman", $tglbukti)
                            ->where("penjualanheader.pesananfinalid", '!=', 0)
                            ->get();

                        foreach ($query as $fetch) {

                            $dataHpp = [
                                "pengeluaranid" => $fetch->pengeluaranid,
                                "tglbukti" => $fetch->tglbukti,
                                "pengeluarannobukti" => $fetch->pengeluarannobukti,
                                "pengeluarandetailid" => $fetch->pengeluarandetailid,
                                "pengeluarannobukti" => $fetch->pengeluarannobukti,
                                "productid" => $fetch->productid,
                                "qtypengeluaran" => $fetch->pengeluaranqty,
                                "hargapengeluaranhpp" => $fetch->pengeluaranhargahpp,
                                "hargapengeluaran" => $fetch->pengeluaranharga,
                                "totalpengeluaranhpp" => $fetch->pengeluarantotalhpp,
                                "totalpengeluaran" => $fetch->pengeluarantotal,
                                "flag" => 'PJ',
                                "flagkartustok" => 'J',
                                "seqno" => 2,
                            ];
                            $hpp = (new HPP())->processStore($dataHpp);
                        }
                    }
                }
            }
        }

        // dd($pembelianHeaderData);
        // $test = DB::table('pembeliandetail')
        //     ->select('*')
        //     ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
        //     ->where('pembelianheader.supplierid', 2)
        //     // ->where('pembelianheader.tglbukti', "2024-04-20")
        //     ->get();

        // dd($test);

        return $pembelianHeaderData;
    }

    public function processStore(array $row): PembelianHeader
    {
        $pembelianHeader = new PembelianHeader();

        /*STORE HEADER*/
        $group = 'PEMBELIAN HEADER BUKTI';
        $subGroup = 'PEMBELIAN HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();

        $tglbukti = date('Y-m-d', strtotime($row['tglbukti']));
        $tglterima = date('Y-m-d', strtotime($row['tglterima']));

        $pembelianHeader->tglbukti = $tglbukti ?? '';
        $pembelianHeader->supplierid = $row['supplierid'] ?? 0;
        $pembelianHeader->top = $row['top'] ?? 0;
        $pembelianHeader->karyawanid = $row['karyawanid'] ?? 0;
        $pembelianHeader->keterangan = $row['keterangan'] ?? '';
        $pembelianHeader->tglterima = $tglterima ?? '';
        $pembelianHeader->potongan = $row['potongan'] ?? 0;
        $pembelianHeader->subtotal = $row['subtotal'] ?? 0;
        $pembelianHeader->total = $row['total'] ?? 0;
        $pembelianHeader->status = $row['status'] ?? 1;
        $pembelianHeader->modifiedby = auth('api')->user()->id;

        $pembelianHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $pembelianHeader->getTable(), date('Y-m-d', strtotime($row['tglbukti'])));
        if (!$pembelianHeader->save()) {
            throw new \Exception("Error storing pembelian header.");
        }

        // dd($pembelianHeader);

        $pembelianHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pembelianHeader->getTable()),
            'postingdari' => strtoupper('ENTRY PEMBELIAN HEADER'),
            'idtrans' => $pembelianHeader->id,
            'nobuktitrans' => $pembelianHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pembelianHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        $pembelianDetails = [];

        for ($i = 0; $i < count($row['productid']); $i++) {
            $pembelianDetail = (new PembelianDetail())->processStore($pembelianHeader, [
                'pembelianid' => $pembelianHeader->id,
                'ismanual' => 1,
                'productid' => $row['productid'][$i],
                'satuanid' => $row['satuanid'][$i],
                'keterangan' => $row['keterangandetail'][$i] ?? '',
                'qtystok' => $row['qtystok'][$i] ?? 0,
                'qty' => $row['qty'][$i] ?? 0,
                'qtyretur' => $row['qtyretur'][$i] ?? 0,
                'qtypesanan' => $row['qty'][$i] ?? 0,
                'qtyterpakai' => $row['qtyterpakai'][$i] ?? 0,
                'harga' => $row['harga'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
            ]);

            // dd('test', $pembelianDetail);
            //STORE KARTU STOK
            $kartuStok = (new KartuStok())->processStore([
                "tglbukti" => $tglbukti,
                "penerimaandetailid" => $pembelianDetail->id,
                "pengeluarandetailid" => 0,
                "nobukti" => $pembelianHeader->nobukti,
                "productid" => $pembelianDetail['productid'],
                "qtypenerimaan" =>  $pembelianDetail['qty'],
                "totalpenerimaan" =>  $pembelianDetail['qty'] * $pembelianDetail['harga'],
                "qtypengeluaran" => 0,
                "totalpengeluaran" => 0,
                "flag" => 'B',
                "seqno" => 1
            ]);

            $pembelianDetails[] = $pembelianDetail->toArray();
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($pembelianHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY PEMBELIAN DETAIL'),
            'idtrans' =>  $pembelianHeaderLogTrail->id,
            'nobuktitrans' => $pembelianHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $pembelianDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

        // STORE HUTANG
        $this->setRequestParameters();
        $query = DB::table($this->table . ' as pembelianheader')
            ->select(
                "pembelianheader.supplierid",
                "pembelianheader.subtotal",
                "pembelianheader.potongan",
                // "supplier.top",
                "pembelianheader.top",
                DB::raw('(pembelianheader.subtotal - pembelianheader.potongan) AS grandtotal'),
                "parameter.text as toptext",
            )
            // ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
            // ->leftJoin(DB::raw("parameter"), 'supplier.top', 'parameter.id')
            ->leftJoin(DB::raw("parameter"), 'pembelianheader.top', 'parameter.id')
            ->where("pembelianheader.id", $pembelianHeader->id)
            ->first();

        $grandtotal = $query->grandtotal;
        $top = $query->toptext;

        if ($top == "CREDIT") {
            $tgljatuhtempo = date("Y-m-d", strtotime("+1 month", strtotime($row['tglbukti'])));
            $dataHutang = [
                "tglbukti" => $tglbukti,
                "pembelianid" => $pembelianHeader->id,
                "tglbuktipembelian" => $tglbukti,
                "tgljatuhtempo" => $tgljatuhtempo,
                "supplierid" => $row['supplierid'],
                "keterangan" => $row['keterangan'] ?? '',
                "nominalhutang" => $grandtotal,
                "nominalsisa" => $grandtotal,
                "tglcetak" => '2023-11-11',
                "status" => $row['status'] ?? 1,
            ];
        } else {
            $tgljatuhtempo = $tglbukti;
            $dataHutang = [
                "tglbukti" => $tglbukti,
                "pembelianid" => $pembelianHeader->id,
                "tglbuktipembelian" => $tglbukti,
                "tgljatuhtempo" => $tgljatuhtempo,
                "supplierid" => $row['supplierid'],
                "keterangan" => $row['keterangan'] ?? '',
                "nominalhutang" => $grandtotal,
                "nominalbayar" => $grandtotal,
                "tglcetak" => '2023-11-11',
                "status" => $row['status'] ?? 1,
            ];
        }
        $hutang = (new Hutang())->processStore($dataHutang);

        //STORE TRANSAKSI BELANJA (CASH)
        // if ($top == "CREDIT") {
        //     // dd('bvbvb');
        // } else {
        //     // dd('test');
        //     // $query = DB::table('hutang')
        //     //     ->select(
        //     //         "hutang.id",
        //     //         "hutang.tglbukti",
        //     //         "hutang.nominalhutang",
        //     //         "hutang.keterangan",
        //     //     )
        //     //     ->where("hutang.supplierid", $hutang->supplierid)
        //     //     ->where("hutang.tglbukti", $tglbukti)
        //     //     ->get();

        //     // $idHutang = array_column($query->toArray(), 'id');
        //     // $tglBuktiHutang = array_column($query->toArray(), 'tglbukti');
        //     // $nominalHutang = array_column($query->toArray(), 'nominalhutang');
        //     // $ketHutang = array_column($query->toArray(), 'keterangan');

        //     // $dataPelunasanHutang = [
        //     //     "tglbukti" => $tglbukti,
        //     //     "supplierid" => $hutang->supplierid,
        //     //     "keterangan" => $hutang->keterangan ?? '',
        //     //     "tglcetak" => '2023-11-11',
        //     //     "status" => $hutang->status ?? 1,
        //     //     "hutangid" => $idHutang,
        //     //     "pelunasanhutangid" => '',
        //     //     "tglbuktihutang" => $tglBuktiHutang,
        //     //     "nominalhutang" => $nominalHutang,
        //     //     "nominalbayar" => $nominalHutang,
        //     //     "nominalsisa" => 0,
        //     //     "keterangandetail" => $ketHutang,
        //     //     "nominalpotongan" => 0,
        //     //     "keteranganpotongan" => '',
        //     //     "nominalnotadebet" => 0,
        //     // ];
        //     // $pelunasanHutang = (new PelunasanHutangHeader())->processStore($dataPelunasanHutang);

        //     //STORE TRANSAKSI BELANJA
        //     $query = DB::table('hutang')
        //         ->select(
        //             "hutang.tglbukti",
        //             "hutang.id",
        //             "hutang.pembelianid",
        //             "pembelianheader.nobukti",
        //             "hutang.supplierid",
        //             "supplier.nama as suppliernama",
        //             "supplier.karyawanid",
        //             "hutang.nominalhutang"
        //         )
        //         ->leftJoin("supplier", "hutang.supplierid", "supplier.id")
        //         ->leftJoin("pembelianheader", "hutang.pembelianid", "pembelianheader.id")
        //         ->where("hutang.id", $hutang['id'])
        //         ->where("hutang.tglbukti", $tglbukti)
        //         ->get();
        //     $queryResults = $query->toArray();

        //     // dd($queryResults);

        //     $trBelanja = [];
        //     foreach ($queryResults as $result) {
        //         $keterangan = "nobukti: " . $pembelianHeader->nobukti . ", supplier: " . $result->suppliernama;

        //         $dataTrBelanja = (new TransaksiBelanja())->processStore([
        //             "perkiraanid" => 1,
        //             "tglbukti" => $result->tglbukti,
        //             "karyawanid" => $result->karyawanid,
        //             "pembelianid" => $result->pembelianid,
        //             "nominal" => $result->nominalhutang,
        //             "keterangan" => $keterangan
        //         ]);
        //         $trBelanja[] = $dataTrBelanja->toArray();
        //     }
        // }

        // die;
        return $pembelianHeader;
    }

    // public function processUpdateOld(PembelianHeader $pembelianHeader, array $data)
    // {
    //     $nobuktiOld = $pembelianHeader->nobukti;

    //     $group = 'PEMBELIAN HEADER BUKTI';
    //     $subGroup = 'PEMBELIAN HEADER BUKTI';
    //     $tglterima = date('Y-m-d', strtotime($data['tglterima']));

    //     $pembelianHeader->supplierid = $data['supplierid'] ?? 0;
    //     $pembelianHeader->karyawanid = $data['karyawanid'] ?? 0;
    //     $pembelianHeader->keterangan = $data['keterangan'] ?? '';
    //     $pembelianHeader->tglterima = $tglterima ?? '';
    //     $pembelianHeader->potongan = $data['potongan'] ?? 0;
    //     $pembelianHeader->subtotal = $data['subtotal'] ?? 0;
    //     // $pembelianHeader->subtotal = $data['total'] ?? 0;
    //     $pembelianHeader->status = $data['status'] ?? 1;
    //     $pembelianHeader->modifiedby = auth('api')->user()->id;

    //     if (!$pembelianHeader->save()) {
    //         throw new \Exception("Error updating Pembelian Header.");
    //     }

    //     // dd($pembelianHeader);

    //     $pembelianHeaderLogTrail = (new LogTrail())->processStore([
    //         'namatabel' => strtoupper($pembelianHeader->getTable()),
    //         'postingdari' => strtoupper('EDIT PEMBELIAN HEADER'),
    //         'idtrans' => $pembelianHeader->id,
    //         'nobuktitrans' => $pembelianHeader->nobukti,
    //         'aksi' => 'EDIT',
    //         'datajson' => $pembelianHeader->toArray(),
    //         'modifiedby' => auth('api')->user()->id
    //     ]);

    //     $query = DB::table($this->table . ' as pembelianheader')
    //         ->select(
    //             "pembelianheader.supplierid",
    //             "pembelianheader.potongan",
    //             DB::raw('(pembelianheader.subtotal + pembelianheader.potongan) AS subtotals'),
    //             DB::raw('(pembelianheader.subtotal - pembelianheader.potongan) AS grandtotal'),
    //             "supplier.top",
    //             "parameter.text as toptext",
    //         )
    //         ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
    //         ->leftJoin(DB::raw("parameter"), 'supplier.top', 'parameter.id')
    //         ->where("pembelianheader.id", $pembelianHeader->id)
    //         ->first();
    //     // dd($query);

    //     //UPDATE HUTANG
    //     $hutang = DB::table('hutang')
    //         ->where('hutang.pembelianid', $pembelianHeader->id)
    //         ->update([
    //             'nominalhutang' => $query->grandtotal,
    //             'nominalsisa' => $query->grandtotal,
    //         ]);

    //     // dd($t5est);

    //     /*DELETE PEMBELIAN DETAIL*/
    //     $pembelianDetail = PembelianDetail::where('pembelianid', $pembelianHeader->id)->lockForUpdate()->delete();
    //     $returDetails = [];
    //     $retur = 0;

    //     // dd($pembelianDetail);

    //     /*STORE PEMBELIAN DETAIL*/
    //     $pembelianDetails = [];
    //     for ($i = 0; $i < count($data['productid']); $i++) {
    //         $pembelianDetail = (new PembelianDetail())->processStore($pembelianHeader, [
    //             'pembelianid' => $pembelianHeader->id,
    //             'ismanual' => 1,
    //             // 'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
    //             'productid' => $data['productid'][$i],
    //             'satuanid' => $data['satuanid'][$i],
    //             'keterangan' => $data['keterangandetail'][$i] ?? '',
    //             'qtystok' => $data['qtystok'][$i] ?? 0,
    //             'qty' => $data['qty'][$i] ?? 0,
    //             'qtyretur' => $data['qtyretur'][$i] ?? 0,
    //             'qtypesanan' => $data['qtypesanan'][$i] ?? 0,
    //             'qtyterpakai' => $data['qtyterpakai'][$i] ?? 0,
    //             'harga' => $data['harga'][$i] ?? 0,
    //             'modifiedby' => auth('api')->user()->id,
    //         ]);
    //         $pembelianDetails[] = $pembelianDetail->toArray();

    //         // dd($pembelianDetail);

    //         //STORE KARTU STOK
    //         $kartuStok = (new KartuStok())->processStore([
    //             "tglbukti" => $pembelianHeader->tglbukti,
    //             "penerimaandetailid" => $pembelianDetail->id,
    //             "pengeluarandetailid" => 0,
    //             "nobukti" => $pembelianHeader->nobukti,
    //             "productid" => $pembelianDetail['productid'],
    //             "qtypenerimaan" =>  $pembelianDetail['qty'],
    //             "totalpenerimaan" =>  $pembelianDetail['qty'] * $pembelianDetail['harga'],
    //             "qtypengeluaran" => 0,
    //             "totalpengeluaran" => 0,
    //             "flag" => 'B',
    //             "seqno" => 1
    //         ]);

    //         //Update PesananFinalDetail
    //         $pesananFinalDetail = PesananFinalDetail::where('id', $pembelianDetail->pesananfinaldetailid)->first();
    //         if ($pesananFinalDetail) {
    //             $pesananFinalDetail->update([
    //                 'qtybeli' => $pembelianDetail->qty,
    //                 'qtyreturbeli' => $pembelianDetail->qtyretur,
    //                 'hargabeli' => $pembelianDetail->harga,
    //             ]);

    //             (new LogTrail())->processStore([
    //                 'namatabel' => $pesananFinalDetail->getTable(),
    //                 'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT PEMBELIAN',
    //                 'idtrans' => $pesananFinalDetail->id,
    //                 'nobuktitrans' => $pesananFinalDetail->id,
    //                 'aksi' => 'EDIT',
    //                 'datajson' => $pesananFinalDetail->toArray(),
    //                 'modifiedby' => auth('api')->user()->id,
    //             ]);
    //         }

    //         //Data Retur Beli
    //         if ($data['qtyretur'][$i] != 0) {
    //             $retur++;
    //             $returDetail = [
    //                 'pembeliandetailid' => $pembelianDetail->id,
    //                 'productid' => $data['productid'][$i],
    //                 'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
    //                 'keterangan' => $data['keterangandetail'][$i] ?? '',
    //                 'qty' => $data['qty'][$i] ?? 0,
    //                 'qtyretur' => $data['qtyretur'][$i] ?? 0,
    //                 'satuanid' => $data['satuanid'][$i] ?? '',
    //                 'harga' => $data['harga'][$i] ?? '',
    //                 'modifiedby' => auth('api')->user()->id,
    //             ];
    //             $returDetails[] = $returDetail;
    //         }
    //     }
    //     (new LogTrail())->processStore([
    //         'namatabel' => strtoupper($pembelianHeaderLogTrail->getTable()),
    //         'postingdari' =>  strtoupper('EDIT PEMBELIAN DETAIL'),
    //         'idtrans' =>  $pembelianHeaderLogTrail->id,
    //         'nobuktitrans' => $pembelianHeader->nobukti,
    //         'aksi' => 'ENTRY',
    //         'datajson' => $pembelianDetail,
    //         'modifiedby' => auth('api')->user()->user,
    //     ]);

    //     //Create Retur Beli
    //     if ($retur > 0) {
    //         $totalRetur = 0;
    //         $details = [];
    //         foreach ($returDetails as $detail) {
    //             $details['pesananfinaldetailid'][] = $detail['pesananfinaldetailid'];
    //             $details['pembeliandetailid'][] = $detail['pembeliandetailid'];
    //             $details['productid'][] = $detail['productid'];
    //             $details['satuanid'][] = $detail['satuanid'];
    //             $details['keterangan'][] = $detail['keterangan'];
    //             $details['qty'][] = $detail['qtyretur'];
    //             $details['harga'][] = $detail['harga'];
    //             $details['modifiedby'][] = $detail['modifiedby'];
    //             $totalRetur += $detail['harga'] * $detail['qtyretur'];
    //         }

    //         $returHeader = [
    //             'tglbukti' =>  now(),
    //             'pembelianid' => $pembelianHeader->id,
    //             'pembeliannobukti' => $pembelianHeader->nobukti,
    //             'supplierid' => $pembelianHeader->supplierid,
    //             'total' => $totalRetur,
    //             'flag' => 'generated',
    //         ];
    //         $result = array_merge($returHeader, $details);
    //     }

    //     if ($retur > 0) {
    //         return [
    //             'pembelianHeader' => $pembelianHeader,
    //             'resultRetur' => $result
    //         ];
    //     } else {
    //         return [
    //             'pembelianHeader' => $pembelianHeader,
    //             'resultRetur' => null
    //         ];
    //     }
    // }

    public function processUpdate(PembelianHeader $pembelianHeader, array $data)
    {
        // dd($data);
        $tempDetail = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempDetail (
            id INT UNSIGNED NULL,
            pembelianid INT NULL,
            ismanual INT NULL,
            pesananfinalid INT NULL,
            pesananfinaldetailid INT NULL,
            productid INT NULL,
            satuanid INT NULL,
            keterangan VARCHAR(500),
            qtystok FLOAT,
            qty FLOAT,
            qtyretur FLOAT,
            qtypesanan FLOAT,
            qtyterpakai FLOAT,
            harga FLOAT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        
        )");

        $nobuktiOld = $pembelianHeader->nobukti;

        $group = 'PEMBELIAN HEADER BUKTI';
        $subGroup = 'PEMBELIAN HEADER BUKTI';
        $tglterima = date('Y-m-d', strtotime($data['tglterima']));

        $pembelianHeader->supplierid = $data['supplierid'] ?? 0;
        $pembelianHeader->karyawanid = $data['karyawanid'] ?? 0;
        $pembelianHeader->keterangan = $data['keterangan'] ?? '';
        $pembelianHeader->tglterima = $tglterima ?? '';
        $pembelianHeader->potongan = $data['potongan'] ?? 0;
        $pembelianHeader->subtotal = $data['subtotal'] ?? 0;
        // $pembelianHeader->subtotal = $data['total'] ?? 0;
        $pembelianHeader->status = $data['status'] ?? 1;
        $pembelianHeader->modifiedby = auth('api')->user()->id;

        if (!$pembelianHeader->save()) {
            throw new \Exception("Error updating Pembelian Header.");
        }

        // dd($pembelianHeader);

        $pembelianHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($pembelianHeader->getTable()),
            'postingdari' => strtoupper('EDIT PEMBELIAN HEADER'),
            'idtrans' => $pembelianHeader->id,
            'nobuktitrans' => $pembelianHeader->nobukti,
            'aksi' => 'EDIT',
            'datajson' => $pembelianHeader->toArray(),
            'modifiedby' => auth('api')->user()->id
        ]);

        $query = DB::table($this->table . ' as pembelianheader')
            ->select(
                "pembelianheader.supplierid",
                "pembelianheader.potongan",
                DB::raw('(pembelianheader.subtotal + pembelianheader.potongan) AS subtotals'),
                DB::raw('(pembelianheader.subtotal - pembelianheader.potongan) AS grandtotal'),
                // "supplier.top",
                "pembelianheader.top",
                "parameter.text as toptext",
            )
            // ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
            // ->leftJoin(DB::raw("parameter"), 'supplier.top', 'parameter.id')
            ->leftJoin(DB::raw("parameter"), 'pembelianheader.top', 'parameter.id')
            ->where("pembelianheader.id", $pembelianHeader->id)
            ->first();

        //UPDATE HUTANG
        $hutang = DB::table('hutang')
            ->where('hutang.pembelianid', $pembelianHeader->id)
            ->update([
                'nominalhutang' => $query->grandtotal,
                'nominalsisa' => $query->grandtotal,
                'updated_at' => $pembelianHeader->updated_at
            ]);

        // dd($hutang);

        /*STORE PEMBELIAN DETAIL*/
        $returDetails = [];
        $retur = 0;
        for ($i = 0; $i < count($data['productid']); $i++) {

            DB::table($tempDetail)->insert([
                'id' => $data['id'][$i],
                'pembelianid' => $pembelianHeader->id,
                'ismanual' => 1,
                'pesananfinalid' => $data['pesananfinalid'][$i],
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
                'productid' => $data['productid'][$i],
                'satuanid' => $data['satuanid'][$i],
                'keterangan' => $data['keterangandetail'][$i] ?? '',
                'qtystok' => $data['qtystok'][$i] ?? 0,
                'qty' => $data['qty'][$i] ?? 0,
                'qtyretur' => $data['qtyretur'][$i] ?? 0,
                'qtypesanan' => $data['qtypesanan'][$i] ?? 0,
                'qtyterpakai' => $data['qtyterpakai'][$i] ?? 0,
                'harga' => $data['harga'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            //Data Retur Beli
            if ($data['qtyretur'][$i] != 0) {
                $retur++;
                $returDetail = [
                    'pembeliandetailid' => $data['id'][$i],
                    'productid' => $data['productid'][$i],
                    'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
                    'keterangan' => $data['keterangandetail'][$i] ?? '',
                    'qty' => $data['qty'][$i] ?? 0,
                    'qtyretur' => $data['qtyretur'][$i] ?? 0,
                    'satuanid' => $data['satuanid'][$i] ?? '',
                    'harga' => $data['harga'][$i] ?? '',
                    'modifiedby' => auth('api')->user()->id,
                ];
                $returDetails[] = $returDetail;
            }
        }

        // dd(DB::table($tempDetail)->get());

        //Create Retur Beli
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
                'total' => $totalRetur,
                'flag' => 'generated',
            ];
            $result = array_merge($returHeader, $details);
        }

        // UPDATE PEMBELIAN
        $queryUpdate = DB::table('pembeliandetail as a')
            ->join("pembelianheader as b", 'a.pembelianid', '=', 'b.id')
            ->join("$tempDetail as c", 'a.id', '=', 'c.id')
            ->update([
                'a.id' => DB::raw('c.id'),
                'a.pembelianid' => DB::raw('c.pembelianid'),
                'a.productid' => DB::raw('c.productid'),
                'a.satuanid' => DB::raw('c.satuanid'),
                'a.keterangan' => DB::raw('c.keterangan'),
                'a.qtystok' => DB::raw('c.qtystok'),
                'a.qty' => DB::raw('c.qty'),
                'a.qtyretur' => DB::raw('c.qtyretur'),
                'a.qtypesanan' => DB::raw('c.qtypesanan'),
                'a.qtyterpakai' => DB::raw('c.qtyterpakai'),
                'a.harga' => DB::raw('c.harga'),
                'a.modifiedby' => DB::raw('c.modifiedby'),
                'a.created_at' => DB::raw('c.created_at'),
                'a.updated_at' => DB::raw('c.updated_at')
            ]);

        // UPDATE PESANAN PEMBELIAN DETAIL
        $queryPesananpembelian =  DB::table("pesananpembeliandetail as a")
            ->leftJoin("$tempDetail as b", 'a.pembeliandetailid', '=', 'b.id')
            ->where("b.id", "!=", "0")
            ->where("b.ismanual", "=", "0")
            ->update([
                'a.pembeliandetailid' => DB::raw('b.id'),
                // 'a.pesananfinalid' => DB::raw('b.pesananfinalid'),
                // 'a.pesananfinaldetailid' => DB::raw('b.pesananfinaldetailid'),
                'a.productid' => DB::raw('b.productid'),
                'a.satuanid' => DB::raw('b.satuanid'),
                'a.keterangan' => DB::raw('b.keterangan'),
                'a.qty' => DB::raw('b.qty'),
                'a.harga' => DB::raw('b.harga'),

            ]);


        // UPDATE NO BUKTI PEMBELIAN
        $queryUpdatePesanandetail = DB::table("pesananfinaldetail as a")
            ->where("a.nobuktipembelian", "=", $pembelianHeader->nobukti)
            ->update([
                'a.nobuktipembelian' => $pembelianHeader->nobukti,
            ]);


        // DELETE PEMBELIAN DETAIL
        $queryDelete = DB::table('pembeliandetail as a')
            ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.id')
            ->whereNull('b.id')
            ->where('a.pembelianid', "=", $pembelianHeader->id)
            ->delete();

        if ($queryDelete) {
            $missingIds = DB::table($tempDetail)->pluck('id')->toArray();
            $kartuStok = DB::table('kartustok')
                ->select('*')
                ->whereIn('penerimaandetailid', $missingIds)
                ->get();

            $kartuStok->each(function ($item) {
                DB::table('kartustok')->where('id', $item->id)->delete();
            });
        }

        // insert pembelian header add row
        $insertAddRowQuery =  DB::table("$tempDetail as a")
            ->select(
                'a.id',
                'a.pembelianid',
                'a.productid',
                'a.satuanid',
                'a.keterangan',
                'a.qtystok',
                'a.qty',
                'a.qtyretur',
                'a.qtypesanan',
                'a.qtyterpakai',
                'a.harga',
                'a.modifiedby',
                'a.created_at',
                'a.updated_at'
            )
            ->where("a.id", '=', '0');

        DB::table('pembeliandetail')->insertUsing(["id", "pembelianid", "productid", "satuanid", "keterangan", "qtystok", "qty", "qtyretur", "qtypesanan", "qtyterpakai", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);


        if ($retur > 0) {
            return [
                'pembelianHeader' => $pembelianHeader,
                'resultRetur' => $result
            ];
        } else {
            return [
                'pembelianHeader' => $pembelianHeader,
                'resultRetur' => null
            ];
        }
    }

    public function processUpdateHpp($pembelianHeader, $data)
    {
        $cekHpp = DB::table('hpp')
            ->select('*')
            ->where('penerimaanid', $pembelianHeader->id)
            ->first();

        // dd($cekHpp);

        if (!$cekHpp) {
            // dd('test');
            $pembelianDetail = DB::table('pembeliandetail')
                ->select('*', 'pembelianheader.nobukti', 'pembeliandetail.id as pembeliandetailid', 'pembelianheader.tglbukti as tglbukti')
                ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
                ->where('pembelianid', $pembelianHeader->id)
                ->get();

            // dd($pembelianDetail);

            foreach ($pembelianDetail as $value) {
                $dataKartuStok = KartuStok::where('penerimaandetailid', $value->pembeliandetailid)->first();

                // dump($dataKartuStok);
                if ($dataKartuStok) {
                    $dataKartuStok->delete();
                }

                $kartuStok = (new KartuStok())->processStore([
                    "tglbukti" => $pembelianHeader->tglbukti,
                    "penerimaandetailid" => $value->pembeliandetailid,
                    "pengeluarandetailid" => 0,
                    "nobukti" => $pembelianHeader->nobukti,
                    "productid" => $value->productid,
                    "qtypenerimaan" =>  $value->qty,
                    "totalpenerimaan" =>  $value->qty * $value->harga,
                    "qtypengeluaran" => 0,
                    "totalpengeluaran" => 0,
                    "flag" => 'B',
                    "seqno" => 1
                ]);
            }
            // die;
            // dd(KartuStok::select('*')->get());

            // HAPUS KARTU STOK YG DELETE ROW
            // $fetchKartuStok = KartuStok::where('nobukti', $pembelianHeader->nobukti)->where('flag', 'B')->get();

            // // dd($fetchKartuStok);
            // $pembelianIds = $pembelianDetail->pluck('pembeliandetailid')->toArray();

            // dd($pembelianIds)+



            // $penerimaanIds = collect($fetchKartuStok)->pluck('penerimaandetailid')->toArray();
            // $missingIds = array_diff($penerimaanIds, $pembelianIds);
            // $missingIds = array_values($missingIds);

            // if ($missingIds) {
            //     $kartuStok = DB::table('kartustok')
            //         ->select('*')
            //         ->whereIn('penerimaandetailid', $missingIds)
            //         ->get();

            //     $kartuStok->each(function ($item) {
            //         DB::table('kartustok')->where('id', $item->id)->delete();
            //     });
            // }

            if ($data != null) {

                $dataReturBeli = ReturBeliHeader::where('pembelianid', $pembelianHeader->id)->where('flag', 'generated')->first();

                if (!$dataReturBeli) {
                    $returBeli = (new ReturBeliHeader())->processStore($data);
                }
            }
        } else {

            $tempHpp = 'tempHpp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
            DB::statement("CREATE TEMPORARY TABLE $tempHpp (
                id INT UNSIGNED,
                pengeluaranid INT UNSIGNED,
                flag VARCHAR(50),
                pengeluarannobukti VARCHAR(200),
                pengeluarandetailid INT UNSIGNED,
                penerimaanid INT UNSIGNED,
                penerimaandetailid INT UNSIGNED,
                productid INT UNSIGNED,
                pengeluaranqty FLOAT,
                penerimaanharga FLOAT,
                pengeluaranhargahpp FLOAT,
                penerimaantotal FLOAT,
                pengeluarantotalhpp FLOAT,
                profit FLOAT,
                position INT AUTO_INCREMENT PRIMARY KEY
            )");

            $hppRow = DB::table('hpp')
                ->select('*')
                ->where('penerimaanid', $pembelianHeader->id)
                ->first();

            $fetchDataHpp = DB::table('hpp')
                ->select(
                    'hpp.id',
                    'hpp.pengeluaranid',
                    'hpp.flag',
                    DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualanheader.tglbukti
                            WHEN hpp.flag = 'RB' THEN returbeliheader.tglbukti
                            ELSE NULL
                        END AS tglbukti
                    "),
                    DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualanheader.nobukti
                            WHEN hpp.flag = 'RB' THEN returbeliheader.nobukti
                            ELSE NULL
                        END AS pengeluarannobukti
                    "),
                    DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.id
                            WHEN hpp.flag = 'RB' THEN returbelidetail.id
                            ELSE NULL
                        END AS pengeluarandetailid
                    "),
                    'hpp.penerimaanid',
                    'hpp.penerimaandetailid',
                    'hpp.productid',
                    DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                            WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                            ELSE NULL
                        END AS pengeluaranqty
                    "),
                    'hpp.penerimaanharga',
                    DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.harga
                            WHEN hpp.flag = 'RB' THEN returbelidetail.harga
                            ELSE NULL
                        END AS pengeluaranhargahpp
                    "),
                    'hpp.penerimaantotal',
                    DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.harga * (pengeluaranqty - penjualandetail.qtyreturjual)
                            WHEN hpp.flag = 'RB' THEN returbelidetail.harga * returbelidetail.qty
                            ELSE NULL
                        END AS pengeluarantotalhpp
                    "),
                )
                ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
                ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
                ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
                ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
                ->where('hpp.id', '>=', $hppRow->id)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($fetchDataHpp as $value) {
                DB::table($tempHpp)->insert([
                    'id' => $value->id,
                    'pengeluaranid' => $value->pengeluaranid ?? 0,
                    'flag' => $value->flag ?? '',
                    'pengeluarannobukti' => $value->pengeluarannobukti ?? 0,
                    'pengeluarandetailid' => $value->pengeluarandetailid ?? 0,
                    'penerimaanid' => $value->penerimaanid ?? 0,
                    'penerimaandetailid' => $value->penerimaandetailid ?? 0,
                    'productid' => $value->productid ?? 0,
                    'pengeluaranqty' => $value->pengeluaranqty ?? 0,
                    'penerimaanharga' => $value->penerimaanharga ?? 0,
                    'pengeluaranhargahpp' => $value->pengeluaranhargahpp ?? 0,
                    'penerimaantotal' => $value->penerimaantotal ?? 0,
                    'pengeluarantotalhpp' => $value->pengeluarantotalhpp ?? 0,
                    'profit' => $value->profit ?? 0,
                ]);
            }

            foreach (DB::table($tempHpp)->get() as $value) {
                //UPDATE QTY TERPAKAI
                $pembelian = DB::table('pembeliandetail')
                    ->select(
                        'qty',
                        'qtyterpakai',
                        'productid'
                    )
                    ->where('id', $value->penerimaandetailid)
                    ->first();
                if ($pembelian) {
                    $qtyterpakai = $pembelian->qtyterpakai - $value->pengeluaranqty ?? 0;
                    $pembelianDetail = PembelianDetail::where('id', $value->penerimaandetailid)->first();
                    $pembelianDetail->qtyterpakai = $qtyterpakai;
                    $pembelianDetail->save();
                }

                //DELETE HPP
                $hpp = HPP::where('id', $value->id)->first();
                if ($hpp) {
                    $hpp->delete();
                }

                //DELETE KARTUSTOK
                $kartuStok = KartuStok::where('pengeluarandetailid', $value->pengeluarandetailid)->first();
                if ($kartuStok) {
                    $kartuStok->delete();
                }
            }

            $pembelianDetail = DB::table('pembeliandetail')
                ->select('*', 'pembelianheader.nobukti', 'pembeliandetail.id as pembeliandetailid')
                ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
                ->where('pembelianid', $hppRow->penerimaanid)
                ->get();

            // dd($pembelianDetail);

            foreach ($pembelianDetail as $value) {

                //DELETE KARTU STOK PEMBELIAN
                $dataKartuStok = KartuStok::where('penerimaandetailid', $value->pembeliandetailid)->first();
                if ($dataKartuStok) {
                    $dataKartuStok->delete();
                }

                //STORE KARTU STOK PEMBELIAN
                $kartuStok = (new KartuStok())->processStore([
                    "tglbukti" => $value->tglbukti,
                    "penerimaandetailid" => $value->pembeliandetailid,
                    "pengeluarandetailid" => 0,
                    "nobukti" => $value->nobukti,
                    "productid" => $value->productid,
                    "qtypenerimaan" =>  $value->qty,
                    "totalpenerimaan" =>  $value->qty * $value->harga,
                    "qtypengeluaran" => 0,
                    "totalpengeluaran" => 0,
                    "flag" => 'B',
                    "seqno" => 1
                ]);
            }

            //HAPUS KARTU STOK YG DELETE ROW
            $fetchKartuStok = KartuStok::where('nobukti', $pembelianHeader->nobukti)->where('flag', 'B')->get();
            $pembelianIds = $pembelianDetail->pluck('pembeliandetailid')->toArray();
            $penerimaanIds = collect($fetchKartuStok)->pluck('penerimaandetailid')->toArray();
            $missingIds = array_diff($penerimaanIds, $pembelianIds);
            $missingIds = array_values($missingIds);

            $kartuStok = DB::table('kartustok')
                ->select('*')
                ->whereIn('penerimaandetailid', $missingIds)
                ->get();

            $kartuStok->each(function ($item) {
                DB::table('kartustok')->where('id', $item->id)->delete();
            });

            // dd(DB::table($tempHpp)->get());

            //INSERT ULANG HPP
            foreach (DB::table($tempHpp)->get() as $value) {
                $flag = null;
                $flagkartustok = null;
                $seqno = 0;

                if ($value->pengeluarannobukti !== null) {
                    if (strpos($value->pengeluarannobukti, 'J') === 0) {
                        $flag = 'PJ';
                        $flagkartustok = 'J';
                        $seqno = 2;
                    } elseif (strpos($value->pengeluarannobukti, 'RB') === 0) {
                        $flag = 'RB';
                        $flagkartustok = 'RB';
                        $seqno = 4;
                    }
                }

                // dd($value);

                $dataHpp = [
                    "pengeluaranid" => $value->pengeluaranid,
                    "tglbukti" => $value->tglbukti,
                    "pengeluarannobukti" => $value->pengeluarannobukti,
                    "pengeluarandetailid" => $value->pengeluarandetailid,
                    "pengeluarannobukti" => $value->pengeluarannobukti,
                    "productid" => $value->productid,
                    "qtypengeluaran" => $value->pengeluaranqty,
                    "hargapengeluaranhpp" => $value->pengeluaranhargahpp,
                    "totalpengeluaranhpp" => $value->pengeluarantotalhpp,
                    "flag" => $flag,
                    "flagkartustok" => $flagkartustok,
                    "seqno" => $seqno,
                ];
                $hpp = (new HPP())->processStore($dataHpp);
            }

            //RETUR
            if ($data != null) {
                $dataReturBeli = ReturBeliHeader::where('pembelianid', $pembelianHeader->id)->where('flag', 'generated')->first();

                if ($dataReturBeli) {
                    $returBeliDetail = DB::table('returbelidetail')
                        ->select('*')
                        ->where('returbeliid', $dataReturBeli->id)
                        ->get();

                    if (!isset($data['id'])) {
                        $data['id'] = [];
                    }
                    $returBeliDetailIds = $returBeliDetail->pluck('id', 'pembeliandetailid')->toArray();

                    foreach ($data['pembeliandetailid'] as $pembeliandetailid) {
                        $data['id'][$pembeliandetailid] = isset($returBeliDetailIds[$pembeliandetailid]) ? $returBeliDetailIds[$pembeliandetailid] : 0;
                    }
                    $data['id'] = array_values($data['id']);

                    $returBeliHeader = ReturBeliHeader::findOrFail($dataReturBeli->id);
                    $returBeli = (new ReturBeliHeader())->processUpdate($returBeliHeader, $data);
                } else {
                    $returBeli = (new ReturBeliHeader())->processStore($data);
                }
            } else {
                $dataReturBeli = ReturBeliHeader::where('pembelianid', $pembelianHeader->id)->where('flag', 'generated')->first();
                if ($dataReturBeli) {
                    $returBeli = (new ReturBeliHeader())->processDestroy($dataReturBeli->id, "DELETE RETUR BELI HEADER");
                }
            }
        }

        return $pembelianHeader;
    }

    public function processDestroy($id, $postingDari = ''): PembelianHeader
    {
        $pembelianDetail = DB::table('pembeliandetail')
            ->select('pembeliandetail.id', 'pembeliandetail.qty', 'pembeliandetail.harga', 'pembeliandetail.productid', 'pembelianheader.nobukti')
            ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
            ->where('pembeliandetail.pembelianid', $id)
            ->get();

        foreach ($pembelianDetail as $detail) {
            $kartuStok = KartuStok::where('nobukti', $detail->nobukti)
                ->where('productid', $detail->productid)
                ->delete();
        }

        //DELETE HUTANG  
        $getHutang = DB::table("hutang")
            ->select('id', 'nobukti')
            ->where('pembelianid', '=', $id)
            ->first();

        $hutang = new Hutang();
        $hutang->processDestroy($getHutang->id);

        /*DELETE EXISTING PEMBELIAN HEADER*/
        $pembelianHeader = new PembelianHeader();
        $pembelianHeader = $pembelianHeader->lockAndDestroy($id);
        $pembelianHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $pembelianHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $pembelianHeader->id,
            'nobuktitrans' => $pembelianHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $pembelianHeader->toArray(),
            'modifiedby' => auth('api')->user()->id
        ]);

        $pembelianDetail = PembelianDetail::where('pembelianid', '=', $id)->get();
        $dataDetail = $pembelianDetail->toArray();
        (new LogTrail())->processStore([
            'namatabel' => 'PEMBELIAN DETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $pembelianHeaderLogTrail['id'],
            'nobuktitrans' => $pembelianHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->id
        ]);

        return $pembelianHeader;
    }

    public function cekValidasi($nobukti, $id)
    {
        $hutang = DB::table('hutang')
            ->from(
                DB::raw("hutang as a")
            )
            ->select(
                'a.pembelianid',
                'a.nominalhutang',
                'a.nominalsisa',
            )
            ->where('a.pembelianid', '=', $id)
            ->first();

        if ($hutang->nominalhutang != $hutang->nominalsisa) {

            if (request()->btn == 'DELETE') {
                $data = [
                    'kondisi' => true,
                    'btn' => 'true',
                    'keterangan' => 'Pembelian ' . $nobukti,
                    'kodeerror' => 'TBDPH'
                ];
            } else {
                $data = [
                    'kondisi' => true,
                    'keterangan' => 'Pembelian ' . $nobukti,
                    'kodeerror' => 'TBEPH'
                ];
            }
            goto selesai;
        }
        $data = [
            'kondisi' => false,
            'keterangan' => '',
        ];
        selesai:


        return $data;
    }

    public function cekValidasiAksi($nobukti, $id)
    {
        $pesananfinaldetail = DB::table('pesananfinaldetail')
            ->from(
                DB::raw("pesananfinaldetail as a")
            )
            ->select(
                'a.pesananfinalid',
                'a.nobuktipembelian'
            )
            ->where('a.nobuktipembelian', '=', $nobukti)
            ->first();

        if (isset($pesananfinaldetail)) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Pembelian ' . $pesananfinaldetail->nobuktipembelian,
                'kodeerror' => 'TDT'
            ];
            goto selesai;
        }
        $cekHpp = DB::table('hpp')
            ->leftJoin('pembeliandetail', 'pembeliandetail.id', '=', 'hpp.penerimaandetailid')
            ->where('hpp.penerimaanid', $id)
            ->get();

        if ($cekHpp->count() > 0) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'sudah dipakai di penjualan',
                'kodeerror' => 'SDP'
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

    public function processHapusPembelian($tglpengiriman)
    {
        // dd('test');
        $tglpengiriman = DateTime::createFromFormat('d-m-Y', $tglpengiriman)->format('Y-m-d');

        $this->setRequestParameters();
        $query = DB::table('pesananfinalheader as a')
            ->select(
                "a.nobukti",
                // "a.nobuktipenjualan",
                "b.nobuktipembelian",
                "a.status",
            )
            ->leftJoin(DB::raw("pesananfinaldetail as b"), 'a.id', 'b.pesananfinalid')
            ->where('a.status', 1)
            ->where("a.tglpengiriman", $tglpengiriman)
            ->where("b.nobuktipembelian", '')
            ->first();

        $nobuktipembelian = $query->nobuktipembelian ?? '';

        // dd($nobuktipembelian);

        if ($nobuktipembelian == '') {

            $fecth = DB::table('pesananfinalheader')
                ->select('pesananfinalheader.nobuktipenjualan', 'penjualanheader.id')
                ->leftJoin('penjualanheader', 'pesananfinalheader.nobuktipenjualan', 'penjualanheader.nobukti')
                ->where('pesananfinalheader.tglpengiriman', $tglpengiriman)
                ->get();

            foreach ($fecth as $row) {
                $hpps = DB::table('hpp')
                    ->select(
                        'pengeluaranid',
                        'penjualanheader.nobukti as nobuktipenjualan',
                        'pengeluarandetailid',
                        'penerimaanid',
                        'pembelianheader.nobukti as nobuktipembelian',
                        'penerimaandetailid',
                        'pengeluaranqty'
                    )
                    ->leftJoin('penjualanheader', 'hpp.pengeluaranid', 'penjualanheader.id')
                    ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
                    ->where('pengeluaranid', $row->id)
                    ->get();

                // dd($hpps);
                if ($hpps) {
                    foreach ($hpps as $fetch) {
                        // dd($fetch);
                        $pembelian = DB::table('pembeliandetail')
                            ->select(
                                'qty',
                                'qtyterpakai',
                                'productid',
                                // 'pesananfinalid'
                            )
                            ->where('id', $fetch->penerimaandetailid)
                            ->first();

                        // dd($pembelian);

                        $qtyterpakai = $pembelian->qtyterpakai - $fetch->pengeluaranqty;
                        $pembelian = PembelianDetail::where('id', $fetch->penerimaandetailid)->first();
                        $pembelian->qtyterpakai = $qtyterpakai;
                        $pembelian->save();

                        // dd($pembelian);

                        $hpp = HPP::where('pengeluaranid', $fetch->pengeluaranid)->delete();
                        $kartuStok = KartuStok::where('nobukti', $fetch->nobuktipenjualan)->delete();

                        $pembelianHeader = DB::table('pembelianheader')
                            ->whereIn('nobukti', function ($query) use ($tglpengiriman) {
                                $query->select('b.nobuktipembelian')
                                    ->from('pesananfinalheader as a')
                                    ->leftJoin('pesananfinaldetail as b', 'a.id', '=', 'b.pesananfinalid')
                                    ->where('a.tglpengiriman', $tglpengiriman)
                                    ->where('b.nobuktipembelian', '!=', '');
                            })->select("pembelianheader.nobukti", "pembelianheader.id")
                            ->get();
                        // dd($pembelianHeader);

                        foreach ($pembelianHeader as $pembelian) {
                            $fetch = DB::table('pembeliandetail')
                                ->select('pembeliandetail.pembelianid', 'pesananpembeliandetail.pesananfinalid')
                                ->leftJoin('pesananpembeliandetail', 'pembeliandetail.id', 'pesananpembeliandetail.pembeliandetailid')
                                ->where('pembeliandetail.pembelianid', $pembelian->id)
                                ->first();
                            // dd($fetch);

                            if ($fetch->pesananfinalid != 0) {
                                DB::table('kartustok')
                                    ->select("kartustok.nobukti")
                                    ->where('nobukti', $pembelian->nobukti)
                                    ->delete();
                            }
                        }
                    }
                }
            }

            $pembelianHeader = DB::table('pembelianheader')
                ->whereIn('nobukti', function ($query) use ($tglpengiriman) {
                    $query->select('b.nobuktipembelian')
                        ->from('pesananfinalheader as a')
                        ->leftJoin('pesananfinaldetail as b', 'a.id', '=', 'b.pesananfinalid')
                        ->where('a.tglpengiriman', $tglpengiriman)
                        ->where('b.nobuktipembelian', '!=', '');
                })->select("pembelianheader.nobukti", "pembelianheader.id")
                ->get();

            foreach ($pembelianHeader as $pembelian) {
                $fetch = DB::table('pembeliandetail')
                    ->select('pembeliandetail.pembelianid', 'pesananpembeliandetail.pesananfinalid')
                    ->leftJoin('pesananpembeliandetail', 'pembeliandetail.id', 'pesananpembeliandetail.pembeliandetailid')
                    ->where('pembeliandetail.pembelianid', $pembelian->id)
                    ->first();

                if ($fetch->pesananfinalid != 0) {
                    Hutang::where('pembelianid', $pembelian->id)->delete();
                    PembelianHeader::where('id', $pembelian->id)->delete();
                }
            }

            DB::table('pesananfinaldetail')
                ->whereIn('pesananfinalid', function ($query) use ($tglpengiriman) {
                    $query->select('a.id')
                        ->from('pesananfinalheader as a')
                        ->where('a.tglpengiriman', $tglpengiriman);
                })
                ->update(['nobuktipembelian' => '']);
        }

        // $test = DB::table('pembeliandetail')
        //     ->select('*')
        //     ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
        //     ->where('pembelianheader.supplierid', 2)
        //     // ->where('pembelianheader.tglbukti', "2024-04-20")
        //     ->get();

        // dd($test);

        return true;
    }

    public function editingAt($id, $btn)
    {

        $pembelianHeader = PembelianHeader::find($id);
        $oldUser = $pembelianHeader->editingby;
        if ($btn == 'EDIT') {
            $pembelianHeader->editingby = auth('api')->user()->name;
            $pembelianHeader->editingat = date('Y-m-d H:i:s');
        } else {

            if ($pembelianHeader->editingby == auth('api')->user()->name) {
                $pembelianHeader->editingby = '';
                $pembelianHeader->editingat = null;
            }
        }
        if (!$pembelianHeader->save()) {
            throw new \Exception("Error Update pembelian header.");
        }

        $pembelianHeader->oldeditingby = $oldUser;
        return $pembelianHeader;
    }

    public function processData($data)
    {
        // dd(request()->top, request()->topnama);
        $ids = [];
        $productIds = [];
        $satuanIds = [];
        $qtys = [];
        $qtyRetur = [];
        $keteranganDetails = [];
        $qtyStoks = [];
        $qtyTerpakais = [];
        $qtyPesanans = [];
        $hargas = [];
        $totalHargas = [];
        $pesananfinalIds = [];
        $pesananFinalDetails = [];

        foreach ($data as $detail) {
            $ids[] = $detail['id'];
            $productIds[] = $detail['productid'];
            $satuanIds[] = $detail['satuanid'];
            $qtys[] = $detail['qty'];
            $qtyRetur[] = $detail['qtyretur'];
            $keteranganDetails[] = $detail['keterangandetail'];
            $hargas[] = $detail['harga'];
            $qtyStoks[] = $detail['qtystok'];
            $qtyTerpakais[] = $detail['qtyterpakai'] ?? 0;
            $qtyPesanans[] = $detail['qtypesanan'];
            $totalHargas[] = $detail['totalharga'];
            $pesananfinalIds[] = $detail['pesananfinalid'];
            $pesananFinalDetails[] = $detail['pesananfinaldetailid'] ?? 0;
        }

        $data = [
            "tglbukti" => request()->tglbukti,
            "supplierid" => request()->supplierid,
            "top" => request()->top,
            "karyawanid" => request()->karyawanid,
            "tglterima" => request()->tglterima,
            "keterangan" => request()->keterangan,
            "status" => request()->status ?? 1,
            "subtotal" => request()->subtotal,
            "potongan" => request()->potongan,
            "total" => request()->total,
            "status" => request()->status,
            "id" =>  $ids,
            "productid" =>  $productIds,
            "qty" =>  $qtys,
            "satuanid" => $satuanIds,
            "harga" => $hargas,
            "totalharga" => $totalHargas,
            "qtyretur" =>  $qtyRetur,
            "qtystok" => $qtyStoks,
            "qtyterpakai" => $qtyTerpakais,
            "qtypesanan" => $qtyPesanans,
            "keterangandetail" => $keteranganDetails,
            "pesananfinaldetailid" =>  $pesananFinalDetails,
            "pesananfinalid" => $pesananfinalIds,
        ];

        return $data;
    }

    public function findEditAll()
    {
        $tglpengiriman = date('Y-m-d', strtotime(request()->tglpengirimanbeli));

        $data = DB::table("pesananfinalheader")
            ->select('id')
            ->where('tglpengiriman', $tglpengiriman)
            ->where('status', 1)
            ->pluck('id')
            ->toArray();

        $tglbukti = date('Y-m-d');

        if ($tglpengiriman == $tglbukti) {
            $tglbukti = date('Y-m-d', strtotime('-1 day'));
        }


        $this->setRequestParameters();
        $headers = DB::table('hutang as hutang')
            ->select(
                "hutang.nominalbayar",
                "pembelianheader.id",
                "pembelianheader.nobukti",
                "pembelianheader.tglbukti",
                "supplier.id as supplierid",
                "supplier.nama as suppliernama",
                "karyawan.id as karyawanid",
                "karyawan.nama as karyawannama",
                "pembelianheader.tglterima",
                "pembelianheader.keterangan",
                "top.id as topid",
                "top.text as topnama",
                DB::raw('IFNULL(pembelianheader.potongan, 0) AS potongan'),
                DB::raw('IFNULL(pembelianheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(pembelianheader.subtotal - pembelianheader.potongan,0) AS total'),
                // DB::raw('IFNULL(pembelianheader.total, 0) AS total'),
                "pembelianheader.potongan",
                "pembelianheader.tglcetak",
                "parameter.id as status",
                "parameter.text as statusnama",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pembelianheader.created_at',
                'pembelianheader.updated_at'
            )
            ->leftJoin(DB::raw("pembelianheader"), 'hutang.pembelianid', 'pembelianheader.id')
            ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("karyawan"), 'pembelianheader.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("parameter"), 'pembelianheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as top"), 'pembelianheader.top', 'top.id')
            ->leftJoin(DB::raw("user as modifier"), 'pembelianheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("pembeliandetail"), 'pembelianheader.id', 'pembeliandetail.pembelianid')
            ->whereIn("pembeliandetail.pesananfinalid", $data);
        // dd($headers->get());

        $getPanjar = DB::table('perkiraan')
            ->select(
                'id',
                'nama'
            )->where('nama', 'PANJAR')->first();

        // dd($getPanjar);

        $headers = DB::table('pembelianheader')
            ->select(
                DB::raw("'" . $getPanjar->id . "' as perkiraanid"),
                DB::raw("'" . $getPanjar->nama . "' as perkiraannama"),
                "hutang.nominalbayar",
                "pembelianheader.id",
                "pembelianheader.nobukti",
                "pembelianheader.tglbukti",
                "supplier.id as supplierid",
                "supplier.nama as suppliernama",
                "supplier.top as top",
                "karyawan.id as karyawanid",
                "karyawan.nama as karyawannama",
                "pembelianheader.tglterima",
                "pembelianheader.keterangan",
                "top.id as topid",
                "top.text as topnama",
                DB::raw('IFNULL(pembelianheader.potongan, 0) AS potongan'),
                DB::raw('IFNULL(pembelianheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(pembelianheader.subtotal - pembelianheader.potongan,0) AS total'),
                // DB::raw('IFNULL(pembelianheader.total, 0) AS total'),
                "pembelianheader.potongan",
                "pembelianheader.tglcetak",
                "parameter.id as status",
                "parameter.text as statusnama",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'pembelianheader.created_at',
                'pembelianheader.updated_at'
            )
            ->leftJoin(DB::raw("hutang"), 'hutang.pembelianid', 'pembelianheader.id')
            ->leftJoin(DB::raw("supplier"), 'pembelianheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("karyawan"), 'pembelianheader.karyawanid', 'karyawan.id')
            ->leftJoin(DB::raw("parameter"), 'pembelianheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as top"), 'pembelianheader.top', 'top.id')
            ->leftJoin(DB::raw("user as modifier"), 'pembelianheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("pembeliandetail"), 'pembelianheader.id', 'pembeliandetail.pembelianid')
            ->where("pembelianheader.tglbukti",  $tglpengiriman);



        if (request()->karyawanid != '') {
            $headers->where("karyawan.id",  request()->karyawanid);
        }

        // dd($headers->get());

        $this->totalRows = $headers->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($headers);
        $this->filter($headers);
        // $this->paginate($headers);
        $headers = $headers->get();

        // dd($headers);

        $groupedHeaders = $headers->groupBy('id')->map(function ($group) {
            return $group->first();
        })->values();


        $details = DB::table('pembeliandetail')
            ->select(
                DB::raw("MAX(pembeliandetail.id) as id"),
                DB::raw("MAX(pembelianheader.id) as pembelianid"),
                DB::raw("MAX(pembelianheader.nobukti) as pembeliannobukti"),
                DB::raw('IFNULL(MAX(pesananpembeliandetail.pesananfinalid), 0) AS pesananfinalid'),
                DB::raw('IFNULL(MAX(pesananpembeliandetail.pesananfinaldetailid), 0) AS pesananfinaldetailid'),
                DB::raw("MAX(product.id) as productid"),
                DB::raw("MAX(product.nama) as productnama"),
                DB::raw("MAX(pembeliandetail.keterangan) as keterangandetail"),
                DB::raw("MAX(pembeliandetail.qty) as qty"),
                DB::raw("MAX(pembeliandetail.qtyretur) as qtyretur"),
                DB::raw("MAX(pembeliandetail.qtystok) as qtystok"),
                DB::raw("MAX(pembeliandetail.qtypesanan) as qtypesanan"),
                DB::raw("MAX(pembeliandetail.harga) as harga"),
                DB::raw('MAX(pembeliandetail.qty * pembeliandetail.harga) AS totalharga'),
                DB::raw("MAX(satuan.nama) as satuannama"),
                DB::raw("MAX(satuan.id) as satuanid"),
                DB::raw("MAX(modifier.id) as modified_by_id"),
                DB::raw("MAX(modifier.name) as modified_by"),
                DB::raw("MAX(pembeliandetail.created_at) as created_at"),
                DB::raw("MAX(pembeliandetail.updated_at) as updated_at")
            )
            ->leftJoin(DB::raw("pembelianheader"), 'pembeliandetail.pembelianid', 'pembelianheader.id')
            ->leftJoin(DB::raw("product"), 'pembeliandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'pembeliandetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'pembeliandetail.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("pesananpembeliandetail"), 'pembeliandetail.id', 'pesananpembeliandetail.pembeliandetailid')
            ->whereIn('pesananpembeliandetail.pesananfinalid', $data)
            ->orderBy('productnama', 'asc')
            ->groupBy('pembeliandetail.id')
            ->get();

        // dd($details);


        $details = DB::table('pembeliandetail')
            ->select(
                DB::raw("MAX(pembeliandetail.id) as id"),
                DB::raw("MAX(pembelianheader.id) as pembelianid"),
                DB::raw("MAX(pembelianheader.nobukti) as pembeliannobukti"),
                DB::raw('IFNULL(MAX(pesananpembeliandetail.pesananfinalid), 0) AS pesananfinalid'),
                DB::raw('IFNULL(MAX(pesananpembeliandetail.pesananfinaldetailid), 0) AS pesananfinaldetailid'),
                DB::raw("MAX(product.id) as productid"),
                DB::raw("MAX(product.nama) as productnama"),
                DB::raw("MAX(pembeliandetail.keterangan) as keterangandetail"),
                DB::raw("MAX(pembeliandetail.qty) as qty"),
                DB::raw("MAX(pembeliandetail.qtyretur) as qtyretur"),
                DB::raw("MAX(pembeliandetail.qtystok) as qtystok"),
                DB::raw("MAX(pembeliandetail.qtypesanan) as qtypesanan"),
                DB::raw("MAX(pembeliandetail.harga) as harga"),
                DB::raw('MAX(pembeliandetail.qty * pembeliandetail.harga) AS totalharga'),
                DB::raw("MAX(satuan.nama) as satuannama"),
                DB::raw("MAX(satuan.id) as satuanid"),
                DB::raw("MAX(modifier.id) as modified_by_id"),
                DB::raw("MAX(modifier.name) as modified_by"),
                DB::raw("MAX(pembeliandetail.created_at) as created_at"),
                DB::raw("MAX(pembeliandetail.updated_at) as updated_at")
            )
            ->leftJoin(DB::raw("pembelianheader"), 'pembeliandetail.pembelianid', 'pembelianheader.id')
            ->leftJoin(DB::raw("product"), 'pembeliandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'pembeliandetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'pembeliandetail.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("pesananpembeliandetail"), 'pembeliandetail.id', 'pesananpembeliandetail.pembeliandetailid')
            ->where('pembelianheader.tglbukti', $tglpengiriman)
            ->groupBy('pembeliandetail.id')
            ->get();

        // dd($details);
        $groupedDetails = $details->groupBy('pembelianid');

        // dd($groupedDetails);



        $result = $groupedHeaders->map(function ($header) use ($groupedDetails) {
            $getTop = DB::table('parameter')
                ->select(
                    'id',
                    'text'
                )->where('id', $header->top)->first();

            return [
                'id' => $header->id,
                'nobukti' => $header->nobukti,
                'tglbukti' => $header->tglbukti,
                'supplierid' => $header->supplierid,
                'suppliernama' => $header->suppliernama,
                'top' =>  $getTop->text,
                'topid' =>  $header->topid,
                'topnama' =>  $header->topnama,
                'perkiraanid' =>  $header->perkiraanid,
                'perkiraannama' =>  $header->perkiraannama,
                'karyawanid' => $header->karyawanid ?? 0,
                'karyawannama' => $header->karyawannama ?? '',
                'tglterima' => $header->tglterima,
                'nominalbayar' => $header->nominalbayar,
                'keterangan' => $header->keterangan,
                'potongan' => $header->potongan,
                'subtotal' => $header->subtotal,
                'total' => $header->total,
                'tglcetak' => $header->tglcetak,
                'status' => $header->status,
                'statusnama' => $header->statusnama,
                'modifiedby' => $header->modifiedby,
                'modifiedby_name' => $header->modifiedby_name,
                'details' => $groupedDetails->get($header->id, []),
            ];
        });

        $data = $result->toArray();
        // dd($data);
        $totalCredit = 0;
        $totalCash = 0;
        foreach ($data as $pembelianheader) {

            if ($pembelianheader['top'] == 'CREDIT') {
                $totalCredit += $pembelianheader['total'];
            } elseif ($pembelianheader['top'] == 'CASH') {
                $totalCash += $pembelianheader['total'];
            }
        }
        $this->totalCredit = $totalCredit;
        $this->totalCash =   $totalCash;
        return $data;
    }

    public function processEditAllOld($dataPembelian)
    {
        $results = [];
        foreach ($dataPembelian as $data) {
            if (empty($data)) {
                continue;
            }

            $idToUpdate = $data['id'];

            $pembelianHeader = PembelianHeader::find($idToUpdate);

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
                    'postingdari' => 'EDIT PEMBELIAN HEADER DARI EDIT ALL PEMBELIAN',
                    'idtrans' => $pembelianHeader->id,
                    'nobuktitrans' => $pembelianHeader->id,
                    'aksi' => 'EDIT',
                    'datajson' => $pembelianHeader->toArray(),
                    'modifiedby' => auth('api')->user()->id,
                ]);

                $returDetails = [];
                $retur = 0;

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
                            'postingdari' => 'EDIT PEMBELIAN DETAIL DARI EDIT ALL PEMBELIAN',
                            'idtrans' => $detail->id,
                            'nobuktitrans' => $detail->id,
                            'aksi' => 'EDIT',
                            'datajson' => $detail->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }

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
                            'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT ALL PEMBELIAN',
                            'idtrans' => $pesananFinalDetail->id,
                            'nobuktitrans' => $pesananFinalDetail->id,
                            'aksi' => 'EDIT',
                            'datajson' => $pesananFinalDetail->toArray(),
                            'modifiedby' => auth('api')->user()->id,
                        ]);
                    }

                    // Update Product
                    // $product = Product::find($data['details']['productid[]'][$index]);
                    // if ($product) {
                    //     $product->hargabeli = $data['details']['harga[]'][$index];
                    //     $product->save();

                    //     // Log the update in LogTrail
                    //     (new LogTrail())->processStore([
                    //         'namatabel' => 'product',
                    //         'postingdari' => 'EDIT PRODUCT DARI EDIT ALL PEMBELIAN',
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
                        'postingdari' => 'EDIT HUTANG DARI EDIT ALL PEMBELIAN',
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
                    $results[] = $result;
                    // $returBeli = (new ReturBeliHeader())->processStore($result);
                }
            }
        }

        if (!empty($results)) {
            return [
                'pembelianHeader' => $dataPembelian,
                'resultRetur' => $results
            ];
        } else {
            return [
                'pembelianHeader' => $dataPembelian,
                'resultRetur' => null
            ];
        }
    }

    public function processEditAll($dataPembelian)
    {
        $dataPembelian = array_filter($dataPembelian);

        $dataPembelian = array_values($dataPembelian);

        $results = [];

        $tempHeader = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempHeader (
            id INT UNSIGNED NULL,
            supplierid INT,
            top INT,
            nobukti VARCHAR(100),
            karyawanid INT,
            tglterima DATE,
            tglbukti DATE,
            keterangan VARCHAR(500),
            subtotal VARCHAR(500),
            potongan VARCHAR(500),
            total VARCHAR(500),
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        )");

        $tempDetail = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempDetail (
            id INT UNSIGNED NULL,
            pembelianid INT NULL,
            productid INT NULL,
            keterangan VARCHAR(500),
            qty FLOAT,
            qtystok FLOAT,
            qtypesanan FLOAT,
            qtyretur FLOAT,
            qtyterpakai FLOAT,
            satuanid INT NULL,
            harga FLOAT,
            totalharga FLOAT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME

        )");

        foreach ($dataPembelian as $data) {
            if (empty($data)) {
                continue;
            }
            DB::table($tempHeader)->insert([
                'id' => $data['id'],
                'supplierid' => $data['supplierid'],
                'top' => $data['top'],
                'nobukti' => $data['nobukti'],
                'karyawanid' => $data['karyawanid'],
                'tglterima' => date('Y-m-d', strtotime($data['tglterima'])),
                'tglbukti' => date('Y-m-d', strtotime($data['tglbuktieditall'])),
                'keterangan' => $data['keterangan'],
                'subtotal' => $data['subtotal'],
                'potongan' => $data['potongan'],
                'total' => $data['total'],
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            foreach ($data['details']['productnama[]'] as $index => $productName) {

                $insert =  DB::table($tempDetail)->insert([
                    'id' => $data['details']['iddetail[]'][$index],
                    'pembelianid' => $data['details']['idheader[]'][$index],
                    'productid' => $data['details']['productid[]'][$index],
                    'keterangan' => $data['details']['keterangandetail[]'][$index] ?? "",
                    'qty' => $data['details']['qty[]'][$index],
                    'qtystok' => $data['details']['qtystok[]'][$index],
                    'qtypesanan' => $data['details']['qtypesanan[]'][$index],
                    'qtyretur' => $data['details']['qtyretur[]'][$index],
                    'satuanid' => $data['details']['satuanid[]'][$index],
                    'harga' => $data['details']['harga[]'][$index],
                    'totalharga' => $data['details']['totalharga[]'][$index],
                    'modifiedby' => auth('api')->user()->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // update pembelian header
        $queryUpdate =  DB::table('pembelianheader as a')
            ->join("$tempHeader as b", 'a.id', '=', 'b.id')
            ->update([
                'a.id' => DB::raw('b.id'),
                'a.nobukti' => DB::raw('b.nobukti'),
                'a.top' => DB::raw('b.top'),
                'a.tglbukti' => DB::raw('b.tglbukti'),
                'a.karyawanid' => DB::raw('b.karyawanid'),
                'a.tglterima' => DB::raw('b.tglterima'),
                'a.keterangan' => DB::raw('b.keterangan'),
                'a.potongan' => DB::raw('b.potongan'),
                'a.subtotal' => DB::raw('b.subtotal'),
                'a.total' => DB::raw('b.total'),
                'a.modifiedby' => DB::raw('b.modifiedby'),
                'a.created_at' => DB::raw('b.created_at'),
                'a.updated_at' => DB::raw('b.updated_at')

            ]);

        // dd('test');

        // update pesanan final detail
        // $pesananDetail = DB::table("pesananfinaldetail as a")
        // ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.pesananfinaldetailid')
        // ->where("b.pesananfinaldetailid", "!=", "0")
        // ->update([
        //     'a.qtyreturbeli' => DB::raw('b.qtyretur'),
        //     'a.keterangan' => DB::raw('b.keterangan'),
        //     'a.hargabeli' => DB::raw('b.harga'),
        //     'a.modifiedby' => DB::raw('b.modifiedby'),
        //     'a.created_at' => DB::raw('b.created_at'),
        //     'a.updated_at' => DB::raw('b.updated_at')
        // ]);

        // update pembelian detail 
        $pembelianDetail = DB::table('pembeliandetail as a')
            ->join("pembelianheader as b", 'a.pembelianid', '=', 'b.id')
            ->join("$tempDetail as c", 'a.id', '=', 'c.id')
            ->update([
                'a.id' => DB::raw('c.id'),
                'a.pembelianid' => DB::raw('c.pembelianid'),
                'a.productid' => DB::raw('c.productid'),
                'a.keterangan' => DB::raw('c.keterangan'),
                'a.qty' => DB::raw('c.qty'),
                'a.qtystok' => DB::raw('c.qtystok'),
                'a.qtypesanan' => DB::raw('c.qtypesanan'),
                'a.qtyretur' => DB::raw('c.qtyretur'),
                'a.satuanid' => DB::raw('c.satuanid'),
                'a.harga' => DB::raw('c.harga'),
                'a.modifiedby' => DB::raw('c.modifiedby'),
                'a.created_at' => DB::raw('c.created_at'),
                'a.updated_at' => DB::raw('c.updated_at')
            ]);

        // delete pembelian
        $delete = DB::table('pembeliandetail as a')
            ->join("$tempHeader as b", 'a.pembelianid', '=', 'b.id')
            ->leftJoin("$tempDetail as c", 'a.id', '=', 'c.id')
            ->whereNull('c.id')
            ->delete();

        // dd('masuk');

        // insert addRow
        $insertAddRowQuery =  DB::table($tempDetail)
            ->select(
                "id",
                "pembelianid",
                "productid",
                "satuanid",
                "keterangan",
                "qtystok",
                "qty",
                "qtyretur",
                "qtypesanan",
                DB::raw("0 as qtyterpakai"),
                "harga",
                "modifiedby",
                "created_at",
                "updated_at",
            )
            ->where("id", '=', '0');

        DB::table('pembeliandetail')->insertUsing(["id", "pembelianid", "productid", "satuanid", "keterangan", "qtystok", "qty", "qtyretur", "qtypesanan", "qtyterpakai", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

        $newlyInsertedIds = DB::table('pembeliandetail')->pluck('id');

        // dd($newlyInsertedIds);
        $updatedDataPembelian = [];

        DB::table('pembeliandetail')->insertUsing(["id", "pembelianid", "productid", "satuanid", "keterangan", "qtystok", "qty", "qtyretur", "qtypesanan", "qtyterpakai", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

        $newlyInsertedIds = DB::table('pembeliandetail')->pluck('id');



        $updatedDataPembelian = [];
        foreach ($dataPembelian as $pembelian) {
            $updatedPembelian = $pembelian;

            foreach ($pembelian['details']['productnama[]'] as $index => $productName) {
                if ($pembelian['details']['iddetail[]'][$index] == "0") {
                    $updatedPembelian['details']['iddetail[]'][$index] = $newlyInsertedIds->pop();
                }
            }
            $updatedDataPembelian[] = $updatedPembelian;
        }


        foreach ($dataPembelian as $data) {

            $idToUpdate = $data['id'];
            $pembelianHeader = PembelianHeader::find($idToUpdate);

            if ($pembelianHeader) {
                $returDetails = [];
                $retur = 0;

                foreach ($data['details']['productnama[]'] as $index => $productName) {
                    $detailId = $data['details']['iddetail[]'][$index];
                    $detail = PembelianDetail::find($detailId);
                    // dump($data['details']['qtyretur[]'][$index]);
                    if ($detail) {

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
                    }
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
                        'postingdari' => 'EDIT HUTANG DARI EDIT ALL PEMBELIAN',
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
                        'total' => $totalRetur,
                        'flag' => 'generated'
                    ];

                    $result = array_merge($returHeader, $details);
                    $results[] = $result;
                    // $returBeli = (new ReturBeliHeader())->processStore($result);
                }
            }
        }

        if (!empty($results)) {
            return [
                'pembelianHeader' => $updatedDataPembelian,
                'resultRetur' => $results
            ];
        } else {
            return [
                'pembelianHeader' => $updatedDataPembelian,
                'resultRetur' => null
            ];
        }
    }

    public function processEditHpp($pembelianHeader, $data)
    {
        foreach ($pembelianHeader as $dataPembelian) {

            $cekHpp = DB::table('hpp')
                ->select('*')
                ->where('penerimaanid', $dataPembelian['id'])
                ->first();

            if (!$cekHpp) {

                $pembelianDetail = DB::table('pembeliandetail')
                    ->select('*', 'pembelianheader.nobukti', 'pembeliandetail.id as pembeliandetailid', 'pembelianheader.tglbukti as tglbukti')
                    ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
                    ->where('pembelianid', $dataPembelian['id'])
                    ->get();

                foreach ($pembelianDetail as $value) {
                    $dataKartuStok = KartuStok::where('penerimaandetailid', $value->pembeliandetailid)->first();
                    if ($dataKartuStok) {
                        $dataKartuStok->delete();
                    }

                    $tglbukti = date('Y-m-d', strtotime($dataPembelian['tglbuktieditall']));
                    $totalpenerimaan = $value->qty * $value->harga;

                    $kartuStok = (new KartuStok())->processStore([
                        "tglbukti" => $tglbukti,
                        "penerimaandetailid" => $value->pembeliandetailid,
                        "pengeluarandetailid" => 0,
                        "nobukti" => $dataPembelian['nobukti'],
                        "productid" => $value->productid,
                        "qtypenerimaan" =>  $value->qty,
                        "totalpenerimaan" =>  $totalpenerimaan,
                        "qtypengeluaran" => 0,
                        "totalpengeluaran" => 0,
                        "flag" => 'B',
                        "seqno" => 1
                    ]);
                }

                //HAPUS KARTU STOK YG DELETE ROW
                $fetchKartuStok = KartuStok::where('nobukti', $dataPembelian['nobukti'])->where('flag', 'B')->get();
                $pembelianIds = $pembelianDetail->pluck('pembeliandetailid')->toArray();
                $penerimaanIds = collect($fetchKartuStok)->pluck('penerimaandetailid')->toArray();
                $missingIds = array_diff($penerimaanIds, $pembelianIds);
                $missingIds = array_values($missingIds);

                // dd($missingIds);
                if ($missingIds) {
                    $kartuStok = DB::table('kartustok')
                        ->select('*')
                        ->whereIn('penerimaandetailid', $missingIds)
                        ->get();

                    $kartuStok->each(function ($item) {
                        DB::table('kartustok')->where('id', $item->id)->delete();
                    });
                }

                if ($data != null) {
                    foreach ($data as $value) {
                        if ($dataPembelian['nobukti'] == $value['pembeliannobukti']) {
                            $dataReturBeli = ReturBeliHeader::where('pembelianid', $dataPembelian['id'])->where('flag', 'generated')->first();

                            if (!$dataReturBeli) {
                                $returBeli = (new ReturBeliHeader())->processStore($value);
                            }
                        }
                    }
                }
            } else {

                $tempHpp = 'tempHpp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
                DB::statement("CREATE TEMPORARY TABLE $tempHpp (
                    id INT UNSIGNED,
                    pengeluaranid INT UNSIGNED,
                    flag VARCHAR(50),
                    pengeluarannobukti VARCHAR(200),
                    pengeluarandetailid INT UNSIGNED,
                    penerimaanid INT UNSIGNED,
                    penerimaandetailid INT UNSIGNED,
                    productid INT UNSIGNED,
                    pengeluaranqty FLOAT,
                    penerimaanharga FLOAT,
                    pengeluaranhargahpp FLOAT,
                    penerimaantotal FLOAT,
                    pengeluarantotalhpp FLOAT,
                    profit FLOAT,
                    position INT AUTO_INCREMENT PRIMARY KEY
                )");

                $hppRow = DB::table('hpp')
                    ->select('*')
                    ->where('penerimaanid', $dataPembelian['id'])
                    ->first();

                // dd($hppRow);
                $fetchDataHpp = DB::table('hpp')
                    ->select(
                        'hpp.id',
                        'hpp.pengeluaranid',
                        'hpp.flag',
                        DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualanheader.tglbukti
                            WHEN hpp.flag = 'RB' THEN returbeliheader.tglbukti
                            ELSE NULL
                        END AS tglbukti
                    "),
                        DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualanheader.nobukti
                            WHEN hpp.flag = 'RB' THEN returbeliheader.nobukti
                            ELSE NULL
                        END AS pengeluarannobukti
                    "),
                        DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.id
                            WHEN hpp.flag = 'RB' THEN returbelidetail.id
                            ELSE NULL
                        END AS pengeluarandetailid
                    "),
                        'hpp.penerimaanid',
                        'hpp.penerimaandetailid',
                        'hpp.productid',
                        DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                            WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                            ELSE NULL
                        END AS pengeluaranqty
                    "),
                        'hpp.penerimaanharga',
                        DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.harga
                            WHEN hpp.flag = 'RB' THEN returbelidetail.harga
                            ELSE NULL
                        END AS pengeluaranhargahpp
                    "),
                        'hpp.penerimaantotal',
                        DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.harga * (pengeluaranqty - penjualandetail.qtyretur)
                            WHEN hpp.flag = 'RB' THEN returbelidetail.harga * returbelidetail.qty
                            ELSE NULL
                        END AS pengeluarantotalhpp
                    "),
                    )
                    ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
                    ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
                    ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
                    ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
                    ->where('hpp.id', '>=', $hppRow->id)
                    ->orderBy('id', 'asc')
                    ->get();

                // dd($fetchDataHpp);

                foreach ($fetchDataHpp as $value) {
                    DB::table($tempHpp)->insert([
                        'id' => $value->id,
                        'pengeluaranid' => $value->pengeluaranid ?? 0,
                        'flag' => $value->flag ?? '',
                        'pengeluarannobukti' => $value->pengeluarannobukti ?? 0,
                        'pengeluarandetailid' => $value->pengeluarandetailid ?? 0,
                        'penerimaanid' => $value->penerimaanid ?? 0,
                        'penerimaandetailid' => $value->penerimaandetailid ?? 0,
                        'productid' => $value->productid ?? 0,
                        'pengeluaranqty' => $value->pengeluaranqty ?? 0,
                        'penerimaanharga' => $value->penerimaanharga ?? 0,
                        'pengeluaranhargahpp' => $value->pengeluaranhargahpp ?? 0,
                        'penerimaantotal' => $value->penerimaantotal ?? 0,
                        'pengeluarantotalhpp' => $value->pengeluarantotalhpp ?? 0,
                        'profit' => $value->profit ?? 0,
                    ]);
                }

                // dd(DB::table($tempHpp)->get());

                foreach (DB::table($tempHpp)->get() as $value) {
                    //UPDATE QTY TERPAKAI
                    $pembelian = DB::table('pembeliandetail')
                        ->select(
                            'qty',
                            'qtyterpakai',
                            'productid'
                        )
                        ->where('id', $value->penerimaandetailid)
                        ->first();
                    if ($pembelian) {
                        $qtyterpakai = $pembelian->qtyterpakai - $value->pengeluaranqty ?? 0;
                        $pembelianDetail = PembelianDetail::where('id', $value->penerimaandetailid)->first();
                        $pembelianDetail->qtyterpakai = $qtyterpakai;
                        $pembelianDetail->save();
                    }

                    //DELETE HPP
                    $hpp = HPP::where('id', $value->id)->first();
                    if ($hpp) {
                        $hpp->delete();
                    }

                    //DELETE KARTUSTOK
                    $kartuStok = KartuStok::where('pengeluarandetailid', $value->pengeluarandetailid)->first();
                    if ($kartuStok) {
                        $kartuStok->delete();
                    }
                    // dump($pembelianDetail, $hpp, $kartuStok);
                }
                // die;

                $pembelianDetail = DB::table('pembeliandetail')
                    ->select('*', 'pembelianheader.nobukti', 'pembeliandetail.id as pembeliandetailid')
                    ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
                    ->where('pembelianid', $hppRow->penerimaanid)
                    ->get();

                // dd($pembelianDetail);
                foreach ($pembelianDetail as $value) {

                    //DELETE KARTU STOK PEMBELIAN
                    $dataKartuStok = KartuStok::where('penerimaandetailid', $value->pembeliandetailid)->first();
                    if ($dataKartuStok) {
                        $dataKartuStok->delete();
                    }

                    //STORE KARTU STOK PEMBELIAN
                    $kartuStok = (new KartuStok())->processStore([
                        "tglbukti" => $value->tglbukti,
                        "penerimaandetailid" => $value->pembeliandetailid,
                        "pengeluarandetailid" => 0,
                        "nobukti" => $value->nobukti,
                        "productid" => $value->productid,
                        "qtypenerimaan" =>  $value->qty,
                        "totalpenerimaan" =>  $value->qty * $value->harga,
                        "qtypengeluaran" => 0,
                        "totalpengeluaran" => 0,
                        "flag" => 'B',
                        "seqno" => 1
                    ]);

                    // dump($kartuStok);
                }
                // die;

                //HAPUS KARTU STOK YG DELETE ROW
                $fetchKartuStok = KartuStok::where('nobukti', $dataPembelian['nobukti'])->where('flag', 'B')->get();
                $pembelianIds = $pembelianDetail->pluck('pembeliandetailid')->toArray();
                $penerimaanIds = collect($fetchKartuStok)->pluck('penerimaandetailid')->toArray();
                $missingIds = array_diff($penerimaanIds, $pembelianIds);
                $missingIds = array_values($missingIds);

                $kartuStok = DB::table('kartustok')
                    ->select('*')
                    ->whereIn('penerimaandetailid', $missingIds)
                    ->get();

                $kartuStok->each(function ($item) {
                    DB::table('kartustok')->where('id', $item->id)->delete();
                });

                //INSERT ULANG HPP
                foreach (DB::table($tempHpp)->get() as $value) {
                    $flag = null;
                    $flagkartustok = null;
                    $seqno = 0;

                    if ($value->pengeluarannobukti !== null) {
                        if (strpos($value->pengeluarannobukti, 'J') === 0) {
                            $flag = 'PJ';
                            $flagkartustok = 'J';
                            $seqno = 2;
                        } elseif (strpos($value->pengeluarannobukti, 'RB') === 0) {
                            $flag = 'RB';
                            $flagkartustok = 'RB';
                            $seqno = 4;
                        }
                    }

                    $dataHpp = [
                        "pengeluaranid" => $value->pengeluaranid,
                        "tglbukti" => $value->tglbukti,
                        "pengeluarannobukti" => $value->pengeluarannobukti,
                        "pengeluarandetailid" => $value->pengeluarandetailid,
                        "pengeluarannobukti" => $value->pengeluarannobukti,
                        "productid" => $value->productid,
                        "qtypengeluaran" => $value->pengeluaranqty,
                        "hargapengeluaranhpp" => $value->pengeluaranhargahpp,
                        "totalpengeluaranhpp" => $value->pengeluarantotalhpp,
                        "flag" => $flag,
                        "flagkartustok" => $flagkartustok,
                        "seqno" => $seqno,
                    ];
                    $hpp = (new HPP())->processStore($dataHpp);

                    // dump($hpp);
                }


                // die;

                // dd($data);

                if ($data != null) {
                    foreach ($data as $value) {
                        // dd($value);
                        if ($dataPembelian['nobukti'] == $value['pembeliannobukti']) {
                            // dd('tessdtrtrtsdt');
                            $dataReturBeli = ReturBeliHeader::where('pembelianid', $dataPembelian['id'])->where('flag', 'generated')->first();

                            if ($dataReturBeli) {
                                $returBeliDetail = DB::table('returbelidetail')
                                    ->select('*')
                                    ->where('returbeliid', $dataReturBeli->id)
                                    ->get();

                                // dd($returBeliDetail);

                                if (!isset($value['id'])) {
                                    $value['id'] = [];
                                }
                                $returBeliDetailIds = $returBeliDetail->pluck('id', 'pembeliandetailid')->toArray();

                                foreach ($value['pembeliandetailid'] as $pembeliandetailid) {
                                    $value['id'][$pembeliandetailid] = isset($returBeliDetailIds[$pembeliandetailid]) ? $returBeliDetailIds[$pembeliandetailid] : 0;
                                }
                                $value['id'] = array_values($value['id']);

                                // dd($value);
                                $returBeliHeader = ReturBeliHeader::findOrFail($dataReturBeli->id);
                                $returBeli = (new ReturBeliHeader())->processUpdate($returBeliHeader, $value);
                            } else {
                                $returBeli = (new ReturBeliHeader())->processStore($value);
                            }
                        } else {
                            $dataReturBeli = ReturBeliHeader::where('pembelianid', $dataPembelian['id'])->where('flag', 'generated')->first();

                            // dd($dataReturBeli);
                            if ($dataReturBeli) {
                                $returBeli = (new ReturBeliHeader())->processDestroy($dataReturBeli->id, "DELETE RETUR BELI HEADER");
                                // dd($returBeli);
                            }
                        }
                    }
                } else {
                    // dd($data);
                    $dataReturBeli = ReturBeliHeader::where('pembelianid', $dataPembelian['id'])->where('flag', 'generated')->first();

                    // dd($dataReturBeli);
                    if ($dataReturBeli) {
                        $returBeli = (new ReturBeliHeader())->processDestroy($dataReturBeli->id, "DELETE RETUR BELI HEADER");
                        // dump($returBeli);
                    }
                }
            }
            // die;
        }

        return $pembelianHeader;
    }

    public function cekStok($productid)
    {

        if (request()->edit) {
            $query = DB::table('kartustok')
                ->select(
                    'nobukti',
                    'qtypenerimaan',
                    'qtypengeluaran',
                    'totalpenerimaan',
                    'totalpengeluaran',
                    'qtysaldo',
                    'totalsaldo'
                )
                ->where('productid', $productid)
                ->where('flag', 'B')
                ->orderByDesc('id')
                ->first();
        } else {
            $query = DB::table('kartustok')
                ->select(
                    'nobukti',
                    'qtypenerimaan',
                    'qtypengeluaran',
                    'totalpenerimaan',
                    'totalpengeluaran',
                    'qtysaldo',
                    'totalsaldo'
                )
                ->where('productid', $productid)
                ->orderByDesc('id')
                ->first();
        }

        $qtysaldo = $query->qtysaldo ?? 0;
        return $qtysaldo;
    }

    public function getSumQty()
    {
        $getPembelian = DB::table("returbelidetail")
            ->select(
                DB::raw('max(returbeliheader.id) as id'),
                DB::raw('max(returbeliheader.pembelianid) as pembelianid'),
                DB::raw('max(returbelidetail.productid) as productid'),
                DB::raw('SUM(returbelidetail.qty) as totalqtyretur')
            )
            ->leftJoin(DB::raw("returbeliheader"), 'returbelidetail.returbeliid', 'returbeliheader.id')
            ->where('returbelidetail.pembeliandetailid', request()->iddetail)
            ->groupBy('returbelidetail.productid')
            ->first();

        // dd($getPembelian);

        if ($getPembelian != null) {
            $totalQtyretur = $getPembelian->totalqtyretur ?? 0;

            $getPembelianHeader = DB::table("pembeliandetail")
                ->select(
                    DB::raw('max(pembelianheader.id) as idheader'),
                    DB::raw('max(pembeliandetail.id) as id'),
                    DB::raw('max(pembeliandetail.productid) as productid'),
                    DB::raw('SUM(pembeliandetail.qty) as totalqty')
                )
                ->leftJoin(DB::raw("pembelianheader"), 'pembelianheader.id', 'pembeliandetail.pembelianid')
                ->where('pembeliandetail.id', request()->iddetail)
                ->groupBy('pembeliandetail.productid')
                ->first();
        }

        return [$totalQtyretur ?? '', $getPembelianHeader->totalqty ?? ''];
    }

    public function getTransaksiBelanja()
    {
        $nominalBayar = $this->findEditAll();

        dd($nominalBayar);
    }

    public function disabledDelete($id)
    {
        $query = DB::table('hpp')
            ->leftJoin(DB::raw("pembeliandetail"), 'pembeliandetail.id', 'hpp.penerimaandetailid')
            ->where('hpp.penerimaanid', $id);

        $getData = $query->get()->toArray();

        return $getData;
    }

    public function disabledDeleteEditALl($data)
    {
        $tglpengiriman = date('Y-m-d', strtotime(request()->date));

        foreach ($data as $pembelian) {
            foreach ($pembelian['details'] as $detail) {
                $id = $pembelian['id'];

                $query = DB::table('hpp')
                    ->select(
                        "pembeliandetail.id as pembeliandetailid"
                    )
                    ->leftJoin(DB::raw("pembeliandetail"), 'pembeliandetail.id', 'hpp.penerimaandetailid')
                    ->leftJoin(DB::raw("pembelianheader"), 'pembeliandetail.pembelianid', 'pembelianheader.id')
                    // ->where('hpp.penerimaanid', $id)
                    ->where('pembelianheader.tglbukti', '=', $tglpengiriman);
            }
        }
        // die;

        $getData = $query->get()->toArray();

        // dd($getData);


        return $getData;
    }
}
