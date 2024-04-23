<?php

namespace App\Models;

use App\Services\RunningNumberService;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ParagonIE\Sodium\Core\Curve25519\H;
use Symfony\Component\VarDumper\VarDumper;

class PenjualanHeader extends MyModel
{
    use HasFactory;

    protected $table = 'penjualanheader';
    public $hpp;

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
        $query = DB::table($this->table . ' as penjualanheader')
            ->select(
                "penjualanheader.id",
                "penjualanheader.nobukti",
                "penjualanheader.tglbukti",
                "customer.id as customerid",
                "customer.nama as customernama",
                "penjualanheader.alamatpengiriman",
                "penjualanheader.tglpengiriman",
                "penjualanheader.keterangan",
                DB::raw('IFNULL(penjualanheader.servicetax, 0) AS servicetax'),
                DB::raw('IFNULL(penjualanheader.tax, 0) AS tax'),
                DB::raw('IFNULL(penjualanheader.taxamount, 0) AS taxamount'),
                DB::raw('IFNULL(penjualanheader.discount, 0) AS discount'),
                DB::raw('IFNULL(penjualanheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(penjualanheader.total, 0) AS total'),
                DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -1), '/', 1) as nobuktinew"),
                "penjualanheader.discount",
                "penjualanheader.tglcetak",
                "pesananfinalheader.id as pesananfinalid",
                "pesananfinalheader.nobukti as pesananfinalnobukti",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "top.id as top",
                "top.memo as topmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'penjualanheader.created_at',
                'penjualanheader.updated_at'
            )
            ->leftJoin(DB::raw("customer"), 'penjualanheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'penjualanheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as top"), 'penjualanheader.top', 'top.id')
            ->leftJoin(DB::raw("pesananfinalheader"), 'penjualanheader.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'penjualanheader.modifiedby', 'modifier.id');

        if (request()->tgldariheader && request()->tglsampaiheader) {
            $query->whereBetween($this->table . '.tglpengiriman', [date('Y-m-d', strtotime(request()->tgldariheader)), date('Y-m-d', strtotime(request()->tglsampaiheader))]);
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
        $query = DB::table('penjualanheader')
            ->select(
                "penjualanheader.id",
                "penjualanheader.nobukti",
                "penjualanheader.tglbukti",
                "customer.id as customerid",
                "customer.nama as customernama",
                "penjualanheader.top",
                "top.text as topnama",
                "penjualanheader.alamatpengiriman",
                "penjualanheader.tglpengiriman",
                "penjualanheader.keterangan",
                DB::raw('IFNULL(penjualanheader.servicetax, 0) AS servicetax'),
                DB::raw('IFNULL(penjualanheader.tax, 0) AS tax'),
                DB::raw('IFNULL(penjualanheader.taxamount, 0) AS taxamount'),
                DB::raw('IFNULL(penjualanheader.discount, 0) AS discount'),
                DB::raw('IFNULL(penjualanheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(penjualanheader.total, 0) AS total'),
                "penjualanheader.discount",
                "penjualanheader.tglcetak",
                "pesananfinalheader.id as pesananfinalid",
                "pesananfinalheader.nobukti as nobuktipesananfinal",
                "parameter.id as status",
                "parameter.text as statusnama",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'penjualanheader.created_at',
                'penjualanheader.updated_at'
            )
            ->leftJoin(DB::raw("customer"), 'penjualanheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'penjualanheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as top"), 'penjualanheader.top', 'top.id')
            ->leftJoin(DB::raw("pesananfinalheader"), 'penjualanheader.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'penjualanheader.modifiedby', 'modifier.id')
            ->where('penjualanheader.id', $id);
        $data = $query->first();

        // dd($data);
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('penjualanheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(penjualanheader.nobukti, '/', 1)"), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'customernama') {
            return $query->orderBy('customer.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'pesananfinalnobukti') {
            return $query->orderBy('pesananfinalheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'customernama') {
                            $query = $query->where('customer.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'pesananfinalnobukti') {
                            $query = $query->where('pesananfinalheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglpengiriman' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->where('modifier.name', 'like', "%$filters[data]%");
                        } else {
                            // $query = $query->where($this->table . '.' . $filters['field'], 'like', "%$filters[data]%");
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {

                        if ($filters['field'] == 'customernama') {
                            $query = $query->orWhere('customer.nama', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'pesananfinalnobukti') {
                            $query = $query->orWhere('pesananfinalheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'aksi') {
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglpengiriman' || $filters['field'] == 'tglbukti') {
                            $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'modifiedby_name') {
                            $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
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
                $this->table.alamatpengiriman,
                $this->table.tglpengiriman,
                $this->table.tglterima,
                $this->table.keterangan,
                $this->table.servicetax,
                $this->table.tax,
                $this->table.taxamount,
                $this->table.subtotal,
                $this->table.total,
                $this->table.discount,
                $this->table.tglcetak,
                pesananfinalheader.id as pesananfinalid,
                pesananfinalheader.nobukti as nobuktipesanan,
                parameter.id as status,
                parameter.text as statusnama,
                parameter.memo as statusmemo,
                modifier.id as modifiedby,
                modifier.name as modifiedby_name,
                $this->table.created_at,
                $this->table.updated_at

            ")
            )
            ->leftJoin(DB::raw("customer"), 'penjualanheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'penjualanheader.status', 'parameter.id')
            ->leftJoin(DB::raw("pesananfinalheader"), 'penjualanheader.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'penjualanheader.modifiedby', 'modifier.id');
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
            nobukti VARCHAR(100),
            tglbukti DATETIME,
            alamatpengiriman VARCHAR(500),
            tglpengiriman DATETIME,
            tglterima DATETIME,
            keterangan VARCHAR(500),
            servicetax VARCHAR(500),
            tax VARCHAR(500),
            taxamount VARCHAR(500),
            subtotal VARCHAR(500),
            total VARCHAR(500),
            discount VARCHAR(500),
            tglcetak DATETIME,
            pesananfinalid INT,
            nobuktipesanan VARCHAR(500),
            status INT,
            statusnama VARCHAR(500),
            statusmemo VARCHAR(500),
            modifiedby VARCHAR(255),
            modifiedby_name VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");

        DB::table($temp)->insertUsing(["id", "customerid", "customernama", "nobukti", "tglbukti", "alamatpengiriman", "tglpengiriman", "tglterima", "keterangan", "servicetax", "tax", "taxamount", "subtotal", "total", "discount", "tglcetak", "pesananfinalid", "nobuktipesanan",  "status", "statusnama", "statusmemo", "modifiedby", "modifiedby_name", "created_at", "updated_at"], $query);

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

    public function getfilterTglPengiriman()
    {
        $this->setRequestParameters();
        $tglpengiriman = (new DateTime())->add(new DateInterval('P1D'))->format('Y-m-d');

        $query = DB::table('pesananfinaldetail')
            ->select(
                "pesananfinaldetail.pesananfinalid",
                "pesananfinaldetail.nobuktipembelian",
                "pesananfinalheader.tglpengiriman",
            )
            ->leftJoin("pesananfinalheader", 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
            ->whereDate('pesananfinalheader.tglpengiriman', $tglpengiriman)
            ->get();

        $hasNobuktipembelian = false;


        foreach ($query as $result) {
            if ($result->nobuktipembelian !== "") {
                $hasNobuktipembelian = true;
                break;
            }
        }

        return $hasNobuktipembelian;
    }

    public function approval($penjualanheaderId)
    {
        // dd($penjualanheaderId);
        $pesananfinalheaderData = [];
        foreach ($penjualanheaderId as $index => $id) {

            $pesananfinalheader = PesananFinalHeader::where('id', $id)
                ->first();
            $data = $pesananfinalheader->toArray();

            if ($pesananfinalheader) {
                $customerid = $pesananfinalheader->customerid;
                $pesananfinalheaderData[$customerid][] = $pesananfinalheader;

                $pesananfinaldetail = PesananFinalDetail::where('pesananfinalid', $id)
                    ->get();

                $data['pesananfinalid'] = $data['id'];

                foreach ($pesananfinaldetail as $detail) {
                    $data['pesananfinaldetailid'][] = $detail->id;
                    $data['productid'][] = $detail->productid;
                    $data['keterangandetail'][] = $detail->keterangan;
                    $data['qtyjual'][] = $detail->qtyjual;
                    $data['qtyreturjual'][] = $detail->qtyreturjual;
                    $data['qtyreturbeli'][] = $detail->qtyreturbeli;
                    $data['satuanid'][] = $detail->satuanid;
                    $data['hargajual'][] = $detail->hargajual;
                }

                if ($pesananfinaldetail->isNotEmpty()) {
                    $pesananfinaldetailData[$id] = $pesananfinaldetail;
                }

                // dd($data);


                $store = $this->processStore($data, 'create');

                $result[] = $data;
            }
        }

        return $result;
    }

    public function processStore(array $data, $create = null): PenjualanHeader
    {
        $penjualanHeader = new PenjualanHeader();
        /*STORE HEADER*/
        $group = 'PENJUALAN HEADER BUKTI';
        $subGroup = 'PENJUALAN HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();
        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));
        $tglpengiriman = date('Y-m-d', strtotime($data['tglpengiriman']));
        // dd($data);

        $penjualanHeader->tglbukti = $tglbukti;
        $penjualanHeader->customerid = $data['customerid'];
        $penjualanHeader->top = $data['top'] ?? 12;
        $penjualanHeader->pesananfinalid = $data['pesananfinalid'] ?? 0;
        $penjualanHeader->alamatpengiriman = $data['alamatpengiriman'];
        $penjualanHeader->tglpengiriman = $tglpengiriman;
        $penjualanHeader->keterangan = $data['keterangan'] ?? '';
        $penjualanHeader->discount = $data['discount'] ?? 0;
        $penjualanHeader->servicetax = $data['servicetax'] ?? 0;
        $penjualanHeader->tax = $data['tax'] ?? 0;
        $penjualanHeader->taxamount = $data['taxamount'] ?? 0;
        $penjualanHeader->subtotal = $data['subtotal'] ?? 0;
        $penjualanHeader->total = $data['total'] ?? 0;
        $penjualanHeader->status = $data['status'] ?? 1;
        $penjualanHeader->modifiedby = auth('api')->user()->id;

        $penjualanHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $penjualanHeader->getTable(), date('Y-m-d', strtotime($data['tglbukti'])));

        if (!$penjualanHeader->save()) {
            throw new \Exception("Error storing penjualan header.");
        }
        // dd($penjualanHeader);

        $penjualanHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($penjualanHeader->getTable()),
            'postingdari' => strtoupper('ENTRY PENJUALAN HEADER'),
            'idtrans' => $penjualanHeader->id,
            'nobuktitrans' => $penjualanHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $penjualanHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        if ($create) {
            DB::table('pesananfinalheader')
                ->where('id', $penjualanHeader->pesananfinalid)
                ->update(['nobuktipenjualan' => $penjualanHeader->nobukti, 'status' => 1]);
        }

        $pesananDetails = [];
        // dd($data);

        for ($i = 0; $i < count($data['productid']); $i++) {
            $penjualanDetail = (new PenjualanDetail())->processStore($penjualanHeader, [
                'penjualanid' => $penjualanHeader->id,
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
                'productid' => $data['productid'][$i],
                'keterangan' => $data['keterangandetail'][$i] ?? '',
                'qty' => $data['qty'][$i] ?? $data['qtyjual'][$i],
                'qtyreturjual' => $data['qtyreturjual'][$i] ?? 0,
                'qtyreturbeli' => $data['qtyreturbeli'][$i] ?? 0,
                'satuanid' => $data['satuanid'][$i] ?? '',
                'harga' => $data['harga'][$i] ?? $data['hargajual'][$i],
                'modifiedby' => auth('api')->user()->id,
            ]);
            $pesananDetails[] = $penjualanDetail->toArray();

            if ($penjualanHeader->pesananfinalid == 0) {
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
                    )
                    ->leftJoin("penjualanheader", "penjualandetail.penjualanid", "penjualanheader.id")
                    ->leftJoin("pesananfinaldetail", "penjualandetail.pesananfinaldetailid", "pesananfinaldetail.id")
                    ->leftJoin("pesananfinalheader", "pesananfinalheader.id", "pesananfinaldetail.pesananfinalid")
                    ->leftJoin("product", "penjualandetail.productid", "product.id")
                    ->where("penjualanheader.id", $penjualanHeader->id)
                    ->where("penjualandetail.productid", $data['productid'][$i])
                    ->first();

                $dataHpp = [
                    'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
                    "pengeluaranid" => $query->pengeluaranid,
                    "tglbukti" => $query->tglbukti,
                    "pengeluarannobukti" => $query->pengeluarannobukti,
                    "pengeluarandetailid" => $query->pengeluarandetailid,
                    "pengeluarannobukti" => $query->pengeluarannobukti,
                    "productid" => $query->productid,
                    "qtypengeluaran" => $query->pengeluaranqty,
                    "hargapengeluaranhpp" => $query->pengeluaranhargahpp,
                    "hargapengeluaran" => $query->pengeluaranharga,
                    "totalpengeluaranhpp" => $query->pengeluarantotalhpp,
                    "totalpengeluaran" => $query->pengeluarantotal,
                    "flag" => 'PJ',
                    "flagkartustok" => 'J',
                    "seqno" => 2,
                ];
                // dd($dataHpp);
                $hpp = (new HPP())->processStore($dataHpp);
            }
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($penjualanHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY PEMBELIAN DETAIL'),
            'idtrans' =>  $penjualanHeaderLogTrail->id,
            'nobuktitrans' => $penjualanHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $penjualanDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);

        // STORE PIUTANG

        $this->setRequestParameters();
        $query = DB::table($this->table . ' as penjualanheader')
            ->select(
                "penjualanheader.customerid",
                "penjualanheader.top",
                "parameter.text as toptext",
            )
            ->leftJoin(DB::raw("parameter"), 'penjualanheader.top', 'parameter.id')
            ->where("penjualanheader.id", $penjualanHeader->id)
            ->first();

        $top = $query->toptext;

        if ($top == "CREDIT") {
            $tgljatuhtempo = date("Y-m-d", strtotime("+1 month", strtotime($data['tglbukti'])));
            $dataPiutang = [
                "tglbukti" => $tglbukti,
                "penjualanid" => $penjualanHeader->id,
                "tglbuktipenjualan" => $tglbukti,
                "tgljatuhtempo" => $tgljatuhtempo,
                "customerid" => $data['customerid'],
                "keterangan" => $row['keterangan'] ?? '',
                "nominalpiutang" => $data['total'],
                "nominalsisa" => $data['total'],
                "tglcetak" => '2023-11-11',
                "status" => $row['status'] ?? 1,
            ];
        } else {
            $tgljatuhtempo = $tglbukti;
            $dataPiutang = [
                "tglbukti" => $tglbukti,
                "penjualanid" => $penjualanHeader->id,
                "tglbuktipenjualan" => $tglbukti,
                "tgljatuhtempo" => $tgljatuhtempo,
                "customerid" => $data['customerid'],
                "keterangan" => $row['keterangan'] ?? '',
                "nominalpiutang" => $data['total'],
                "nominalbayar" => $data['total'],
                "tglcetak" => '2023-11-11',
                "status" => $row['status'] ?? 1,
            ];
        }

        // dd($dataPiutang);
        $piutang = (new Piutang())->processStore($dataPiutang);

        return $penjualanHeader;
    }

    public function processUpdateOld(PenjualanHeader $penjualanHeader, array $data)
    {
        // dd($data);
        $nobuktiOld = $penjualanHeader->nobukti;

        $group = 'PENJUALAN HEADER BUKTI';
        $subGroup = 'PENJUALAN HEADER BUKTI';
        $tglpengiriman = date('Y-m-d', strtotime($data['tglpengiriman']));

        $getNobuktiPesananFinal = DB::table("pesananfinalheader")
            ->select('id')
            ->where('nobukti', '=', $data['nobuktipesananfinal'])
            ->first();

        $penjualanHeader->customerid = $data['customerid'];
        $penjualanHeader->pesananfinalid = $getNobuktiPesananFinal->id ?? 0;
        $penjualanHeader->alamatpengiriman = $data['alamatpengiriman'];
        $penjualanHeader->tglpengiriman = $tglpengiriman;
        $penjualanHeader->keterangan = $data['keterangan'] ?? '';
        $penjualanHeader->discount = $data['discount'] ?? 0;
        $penjualanHeader->servicetax = $data['servicetax'] ?? 0;
        $penjualanHeader->tax = $data['tax'] ?? 0;
        $penjualanHeader->taxamount = $data['taxamount'] ?? 0;
        $penjualanHeader->subtotal = $data['subtotal'] ?? 0;
        $penjualanHeader->total = $data['total'] ?? 0;
        $penjualanHeader->status = $data['status'] ?? 1;
        $penjualanHeader->modifiedby = auth('api')->user()->id;

        if (!$penjualanHeader->save()) {
            throw new \Exception("Error updating Penjualan Header.");
        }

        // dd($penjualanHeader);

        $penjualanHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($penjualanHeader->getTable()),
            'postingdari' => strtoupper('EDIT Penjualan HEADER'),
            'idtrans' => $penjualanHeader->id,
            'nobuktitrans' => $penjualanHeader->nobukti,
            'aksi' => 'EDIT',
            'datajson' => $penjualanHeader->toArray(),
            'modifiedby' => auth('api')->user()->id
        ]);

        /*DELETE EXISTING PENJUALAN DETAIL*/
        $penjualansDetail = PenjualanDetail::where('penjualanid', $penjualanHeader->id)->lockForUpdate()->delete();

        $nominalPiutang = $penjualanHeader->subtotal + $penjualanHeader->taxamount - $penjualanHeader->discount;
        $piutang = Piutang::where('penjualanid', $penjualanHeader->id)->first();
        if ($piutang) {
            $piutang->update([
                'nominalpiutang' => $nominalPiutang,
                'nominalsisa' => $nominalPiutang,
            ]);

            // Log the update in LogTrail
            (new LogTrail())->processStore([
                'namatabel' => $piutang->getTable(),
                'postingdari' => 'EDIT PIUTANG DARI EDIT PENJUALAN',
                'idtrans' => $piutang->id,
                'nobuktitrans' => $piutang->id,
                'aksi' => 'EDIT',
                'datajson' => $piutang->toArray(),
                'modifiedby' => auth('api')->user()->id,
            ]);
        }

        /* STORE PENJUALAN DETAIL*/
        $penjualanDetails = [];
        $returDetails = [];
        $retur = 0;

        for ($i = 0; $i < count($data['productid']); $i++) {
            $penjualanDetail = (new PenjualanDetail())->processStore($penjualanHeader, [
                'penjualanid' => $penjualanHeader->id,
                'penjualannobukti' => $penjualanHeader->nobukti,
                'productid' => $data['productid'][$i],
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
                'keterangan' => $data['keterangandetail'][$i] ?? '',
                'qty' => $data['qty'][$i] ?? 0,
                'qtyretur' => $data['qtyretur'][$i] ?? 0,
                'satuanid' => $data['satuanid'][$i] ?? '',
                'harga' => $data['harga'][$i] ?? '',
                'modifiedby' => auth('api')->user()->id,
            ]);
            $penjualanDetails[] = $penjualanDetail->toArray();

            $pesananFinalDetail = PesananFinalDetail::where('id', $penjualanDetail->pesananfinaldetailid)->first();
            if ($pesananFinalDetail) {
                $pesananFinalDetail->update([
                    'productid' => $penjualanDetail->productid,
                    'productnama' => $penjualanDetail->productnama,
                    'qtyjual' => $penjualanDetail->qty,
                    'qtyreturjual' => $penjualanDetail->qtyretur,
                    'satuanid' => $penjualanDetail->satuanid,
                    'satuannama' => $penjualanDetail->satuannama,
                    'keterangan' => $penjualanDetail->keterangandetail,
                    'hargajual' => $penjualanDetail->harga,
                    'totalhargajual' => $penjualanDetail->totalharga,
                ]);


                (new LogTrail())->processStore([
                    'namatabel' => $pesananFinalDetail->getTable(),
                    'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT PENJUALAN',
                    'idtrans' => $pesananFinalDetail->id,
                    'nobuktitrans' => $pesananFinalDetail->id,
                    'aksi' => 'EDIT',
                    'datajson' => $pesananFinalDetail->toArray(),
                    'modifiedby' => auth('api')->user()->id,
                ]);
            }

            // $product = Product::where('id', $penjualanDetail->productid)->first();
            // if ($product) {
            //     $product->update([
            //         'hargajual' => $penjualanDetail->harga,
            //     ]);

            //     // Log the update in LogTrail
            //     (new LogTrail())->processStore([
            //         'namatabel' => $product->getTable(),
            //         'postingdari' => 'EDIT PRODUCT DARI EDIT PENJUALAN',
            //         'idtrans' => $product->id,
            //         'nobuktitrans' => $product->id,
            //         'aksi' => 'EDIT',
            //         'datajson' => $product->toArray(),
            //         'modifiedby' => auth('api')->user()->id,
            //     ]);
            // }
            // dd($penjualanDetail);

            if ($data['qtyretur'][$i] != 0) {
                $retur++;
                $returDetail = [
                    'penjualandetailid' => $penjualanDetail->id,
                    'productid' => $data['productid'][$i],
                    'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
                    'keterangan' => $data['keterangandetail'][$i] ?? '',
                    'qty' => $data['qty'][$i] ?? 0,
                    'qtyretur' => $data['qtyretur'][$i] ?? 0,
                    'satuanid' => $data['satuanid'][$i] ?? 0,
                    'harga' => $data['harga'][$i] ?? 0,
                    'modifiedby' => auth('api')->user()->id,
                ];
                $returDetails[] = $returDetail;
            }
        }

        $result = [];
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
                'total' => $totalRetur,
                'flag' => 'generated',
            ];

            $result = array_merge($returHeader, $details);
        }
        // dd($result);
        if ($retur > 0) {
            return [
                'penjualanHeader' => $penjualanHeader,
                'resultRetur' => $result
            ];
        } else {
            return [
                'penjualanHeader' => $penjualanHeader,
                'resultRetur' => null
            ];
        }
    }

    public function processUpdate(PenjualanHeader $penjualanHeader, array $data)
    {
        // dd($data);
        $tempDetail = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempDetail (
            id INT UNSIGNED NULL,
            penjualanid INT NULL,
            productid INT NULL,
            pesananfinaldetailid INT NULL,
            keterangan VARCHAR(500),
            qty FLOAT,
            qtyreturjual FLOAT,
            qtyreturbeli FLOAT,
            satuanid INT NULL,
            harga FLOAT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        
        )");

        // dd($data);
        $nobuktiOld = $penjualanHeader->nobukti;

        $group = 'PENJUALAN HEADER BUKTI';
        $subGroup = 'PENJUALAN HEADER BUKTI';
        $tglpengiriman = date('Y-m-d', strtotime($data['tglpengiriman']));

        $getNobuktiPesananFinal = DB::table("pesananfinalheader")
            ->select('id')
            ->where('nobukti', '=', $data['nobuktipesananfinal'])
            ->first();

        $penjualanHeader->customerid = $data['customerid'];
        $penjualanHeader->top = $data['top'];
        $penjualanHeader->pesananfinalid = $getNobuktiPesananFinal->id ?? 0;
        $penjualanHeader->alamatpengiriman = $data['alamatpengiriman'];
        $penjualanHeader->tglpengiriman = $tglpengiriman;
        $penjualanHeader->keterangan = $data['keterangan'] ?? '';
        $penjualanHeader->discount = $data['discount'] ?? 0;
        $penjualanHeader->servicetax = $data['servicetax'] ?? 0;
        $penjualanHeader->tax = $data['tax'] ?? 0;
        $penjualanHeader->taxamount = $data['taxamount'] ?? 0;
        $penjualanHeader->subtotal = $data['subtotal'] ?? 0;
        $penjualanHeader->total = $data['total'] ?? 0;
        $penjualanHeader->status = $data['status'] ?? 1;
        $penjualanHeader->modifiedby = auth('api')->user()->id;

        if (!$penjualanHeader->save()) {
            throw new \Exception("Error updating Penjualan Header.");
        }

        // dd($penjualanHeader);

        $penjualanHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($penjualanHeader->getTable()),
            'postingdari' => strtoupper('EDIT Penjualan HEADER'),
            'idtrans' => $penjualanHeader->id,
            'nobuktitrans' => $penjualanHeader->nobukti,
            'aksi' => 'EDIT',
            'datajson' => $penjualanHeader->toArray(),
            'modifiedby' => auth('api')->user()->id
        ]);

        $penjualanDetails = [];
        $returDetails = [];
        $returs = [];
        $retur = 0;

        for ($i = 0; $i < count($data['productid']); $i++) {

            DB::table($tempDetail)->insert([
                'id' => $data['id'][$i],
                'penjualanid' => $penjualanHeader->id,
                'productid' => $data['productid'][$i],
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
                'keterangan' => $data['keterangandetail'][$i] ?? '',
                'qty' => $data['qty'][$i] ?? 0,
                'qtyreturjual' => $data['qtyreturjual'][$i] ?? 0,
                'qtyreturbeli' => $data['qtyreturbeli'][$i] ?? 0,
                'satuanid' =>   $data['satuanid'][$i] ?? '',
                'harga' => $data['harga'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // $cekDetail = DB::table("penjualandetail")->where('id', $data['id'][$i])->first();
            // // dd($cekDetail);
            // if ($cekDetail != '') {
            //     // dd('test');
            //     if ($cekDetail->qtyretur != $data['qtyretur'][$i]) {
            //         // dd('test');
            //         if ($data['qtyretur'][$i] == 0) {
            //             $retur++;
            //             $returDetail = [
            //                 'penjualandetailid' => $data['id'][$i],
            //                 'productid' => $data['productid'][$i],
            //                 'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
            //                 'keterangan' => $data['keterangandetail'][$i] ?? '',
            //                 'qty' => $data['qty'][$i] ?? 0,
            //                 'qtyretur' => $data['qtyretur'][$i] ?? 0,
            //                 'satuanid' => $data['satuanid'][$i] ?? 0,
            //                 'harga' => $data['harga'][$i] ?? 0,
            //                 'modifiedby' => auth('api')->user()->id,
            //             ];
            //             $returs[] = $returDetail;
            //         }
            //     } else {
            //         // dd('test2');
            //         if ($data['qtyretur'][$i] != 0) {
            //             $retur++;
            //             $returDetail = [
            //                 'penjualandetailid' => $data['id'][$i],
            //                 'productid' => $data['productid'][$i],
            //                 'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
            //                 'keterangan' => $data['keterangandetail'][$i] ?? '',
            //                 'qty' => $data['qty'][$i] ?? 0,
            //                 'qtyretur' => $data['qtyretur'][$i] ?? 0,
            //                 'satuanid' => $data['satuanid'][$i] ?? 0,
            //                 'harga' => $data['harga'][$i] ?? 0,
            //                 'modifiedby' => auth('api')->user()->id,
            //             ];
            //             $returDetails[] = $returDetail;
            //         }
            //     }
            // } else {
            //     if ($data['qtyretur'][$i] != 0) {
            //         $retur++;
            //         $returDetail = [
            //             'penjualandetailid' => $data['id'][$i],
            //             'productid' => $data['productid'][$i],
            //             'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
            //             'keterangan' => $data['keterangandetail'][$i] ?? '',
            //             'qty' => $data['qty'][$i] ?? 0,
            //             'qtyretur' => $data['qtyretur'][$i] ?? 0,
            //             'satuanid' => $data['satuanid'][$i] ?? 0,
            //             'harga' => $data['harga'][$i] ?? 0,
            //             'modifiedby' => auth('api')->user()->id,
            //         ];
            //         $returDetails[] = $returDetail;
            //     }
            // }

            // dd($data);


            // die;
            $hpp = DB::table('hpp')
                ->select('*')
                ->where('pengeluaranid', $penjualanHeader->id)
                ->where('pengeluarandetailid', $data['id'][$i])
                ->first();


            // DATA QTY RETUR
            if ($data['qtyreturjual'][$i] != 0) {
                $retur++;
                $returDetail = [
                    'penjualandetailid' => $data['id'][$i],
                    'productid' => $data['productid'][$i],
                    'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
                    'keterangan' => $data['keterangandetail'][$i] ?? '',
                    'qty' => $data['qty'][$i] ?? 0,
                    'qtyreturjual' => $data['qtyreturjual'][$i] ?? 0,
                    'qtyreturbeli' => $data['qtyreturbeli'][$i] ?? 0,
                    'satuanid' => $data['satuanid'][$i] ?? 0,
                    'hargajual' => $data['harga'][$i] ?? 0,
                    'hargabeli' => $hpp->penerimaanharga ?? 0,
                    'modifiedby' => auth('api')->user()->id,
                ];
                $returDetails[] = $returDetail;
            }

            // DATA GENERATED DARI PESANAN FINAL
            if ($data['pesananfinaldetailid'][$i] !== "0") {

                //UPDATE QTY PEMBELIAN DETAIL 
                $updatePembelianDetail = PembelianDetail::where('id', $hpp->penerimaandetailid)->first();
                if ($updatePembelianDetail) {
                    $updatePembelianDetail->qty = $data['qty'][$i];
                    $updatePembelianDetail->qtypesanan = $data['qty'][$i];
                    $updatePembelianDetail->qtyterpakai = $data['qty'][$i];
                    $updatePembelianDetail->save();
                }

                //UPDATE HUTANG 
                $pembelianDetails = DB::table('pembeliandetail')
                    ->select('*')
                    ->where('pembelianid', $hpp->penerimaanid)
                    ->get();
                $totalharga = [];
                foreach ($pembelianDetails as $detail) {
                    $totalharga[] = $detail->harga * $detail->qty;
                }
                $total = array_sum($totalharga);
                $hutang = Hutang::where('pembelianid', $hpp->penerimaanid)->first();
                if ($hutang) {
                    if ($hutang->nominalhutang == $hutang->nominalsisa) {
                        $hutang->nominalhutang = $total;
                        $hutang->nominalsisa = $total;
                    } else if ($hutang->nominalhutang == $hutang->nominalbayar) {
                        $hutang->nominalhutang = $total;
                        $hutang->nominalbayar = $total;
                    }
                }

                //DELETE KARTU STOK
                $kartuStok = KartuStok::where('penerimaandetailid', $hpp->penerimaandetailid)->first();
                $tglbuktiBeli = $kartuStok->tglbukti;
                $nobuktiBeli = $kartuStok->nobukti;
                if ($kartuStok) {
                    $kartuStok->delete();
                }

                //STORE KARTU STOK
                $createKartuStok = (new KartuStok())->processStore([
                    "tglbukti" => $tglbuktiBeli,
                    "penerimaandetailid" => $updatePembelianDetail->id,
                    "pengeluarandetailid" => 0,
                    "nobukti" => $nobuktiBeli,
                    "productid" => $updatePembelianDetail['productid'],
                    "qtypenerimaan" =>  $updatePembelianDetail['qty'],
                    "totalpenerimaan" =>  $updatePembelianDetail['qty'] * $updatePembelianDetail['harga'],
                    "qtypengeluaran" => 0,
                    "totalpengeluaran" => 0,
                    "flag" => 'B',
                    "seqno" => 1
                ]);
            }
        }

        $result = [];
        if ($retur > 0) {
            $totalReturJual = 0;
            $totalReturBeli = 0;
            $details = [];

            foreach ($returDetails as $detail) {
                $details['pesananfinaldetailid'][] = $detail['pesananfinaldetailid'];
                $details['penjualandetailid'][] = $detail['penjualandetailid'];
                $details['productid'][] = $detail['productid'];
                $details['satuanid'][] = $detail['satuanid'];
                $details['keterangan'][] = $detail['keterangan'];
                $details['qtyreturjual'][] = $detail['qtyreturjual'];
                $details['qtyreturbeli'][] = $detail['qtyreturbeli'];
                $details['hargajual'][] = $detail['hargajual'];
                $details['hargabeli'][] = $detail['hargabeli'];
                $details['modifiedby'][] = $detail['modifiedby'];
                $totalReturJual += $detail['hargajual'] * $detail['qtyreturjual'];
                $totalReturBeli += $detail['hargabeli'] * $detail['qtyreturbeli'];
            }

            $returHeader = [
                'tglbukti' =>  now(),
                'penjualanid' => $penjualanHeader->id,
                'penjualannobukti' => $penjualanHeader->nobukti,
                'customerid' => $penjualanHeader->customerid,
                'totaljual' => $totalReturJual,
                'totalbeli' => $totalReturBeli,
                'flag' => 'generated',
            ];

            $result = array_merge($returHeader, $details);
        }

        $queryUpdate = DB::table('penjualandetail as a')
            ->join("penjualanheader as b", 'a.penjualanid', '=', 'b.id')
            ->join("$tempDetail as c", 'a.id', '=', 'c.id')
            ->update([
                'a.id' => DB::raw('c.id'),
                'a.penjualanid' => DB::raw('c.penjualanid'),
                'a.productid' => DB::raw('c.productid'),
                'a.pesananfinaldetailid' => DB::raw('c.pesananfinaldetailid'),
                'a.keterangan' => DB::raw('c.keterangan'),
                'a.qty' => DB::raw('c.qty'),
                'a.qtyreturjual' => DB::raw('c.qtyreturjual'),
                'a.qtyreturbeli' => DB::raw('c.qtyreturbeli'),
                'a.satuanid' => DB::raw('c.satuanid'),
                'a.harga' => DB::raw('c.harga'),
                'a.modifiedby' => DB::raw('c.modifiedby'),
                'a.created_at' => DB::raw('c.created_at'),
                'a.updated_at' => DB::raw('c.updated_at')
            ]);

        // update piutang
        $query =  DB::table('piutang as a')
            ->where("a.penjualanid", $penjualanHeader->id)
            ->join("penjualanheader as b", 'a.penjualanid', '=', 'b.id')
            ->update([
                'a.nominalpiutang' => DB::raw('b.total'),
                'a.nominalsisa' => DB::raw('b.total'),
                'a.updated_at' => DB::raw('b.updated_at')
            ]);

        // delete penjualan detail
        $queryDelete = DB::table('penjualandetail as a')
            ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.id')
            ->whereNull('b.id')
            ->where('a.penjualanid', "=", $penjualanHeader->id)
            ->delete();

        // insert penjualan header add row
        $insertAddRowQuery =  DB::table("$tempDetail as a")
            ->where("a.id", '=', '0');

        // dd($insertAddRowQuery->get());

        DB::table('penjualandetail')->insertUsing(["id", "penjualanid", "productid", "pesananfinaldetailid", "keterangan", "qty", "qtyreturjual", "qtyreturbeli", "satuanid", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

        // update ke pesanan final detail
        $queryPesananFinal = DB::table("pesananfinaldetail as a")
            ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.pesananfinaldetailid')
            ->where("b.pesananfinaldetailid", "!=", "0")
            ->update([
                'a.productid' => DB::raw('b.productid'),
                'a.qtyjual' => DB::raw('b.qty'),
                'a.satuanid' => DB::raw('b.satuanid'),
                'a.qtyreturjual' => DB::raw('b.qtyreturjual'),
                'a.qtyreturbeli' => DB::raw('b.qtyreturbeli'),
                'a.keterangan' => DB::raw('b.keterangan'),
                'a.hargajual' => DB::raw('b.harga'),
                'a.modifiedby' => DB::raw('b.modifiedby'),
                'a.created_at' => DB::raw('b.created_at'),
                'a.updated_at' => DB::raw('b.updated_at')
            ]);

        // set qty retur
        if ($retur > 0) {
            return [
                'penjualanHeader' => $penjualanHeader,
                'resultRetur' => $result
            ];
        } else {
            return [
                'penjualanHeader' => $penjualanHeader,
                'resultRetur' => null
            ];
        }
    }

    public function processUpdateHPP($penjualanHeader, $data)
    {
        // dd($penjualanHeader);
        $hppRow = DB::table('hpp')
            ->select('*')
            ->where('hpp.pengeluaranid', '=', $penjualanHeader->id)
            ->where('flag', 'PJ')
            ->first();

        $query = DB::table('penjualandetail')
            ->select('*', 'penjualandetail.id as pengeluarandetailid', 'hpp.id as hppid', 'penjualandetail.productid')
            ->leftJoin('hpp', 'hpp.pengeluarandetailid', 'penjualandetail.id')
            ->where("penjualanid", $penjualanHeader->id)
            ->get();

        $hasNullHppId = $query->contains(function ($item, $key) {
            return $item->hppid === null;
        });

        $filteredData = $query->filter(function ($item) {
            return $item->hppid !== null;
        });

        $firstNonNullHppIdData = $filteredData->first();

        $combinedArray = array_merge([$firstNonNullHppIdData], $query->reject(function ($item) {
            return $item->hppid !== null;
        })->toArray());

        $combinedArray = array_filter($combinedArray);

        if ($hasNullHppId) {
            $fetchDataHpp = collect();
            // dd($combinedArray);
            foreach ($combinedArray as $detail => $value) {
                // dump($value);

                $hpp = DB::table('hpp')
                    ->select('*')
                    ->where('hpp.pengeluarandetailid', '=', $value->pengeluarandetailid)
                    ->where('flag', 'PJ')
                    ->first();

                if ($hpp) {

                    $fetchDataHppIf  = DB::table('hpp')
                        ->select(
                            'hpp.pengeluaranid',
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
                            DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.id
                            WHEN hpp.flag = 'RB' THEN returbelidetail.id
                            ELSE NULL
                        END AS pengeluarandetailid
                    "),
                            'hpp.penerimaanid',
                            'pembelianheader.nobukti as penerimaannobukti',
                            'hpp.penerimaandetailid',
                            DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                            WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                            ELSE NULL
                        END AS pengeluaranqty
                    "),
                            'hpp.penerimaanharga',
                            'hpp.penerimaantotal',
                            DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.harga
                            WHEN hpp.flag = 'RB' THEN returbelidetail.harga
                            ELSE NULL
                        END AS pengeluaranhargahpp
                    "),
                            DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.harga * (pengeluaranqty - penjualandetail.qtyreturjual)
                            WHEN hpp.flag = 'RB' THEN returbelidetail.harga * returbelidetail.qty
                            ELSE NULL
                        END AS pengeluarantotalhpp
                    "),
                            'hpp.productid',
                        )
                        ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
                        ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
                        ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
                        ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
                        ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
                        ->where('hpp.id', '>=', $hpp->id)
                        ->get();

                    // dd($fetchDataHppIf);
                    $fetchDataHpp = $fetchDataHpp->concat($fetchDataHppIf);
                } else {
                    // dd($value);
                    $fetchDataHppElse = DB::table('penjualandetail')
                        ->select(
                            'penjualanheader.id as pengeluaranid',
                            "penjualanheader.nobukti as pengeluarannobukti",
                            "penjualandetail.id as pengeluarandetailid",
                            "penjualandetail.qty as pengeluaranqty",
                            "product.hargabeli as penerimaanharga",
                            DB::raw('penjualandetail.qty * product.hargabeli as penerimaantotal'),
                            "penjualandetail.harga as pengeluaranhargahpp",
                            DB::raw('penjualandetail.qty * penjualandetail.harga as pengeluarantotalhpp'),
                            "penjualandetail.productid",
                        )
                        ->leftJoin("penjualanheader", "penjualandetail.penjualanid", "penjualanheader.id")
                        ->leftJoin("pesananfinaldetail", "penjualandetail.pesananfinaldetailid", "pesananfinaldetail.id")
                        ->leftJoin("pesananfinalheader", "pesananfinalheader.id", "pesananfinaldetail.pesananfinalid")
                        ->leftJoin("product", "penjualandetail.productid", "product.id")
                        ->where("penjualanid", $penjualanHeader->id)
                        ->where("penjualandetail.productid", $value->productid)
                        ->first();

                    // dd($fetchDataHppElse);

                    $fetchDataHppElse = (object) [
                        'pengeluaranid' => $fetchDataHppElse->pengeluaranid,
                        'tglbukti' => $fetchDataHppElse->tglbukti,
                        'pengeluarannobukti' => $fetchDataHppElse->pengeluarannobukti,
                        'pengeluarandetailid' => $fetchDataHppElse->pengeluarandetailid,
                        'penerimaanid' => null,
                        'penerimaannobukti' => null,
                        'penerimaandetailid' => null,
                        'pengeluaranqty' => $fetchDataHppElse->pengeluaranqty,
                        'penerimaanharga' => $fetchDataHppElse->penerimaanharga,
                        'penerimaantotal' => $fetchDataHppElse->penerimaantotal,
                        'pengeluaranhargahpp' => $fetchDataHppElse->pengeluaranhargahpp,
                        'pengeluarantotalhpp' => $fetchDataHppElse->pengeluarantotalhpp,
                        'productid' => $fetchDataHppElse->productid,
                    ];
                    $fetchDataHpp = $fetchDataHpp->concat([$fetchDataHppElse]);
                }
                // dump($fetchDataHpp);
            }
            // die;
        } else {
            $filteredData = DB::table('hpp')
                ->select(
                    'hpp.id',
                    DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualanheader.tglbukti
                            WHEN hpp.flag = 'RB' THEN returbeliheader.tglbukti
                            ELSE NULL
                        END AS tglbukti
                    "),
                    'hpp.pengeluaranid',
                    'hpp.flag',
                    DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualanheader.nobukti
                            WHEN hpp.flag = 'RB' THEN returbeliheader.nobukti
                            ELSE NULL
                        END AS pengeluarannobukti
                    "),
                    'hpp.penerimaanid',
                    'pembelianheader.nobukti as penerimaannobukti',
                    'hpp.penerimaandetailid',
                    'hpp.penerimaanharga',
                    'hpp.penerimaantotal',
                    'hpp.productid',
                    DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                            WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                            ELSE NULL
                        END AS pengeluaranqty
                    "),
                    DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.id
                            WHEN hpp.flag = 'RB' THEN returbelidetail.id
                            ELSE NULL
                        END AS pengeluarandetailid
                    "),
                    DB::raw("
                        CASE
                            WHEN hpp.flag = 'PJ' THEN penjualandetail.harga
                            WHEN hpp.flag = 'RB' THEN returbelidetail.harga
                            ELSE NULL
                        END AS pengeluaranhargahpp
                    "),
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
                ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
                ->where('hpp.id', '>=', $hppRow->id)
                ->orderBy('id', 'asc')
                ->get();

            $hasNull = $filteredData->contains(function ($item, $key) {
                return $item->pengeluarandetailid === null;
            });
            if ($hasNull) {
                $fetchDataHpp = $filteredData->filter(function ($item, $key) {
                    return $item->pengeluarandetailid !== null;
                });
            } else {
                $fetchDataHpp = $filteredData;
            }
        }

        //DELETE HPP & KARTUSTOK
        $fetchHpp = DB::table('hpp')
            ->select(
                'hpp.pengeluaranid',
                'hpp.pengeluarannobukti',
                'pengeluarandetailid',
                'penerimaanid',
                'pembelianheader.nobukti as penerimaannobukti',
                'penerimaandetailid',
                'pengeluaranqty',
                'penerimaantotal',
                'hpp.productid',
            )
            ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
            ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
            ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
            ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
            ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
            ->where('hpp.id', '>=', $hppRow->id)
            ->get();

        $pengeluaranid = $fetchHpp->pluck('pengeluaranid')->toArray();
        $nobuktipenjualan = $fetchHpp->pluck('pengeluarannobukti')->toArray();
        $pengeluarandetailid = $fetchHpp->pluck('pengeluarandetailid')->toArray();
        $penerimaanid = $fetchHpp->pluck('penerimaanid')->toArray();
        $nobuktipembelian = $fetchHpp->pluck('penerimaannobukti')->toArray();
        $penerimaandetailid = $fetchHpp->pluck('penerimaandetailid')->toArray();
        $pengeluaranqty = $fetchHpp->pluck('pengeluaranqty')->toArray();
        $penerimaantotal = $fetchHpp->pluck('penerimaantotal')->toArray();
        $productid = $fetchHpp->pluck('productid')->toArray();

        $result = [
            'pengeluaranid' => $pengeluaranid,
            'nobuktipenjualan' => $nobuktipenjualan,
            'pengeluarandetailid' => $pengeluarandetailid,
            'penerimaanid' => $penerimaanid,
            'nobuktipembelian' => $nobuktipembelian,
            'penerimaandetailid' => $penerimaandetailid,
            'pengeluaranqty' => $pengeluaranqty,
            'penerimaantotal' => $penerimaantotal,
            'productid' => $productid,
        ];

        //DELETE RETURJUAL DI KARTU STOK
        if ($data != null) {
            $dataReturJual = ReturJualHeader::where('penjualanid', $penjualanHeader->id)->where('flag', 'generated')->first();
            if ($dataReturJual) {
                $kartuStok = KartuStok::where('nobukti', $dataReturJual->nobukti)->delete();
            }
        }

        //FOR HAPUS HPP, KARTU STOK, UPDATE QTY TERPAKAI DI PEMBELIAN
        for ($i = 0; $i < count($result['pengeluaranid']); $i++) {
            $pembelian = DB::table('pembeliandetail')
                ->select(
                    'qty',
                    'qtyterpakai',
                    'productid'
                )
                ->where('id', $result['penerimaandetailid'][$i])
                ->first();

            if ($pembelian) {
                $qtyterpakai = $pembelian->qtyterpakai - $result['pengeluaranqty'][$i] ?? 0;
                $pembeliandetail = PembelianDetail::where('id', $result['penerimaandetailid'][$i])->first();
                $pembeliandetail->qtyterpakai = $qtyterpakai;
                $pembeliandetail->save();
            }

            $hpp = HPP::where('pengeluarandetailid', $result['pengeluarandetailid'][$i])->first();
            if ($hpp) {
                $hpp->delete();
            }

            $kartuStok = KartuStok::where('pengeluarandetailid', $result['pengeluarandetailid'][$i])->first();
            if ($kartuStok) {
                $kartuStok->delete();
            }
        }

        // FOR CREATE HPP
        foreach ($fetchDataHpp as $row) {
            $flag = null;
            $flagkartustok = null;
            $seqno = 0;

            if ($row->pengeluarannobukti !== null) {
                if (strpos($row->pengeluarannobukti, 'J') === 0) {
                    $flag = 'PJ';
                    $flagkartustok = 'J';
                    $seqno = 2;
                } elseif (strpos($row->pengeluarannobukti, 'RB') === 0) {
                    $flag = 'RB';
                    $flagkartustok = 'RB';
                    $seqno = 4;
                }
            }

            $dataHpp = [
                "pengeluaranid" => $row->pengeluaranid,
                "tglbukti" => $row->tglbukti,
                "pengeluarannobukti" => $row->pengeluarannobukti,
                "pengeluarandetailid" => $row->pengeluarandetailid,
                "pengeluarannobukti" => $row->pengeluarannobukti,
                "productid" => $row->productid,
                "qtypengeluaran" => $row->pengeluaranqty,
                "hargapengeluaranhpp" => $row->pengeluaranhargahpp,
                "totalpengeluaranhpp" => $row->pengeluarantotalhpp,
                "flag" => $flag,
                "flagkartustok" => $flagkartustok,
                "seqno" => $seqno,
            ];
            $hpp = (new HPP())->processStore($dataHpp);
        }

        if ($data != null) {

            //CREATE / UPDATE RETUR JUAL
            $dataReturJual = ReturJualHeader::where('penjualanid', $penjualanHeader->id)->where('flag', 'generated')->first();
            if ($dataReturJual) {

                $returJualDetail = DB::table('returjualdetail')
                    ->select('*')
                    ->where('returjualid', $dataReturJual->id)
                    ->get();

                //UPDATE QTY TERPAKAI DARI RETUR JUAL
                for ($i = 0; $i < count($data['productid']); $i++) {
                    $pembelian = DB::table('hpp')
                        ->select(
                            'hpp.id',
                            'pengeluaranid',
                            'hpp.productid',
                            'qtyterpakai',
                            'penerimaandetailid'
                        )
                        ->leftJoin('pembeliandetail', 'hpp.penerimaandetailid', 'pembeliandetail.id')
                        ->where('pengeluarandetailid', $data['penjualandetailid'][$i])
                        ->first();

                    if ($pembelian) {
                        $qtyterpakai = $pembelian->qtyterpakai - $data['qtyreturbeli'][$i];
                        $pembelianDetail = PembelianDetail::where('id', $pembelian->penerimaandetailid)->first();
                        $pembelianDetail->qtyterpakai = $qtyterpakai;
                        $pembelianDetail->save();
                    }
                }

                if (!isset($data['id'])) {
                    $data['id'] = [];
                }
                $returJualDetailIds = $returJualDetail->pluck('id', 'penjualandetailid')->toArray();
                foreach ($data['penjualandetailid'] as $penjualandetailid) {
                    $data['id'][$penjualandetailid] = isset($returJualDetailIds[$penjualandetailid]) ? $returJualDetailIds[$penjualandetailid] : 0;
                }
                $data['id'] = array_values($data['id']);

                // dd($data);
                $returJualHeader = ReturJualHeader::findOrFail($dataReturJual->id);
                $returJual = (new ReturJualHeader())->processUpdate($returJualHeader, $data);
            } else {
                $returJual = (new ReturJualHeader())->processStore($data);
            }

            $allReturDetails = [];
            $totalRetur = 0;
            $withRetur = [];
            $withoutRetur = [];

            $fetchPenjualan = DB::table('penjualandetail')
                ->select('*')
                ->leftJoin('hpp', 'penjualandetail.id', 'hpp.pengeluarandetailid')
                ->where('penjualanid', $penjualanHeader->id)
                ->get();


            // dd($fetchPenjualan);


            foreach ($fetchPenjualan as $fetch) {

                $returBeliHeader = DB::table('returbeliheader')
                    ->select('*')
                    ->where('pembelianid', $fetch->penerimaanid)
                    ->where('flag', 'generated')
                    ->first();

                // dd($returBeliHeader, $fetch);

                if ($returBeliHeader) {
                    if ($fetch->qtyreturjual != 0) {
                        // dd('test');
                    } else {
                        $fetchReturDetail = DB::table('returbelidetail')
                            ->select('*')
                            ->where('returbeliid', $returBeliHeader->id)
                            ->get();

                        if ($fetchReturDetail) {
                            if ($fetchReturDetail->count() === 1) {
                                foreach ($fetchReturDetail as $value) {
                                    if ($fetch->productid == $value->productid) {
                                        $returHeaderFetch = (new ReturBeliHeader())->processDestroy($value->returbeliid, "DELETE RETUR BELI HEADER");
                                    }
                                }
                            } else {
                                foreach ($fetchReturDetail as $value) {
                                    if ($fetch->productid) {
                                        $returDetail = DB::table('returbelidetail')
                                            ->select('*', 'returbelidetail.id as returbelidetailid')
                                            ->leftJoin('returbeliheader', 'returbelidetail.returbeliid', 'returbeliheader.id')
                                            ->where('pembelianid', $fetch->penerimaanid)
                                            ->where('productid', $fetch->productid)
                                            ->first();

                                        if ($returDetail) {
                                            $kartuStok = DB::table('kartustok')->where('pengeluarandetailid', $returDetail->returbelidetailid)->delete();

                                            $hpp = DB::table('hpp')->where('pengeluarandetailid', $returDetail->returbelidetailid)->delete();

                                            $test = DB::table('returbelidetail')->where('id', $returDetail->returbelidetailid)->delete();
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                }
            }

            for ($i = 0; $i < count($data['penjualandetailid']); $i++) {
                $fetchHpp = DB::table('hpp')
                    ->select('*')
                    ->where('pengeluarandetailid', $data['penjualandetailid'][$i])
                    ->first();

                $dataReturBeli = ReturBeliHeader::where('pembelianid', $fetchHpp->penerimaanid)->where('flag', 'generated')->first();

                $penjualanDetailId = $data['penjualandetailid'][$i];
                $returJualDetail = DB::table('returjualdetail')
                    ->select('*')
                    ->where('penjualandetailid', $penjualanDetailId)
                    ->first();

                //CREATE / UPDATE RETUR BELI
                if ($dataReturBeli) {
                    $getReturDetail = DB::table("returbelidetail")->where('pembeliandetailid', $fetchHpp->penerimaandetailid)->first();

                    $pembelian = DB::table('pembeliandetail')
                        ->select('*')
                        ->where('id', $fetchHpp->penerimaandetailid)
                        ->first();
                    if ($getReturDetail) {
                        $returDetail = [
                            'pembelianid' => $fetchHpp->penerimaanid,
                            'nobuktirb' => $dataReturBeli->nobukti,
                            'id' => $getReturDetail->id ?? 0,
                            'pembeliandetailid' => $fetchHpp->penerimaandetailid,
                            'productid' => $data['productid'][$i],
                            'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
                            'keterangan' => $data['keterangandetail'][$i] ?? '',
                            'qtyreturbeli' => $data['qtyreturbeli'][$i] ?? 0,
                            'satuanid' => $data['satuanid'][$i] ?? 0,
                            'harga' => $data['hargabeli'][$i] ?? 0,
                            'modifiedby' => auth('api')->user()->id,
                            'returjualdetailid' => $returJualDetail->id,

                        ];
                    } else {
                        $returDetail = [
                            'pembelianid' => $fetchHpp->penerimaanid,
                            'nobuktirb' => $dataReturBeli->nobukti,
                            'id' => 0,
                            'pembeliandetailid' => $fetchHpp->penerimaandetailid,
                            'productid' => $data['productid'][$i],
                            'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
                            'keterangan' => $data['keterangandetail'][$i] ?? '',
                            'qtyreturbeli' => $data['qtyreturbeli'][$i] ?? 0,
                            'satuanid' => $data['satuanid'][$i] ?? 0,
                            'harga' => $data['hargabeli'][$i] ?? 0,
                            'modifiedby' => auth('api')->user()->id,
                            'returjualdetailid' => $returJualDetail->id,

                        ];
                    }
                    $allReturDetails['withRetur'][] = $returDetail;

                    // dd($allReturDetails);
                } else {

                    $returDetail = [
                        'pembelianid' => $fetchHpp->penerimaanid,
                        'pembeliandetailid' => $fetchHpp->penerimaandetailid,
                        'productid' => $data['productid'][$i],
                        'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i] ?? 0,
                        'keterangan' => $data['keterangandetail'][$i] ?? '',
                        'qtyreturbeli' => $data['qtyreturbeli'][$i] ?? 0,
                        'satuanid' => $data['satuanid'][$i] ?? 0,
                        'harga' => $data['hargabeli'][$i] ?? 0,
                        'modifiedby' => auth('api')->user()->id,
                        'returjualdetailid' => $returJualDetail->id,

                    ];
                    $allReturDetails['withoutRetur'][] = $returDetail;
                }
                if ($dataReturBeli) {
                    $withRetur[] = $returDetail;
                } else {
                    $withoutRetur[] = $returDetail;
                }

                $totalRetur += $returDetail['harga'] * $returDetail['qtyreturbeli'];
            }

            $allReturDetails['withRetur'] = $withRetur;
            $allReturDetails['withoutRetur'] = $withoutRetur;

            //RETUR EXIST
            if (!empty($allReturDetails['withRetur'])) {
                $returHeaders = [];
                foreach ($allReturDetails['withRetur'] as $detail) {
                    $pembelianId = $detail['pembelianid'];
                    if (!isset($returHeaders[$pembelianId])) {
                        $pembelianHeader = DB::table('pembelianheader')
                            ->select('*')
                            ->where('id', $detail['pembelianid'])
                            ->first();
                        $returHeaders[$detail['pembelianid']] = [
                            'tglbukti' =>  now(),
                            'pembelianid' => $pembelianId,
                            'pembeliannobukti' => $pembelianHeader->nobukti,
                            'supplierid' => $pembelianHeader->supplierid,
                            'total' => $totalRetur,
                            'flag' => 'generated',
                            'details' => [],
                        ];
                    }

                    $returHeaders[$pembelianId]['details'][] = [
                        'id' => $detail['id'],
                        'pesananfinaldetailid' => $detail['pesananfinaldetailid'],
                        'pembeliandetailid' => $detail['pembeliandetailid'],
                        'returjualdetailid' => $detail['returjualdetailid'],
                        'productid' => $detail['productid'],
                        'satuanid' => $detail['satuanid'],
                        'keterangan' => $detail['keterangan'],
                        'qty' => $detail['qtyreturbeli'],
                        'harga' => $detail['harga'],
                        'modifiedby' => $detail['modifiedby']
                    ];
                }

                $resultWithRetur = [];
                foreach ($returHeaders as $returHeader) {
                    $details = array_column($returHeader['details'], null);
                    unset($returHeader['details']);
                    $resultWithRetur[] = array_merge($returHeader, [
                        'id' => array_column($details, 'id'),
                        'pesananfinaldetailid' => array_column($details, 'pesananfinaldetailid'),
                        'pembeliandetailid' => array_column($details, 'pembeliandetailid'),
                        'returjualdetailid' => array_column($details, 'returjualdetailid'),
                        'productid' => array_column($details, 'productid'),
                        'satuanid' => array_column($details, 'satuanid'),
                        'keterangan' => array_column($details, 'keterangan'),
                        'qty' => array_column($details, 'qty'),
                        'harga' => array_column($details, 'harga'),
                        'modifiedby' => array_column($details, 'modifiedby')
                    ]);
                }

                foreach ($resultWithRetur as $result) {
                    $dataRetur = ReturBeliHeader::where('pembelianid', $result['pembelianid'])->where('flag', 'generated')->first();

                    if ($dataRetur != '') {
                        $returBeli = new ReturBeliHeader();
                        $returBeli = $returBeli->find($dataRetur->id);
                        // dd($dataRetur, $result);
                        $returBeli = (new ReturBeliHeader())->processUpdate($dataRetur, $result);
                    }
                }
            }

            //RETUR NOT EXIST
            if (!empty($allReturDetails['withoutRetur'])) {
                $returHeaders = [];
                foreach ($allReturDetails['withoutRetur'] as $detail) {
                    $pembelianHeader = DB::table('pembelianheader')
                        ->select('*')
                        ->where('id', $detail['pembelianid'])
                        ->first();

                    $returHeaders[$detail['pembelianid']] = [
                        'tglbukti' =>  now(),
                        'pembelianid' => $pembelianHeader->id,
                        'pembeliannobukti' => $pembelianHeader->nobukti,
                        'supplierid' => $pembelianHeader->supplierid,
                        'total' => $totalRetur,
                        'flag' => 'generated',
                    ];
                }

                foreach ($allReturDetails['withoutRetur'] as $detail) {
                    $returHeader = $returHeaders[$detail['pembelianid']];

                    $resultWithoutRetur = array_merge($returHeader, [
                        'pesananfinaldetailid' => [$detail['pesananfinaldetailid']],
                        'pembeliandetailid' => [$detail['pembeliandetailid']],
                        'returjualdetailid' => [$detail['returjualdetailid']],
                        'productid' => [$detail['productid']],
                        'satuanid' => [$detail['satuanid']],
                        'keterangan' => [$detail['keterangan']],
                        'qty' => [$detail['qtyreturbeli']],
                        'harga' => [$detail['harga']],
                        'modifiedby' => [$detail['modifiedby']]
                    ]);
                    // dd($resultWithoutRetur);
                    $returBeli = (new ReturBeliHeader())->processStore($resultWithoutRetur);
                }
            }
        } else {
            $dataReturJual = DB::table('returjualheader')
                ->select('*')
                ->where('penjualanid', $penjualanHeader->id)
                ->where('flag', 'generated')
                ->first();

            if ($dataReturJual) {
                $dataReturJualDetail = DB::table('returjualdetail')
                    ->select('*')
                    ->where('returjualid', $dataReturJual->id)
                    ->get();

                $result = [
                    'tglbukti' => $dataReturJual->tglbukti,
                    'penjualanid' => $dataReturJual->penjualanid,
                    'penjualannobukti' => $penjualanHeader->nobukti,
                    'returjualnobukti' => $dataReturJual->nobukti,
                    'customerid' => $dataReturJual->customerid,
                    'total' => $dataReturJual->total,
                    'flag' => $dataReturJual->flag,
                    'penjualandetailid' => [],
                    'returjualdetailid' => [],
                    'productid' => [],
                    'satuanid' => [],
                    'keterangan' => [],
                    'qty' => [],
                    'harga' => [],
                    'modifiedby' => [],
                ];
                foreach ($dataReturJualDetail as $detail) {
                    $result['penjualandetailid'][] = $detail->penjualandetailid;
                    $result['returjualdetailid'][] = $detail->id;
                    $result['productid'][] = $detail->productid;
                    $result['satuanid'][] = $detail->satuanid;
                    $result['keterangan'][] = $detail->keterangan;
                    $result['qty'][] = $detail->qty;
                    $result['harga'][] = $detail->harga;
                    $result['modifiedby'][] = $detail->modifiedby;
                }


                for ($i = 0; $i < count($result['returjualdetailid']); $i++) {
                    $fetchHpp = DB::table('hpp')
                        ->select('*')
                        ->where('pengeluarandetailid', $result['penjualandetailid'][$i])
                        ->first();

                    $dataReturBeli = ReturBeliHeader::where('pembelianid', $fetchHpp->penerimaanid)->where('flag', 'generated')->first();

                    $dataReturBeliDetail = DB::table('returbelidetail')
                        ->select('*', 'returbelidetail.id as returbelidetailid')
                        ->leftJoin('returbeliheader', 'returbelidetail.returbeliid', 'returbeliheader.id')
                        ->where('returbeliid', $dataReturBeli->id)
                        ->get();

                    foreach ($dataReturBeliDetail as $value) {
                        if ($value->returjualdetailid === $result['returjualdetailid'][$i]) {

                            //DELETE RETUR BELI DETAIL
                            $details = ReturBeliDetail::where('id', $value->returbelidetailid)->first();
                            if ($details) {
                                $details->delete();
                            }

                            //UPDATE QTY RETUR PEMBELIAN DETAIL
                            $pembelianDetails =  PembelianDetail::where('id', $value->pembeliandetailid)->first();
                            if ($pembelianDetails) {
                                $pembelianDetails->qtyretur = 0;
                                $pembelianDetails->save();
                            }
                        }
                    }
                    $fetchReturBeli = ReturBeliDetail::where('returbeliid', $dataReturBeli->id)->first();

                    // dd($fetchReturBeli);

                    if ($fetchReturBeli === null) {
                        //DELETE RETUR BELI HEADER
                        $returBeli = (new ReturBeliHeader())->processDestroy($dataReturBeli->id, "DELETE RETUR BELI HEADER");
                    } else {
                        //UPDATE TOTAL RETUR BELI HEADER
                        $total = 0;
                        $detail = DB::table('returbelidetail')
                            ->select('*')
                            ->where('returbeliid', $dataReturBeli->id)
                            ->get();

                        foreach ($detail as $item) {
                            $total += $item->qty * $item->harga;
                        }
                        $dataReturBeli->total = $total;
                        if (!$dataReturBeli->save()) {
                            throw new \Exception("Error updating Retur Beli Header.");
                        }

                        //UPDATE NOMINAL PIUTANG
                        $piutang = DB::table('piutang')
                            ->where('penjualanid', $dataReturBeli->id)
                            ->update([
                                'nominalpiutang' => $dataReturBeli->total,
                                'nominalsisa' => $dataReturBeli->total,
                                'updated_at' => $dataReturBeli->updated_at,
                            ]);
                    }
                }
                // dd('test');
                //DELETE RETUR JUAL HEADER
                $returJual = (new ReturJualHeader())->processDestroy($dataReturJual->id, "DELETE RETUR JUAL HEADER");
            }
        }

        // $test = DB::table('pembeliandetail')
        //     ->select('*')
        //     ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
        //     ->where('pembelianheader.tglbukti', "2024-04-22")
        //     ->get();

        // dd($test);

        // $test = DB::table('pembeliandetail')
        //     ->select('*')
        //     ->where('pembelianid', 173)
        //     ->get();

        // dd($test);

        // die;
        return $penjualanHeader;
    }

    public function processDestroy($id, $postingDari = ''): PenjualanHeader
    {
        $hpp = DB::table('hpp')
            ->select('*')
            ->where('hpp.pengeluaranid', '=', $id)
            ->where('flag', 'PJ')
            ->orderBy('id', 'desc')
            ->first();

        $hppDelete = DB::table('hpp')
            ->select('*')
            ->where('hpp.pengeluaranid', '=', $id)
            ->where('flag', 'PJ')
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
                DB::raw("CASE
                        WHEN hpp.flag = 'PJ' THEN penjualandetail.id
                        WHEN hpp.flag = 'RB' THEN returbelidetail.id
                        ELSE NULL
                    END AS pengeluarandetailid
                "),
                'hpp.penerimaanid',
                'pembelianheader.nobukti as penerimaannobukti',
                'hpp.penerimaandetailid',
                DB::raw("CASE
                        WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                        WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                        ELSE NULL
                    END AS pengeluaranqty
                "),
                'hpp.penerimaanharga',
                'hpp.penerimaantotal',
                DB::raw("CASE
                        WHEN hpp.flag = 'PJ' THEN penjualandetail.harga
                        WHEN hpp.flag = 'RB' THEN returbelidetail.harga
                        ELSE NULL
                    END AS pengeluaranhargahpp
                "),
                DB::raw("CASE
                        WHEN hpp.flag = 'PJ' THEN penjualandetail.harga * (pengeluaranqty - penjualandetail.qtyreturjual)
                        WHEN hpp.flag = 'RB' THEN returbelidetail.harga * returbelidetail.qty
                        ELSE NULL
                    END AS pengeluarantotalhpp
                "),
                'hpp.productid',
            )
            ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
            ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
            ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
            ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
            ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
            ->where('hpp.id', '>', $hpp->id)
            ->get();

        // dd($fetchDataHpp);

        //DELETE HPP & KARTUSTOK
        $fetchHpp = DB::table('hpp')
            ->select(
                'hpp.pengeluaranid',
                DB::raw("
                    CASE
                        WHEN hpp.flag = 'PJ' THEN penjualanheader.nobukti
                        WHEN hpp.flag = 'RB' THEN returbeliheader.nobukti
                        ELSE NULL
                    END AS pengeluarannobukti
                "),
                'pengeluarandetailid',
                'penerimaanid',
                'pembelianheader.nobukti as penerimaannobukti',
                'penerimaandetailid',
                'pengeluaranqty',
                'penerimaantotal',
                'productid',
            )
            ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
            ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
            ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
            ->where('hpp.id', '>=', $hppDelete->id)
            ->get();

        // dd($fetchHpp);

        foreach ($fetchHpp as $fetch) {
            $pembelian = DB::table('pembeliandetail')
                ->select(
                    'qty',
                    'qtyterpakai',
                    'productid'
                )
                ->where('id', $fetch->penerimaandetailid)
                ->first();

            if ($pembelian) {
                $qtyterpakai = $pembelian->qtyterpakai - $fetch->pengeluaranqty;
                $pembelian = PembelianDetail::where('id', $fetch->penerimaandetailid)->first();
                $pembelian->qtyterpakai = $qtyterpakai;
                $pembelian->save();
            }
            $hpp = HPP::where('pengeluaranid', $fetch->pengeluaranid)->first();
            if ($hpp) {
                $hpp->delete();
            }
            $kartuStok = KartuStok::where('nobukti', $fetch->pengeluarannobukti)->first();
            if ($kartuStok) {
                $kartuStok->delete();
            }
        }

        $penjualanHeader = PenjualanDetail::where('penjualanid', '=', $id)->get();
        $dataDetail = $penjualanHeader->toArray();

        /*DELETE EXISTING PENJUALAN HEADER*/
        $penjualanHeader = new PenjualanHeader();
        $penjualanHeader = $penjualanHeader->lockAndDestroy($id);
        $penjualanHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $penjualanHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $penjualanHeader->id,
            'nobuktitrans' => $penjualanHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $penjualanHeader->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        (new LogTrail())->processStore([
            'namatabel' => 'PENJUALANDETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $penjualanHeaderLogTrail['id'],
            'nobuktitrans' => $penjualanHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->name
        ]);

        // DELETE PIUTANG
        $getPiutang = DB::table("piutang")
            ->select('id', 'nobukti')
            ->where('penjualanid', '=', $id)
            ->first();
        $piutang = new Piutang();
        $piutang->processDestroy($getPiutang->id);

        //CREATE ULANG HPP
        foreach ($fetchDataHpp as $row) {
            $flag = null;
            $flagkartustok = null;
            $seqno = 0;

            if ($row->pengeluarannobukti !== null) {
                if (strpos($row->pengeluarannobukti, 'J') === 0) {
                    $flag = 'PJ';
                    $flagkartustok = 'J';
                    $seqno = 2;
                } elseif (strpos($row->pengeluarannobukti, 'RB') === 0) {
                    $flag = 'RB';
                    $flagkartustok = 'RB';
                    $seqno = 4;
                }
            }

            $dataHpp = [
                "pengeluaranid" => $row->pengeluaranid,
                "tglbukti" => $row->tglbukti,
                "pengeluarannobukti" => $row->pengeluarannobukti,
                "pengeluarandetailid" => $row->pengeluarandetailid,
                "pengeluarannobukti" => $row->pengeluarannobukti,
                "productid" => $row->productid,
                "qtypengeluaran" => $row->pengeluaranqty,
                "hargapengeluaranhpp" => $row->pengeluaranhargahpp,
                "totalpengeluaranhpp" => $row->pengeluarantotalhpp,
                "flag" => $flag,
                "flagkartustok" => $flagkartustok,
                "seqno" => $seqno,
            ];

            $hpp = (new HPP())->processStore($dataHpp);
        }

        return $penjualanHeader;
    }

    public function cekValidasiAksi($pesananfinalid, $id)
    {
        $pesananFinalHeader = PesananFinalHeader::from(DB::raw('pesananfinalheader'))->where('id', $pesananfinalid)->first();
        $returHeader = ReturJualHeader::from(DB::raw('returjualheader'))->where('penjualanid', $id)->first();
        $piutang = Piutang::from(DB::raw('piutang'))->where('penjualanid', $id)->first();

        if (isset($pesananFinalHeader)) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Penjualan ' . $pesananFinalHeader->nobukti,
                'kodeerror' => 'TDT'
            ];
            goto selesai;
        }

        if ($piutang->nominalpiutang != $piutang->nominalsisa) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Penjualan ' . $piutang->nobukti,
                'kodeerror' => 'SATL'
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
        return $data;
    }

    public function cekPembelian($pesananfinalheaderid)
    {
        $nobuktipembelians = [];
        foreach ($pesananfinalheaderid as $index => $id) {
            $pesananfinaldetail = PesananFinalDetail::where('pesananfinaldetail.pesananfinalid', $id)
                ->leftJoin(DB::raw("pesananfinalheader"), 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
                ->get();

            foreach ($pesananfinaldetail as $detail) {
                $nobuktipembelians[] = $detail->nobuktipembelian;
            }
        }
        return !empty($nobuktipembelians) ? $nobuktipembelians : '';
    }

    public function prosessHapusPenjualan($pesananfinalheaderid)
    {
        // dd($pesananfinalheaderid);
        $results = [];

        foreach ($pesananfinalheaderid as $index => $id) {
            $pesananfinaldetail = PesananFinalDetail::where('pesananfinaldetail.pesananfinalid', $id)
                ->leftJoin(DB::raw("pesananfinalheader"), 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
                ->get();

            foreach ($pesananfinaldetail as $detail) {
                $result = [];

                $pesananfinalheader = PenjualanHeader::where('pesananfinalid', $id)->first();

                if ($pesananfinalheader) {
                    $piutang = Piutang::where('penjualanid', $pesananfinalheader->id)->delete();
                    $deletedHeader = $pesananfinalheader->delete();

                    $deletedDetails = PenjualanDetail::where('penjualanid', $id)->delete();
                    $pesananfinalheaderUpdate = PesananFinalHeader::find($id);
                    $pesananfinaldetailUpdate = PesananFinalDetail::where('pesananfinalid', $id)->first();

                    if ($pesananfinalheaderUpdate) {
                        $pesananfinalheaderUpdate->update(['nobuktipenjualan' => '']);
                    }

                    $result['header_deleted'] = $deletedHeader;
                    $result['details_deleted'] = $deletedDetails;
                } else {
                    $result['header_deleted'] = false;
                    $result['details_deleted'] = false;
                }
                $results[] = $result;
            }
        }
        return $results;
    }

    public function editingAt($id, $btn)
    {
        $penjualanHeader = PenjualanHeader::find($id);
        if ($btn == 'EDIT') {
            $penjualanHeader->editingby = auth('api')->user()->name;
            $penjualanHeader->editingat = date('Y-m-d H:i:s');
        } else {
            if ($penjualanHeader->editingby == auth('api')->user()->name) {
                $penjualanHeader->editingby = '';
                $penjualanHeader->editingat = null;
            }
        }
        if (!$penjualanHeader->save()) {
            throw new \Exception("Error Update penjualan header.");
        }
        return $penjualanHeader;
    }

    public function editingateditall($btn, $today)
    {

        // $penjualanEditAll = DB::table("penjualanheader")->from(DB::raw("penjualanheader"))->where('tglbukti', $today)->get();
        $penjualanEditAll = DB::table("penjualanheader")->from(DB::raw("penjualanheader"))->get();

        if ($btn == 'EDIT ALL') {
            $getOldEditingby = DB::table("penjualanheader")->from(DB::raw("penjualanheader"))->first()->editingby ?? '';
            // $getOldEditingby = DB::table("penjualanheader")->from(DB::raw("penjualanheader"))->where('tglbukti', $today)->first()->editingby ?? '';

            $penjualanHeader = DB::table('penjualanheader')
                // ->where('tglbukti', $today)
                ->update([
                    'editingby' => auth('api')->user()->name,
                    'editingat' => date('Y-m-d H:i:s'),
                    'editall' => 'TRUE'
                ]);
            $data = [
                'editingby' => auth('api')->user()->name,
                'editingat' => date('Y-m-d H:i:s'),
                'oldeditingby' => $getOldEditingby
            ];

            return $data;
        } else {

            foreach ($penjualanEditAll as $penjualan) {

                if ($penjualan->editingby == auth('api')->user()->name) {

                    $penjualanHeader = DB::table('penjualanheader')
                        // ->where('tglbukti', $today)
                        ->update([
                            'editingby' => '',
                            'editingat' => null,
                            'editall' => ''
                        ]);
                }
            }
        }


        return $penjualanEditAll;
    }

    public function processData($data)
    {
        // dd($data);
        $productIds = [];
        $ids = [];
        $satuanIds = [];
        $qtys = [];
        $qtyReturJual = [];
        $satuanIds = [];
        $keteranganDetails = [];
        $pesananFinalDetails = [];
        $hargas = [];
        foreach ($data as $detail) {
            $ids[] = $detail['id'];
            $productIds[] = $detail['productid'];
            $satuanIds[] = $detail['satuanid'];
            $qtys[] = $detail['qty'];
            $qtyReturJual[] = $detail['qtyreturjual'];
            $qtyReturBeli[] = $detail['qtyreturbeli'];
            $keteranganDetails[] = $detail['keterangandetail'];
            $pesananFinalDetails[] = $detail['pesananfinaldetailid'] ?? 0;
            $hargas[] = $detail['harga'];
        }


        $data = [
            "tglbukti" => request()->tglbukti,
            "nobuktipesananfinal" => request()->nobuktipesananfinal,
            "customerid" => request()->customerid,
            "top" => request()->top,
            "alamatpengiriman" => request()->alamatpengiriman,
            "tglpengiriman" => request()->tglpengiriman,
            "tglbuktipesanan" => request()->tglbuktipesanan ?? '',
            "keterangan" => request()->keterangan,
            "status" => request()->status,
            "id" =>  $ids,
            "productid" =>  $productIds,
            "pesananfinaldetailid" =>  $pesananFinalDetails,
            "satuanid" => $satuanIds,
            "qty" => $qtys,
            "qtyreturjual" => $qtyReturJual,
            "qtyreturbeli" => $qtyReturBeli,
            "discount" => request()->discount,
            "total" => request()->total,
            "tax" => request()->tax,
            "taxamount" => request()->taxamount,
            "subtotal" => request()->subtotal,
            "keterangandetail" => $keteranganDetails,
            "harga" => $hargas,
        ];

        // dd($data);

        return $data;
    }

    public function cekValidasi($nobukti, $id, $pesananfinalid)
    {
        $piutang = DB::table('piutang')
            ->from(
                DB::raw("piutang as a")
            )
            ->select(
                'a.penjualanid',
                'a.nominalpiutang',
                'a.nominalsisa',
            )
            ->where('a.penjualanid', '=', $id)
            ->first();

        if ($piutang->nominalpiutang != $piutang->nominalsisa) {

            if (request()->btn == 'DELETE') {
                $data = [
                    'kondisi' => true,
                    'btn' => 'true',
                    'keterangan' => 'Pembelian ' . $nobukti,
                    'kodeerror' => 'TBDPP'
                ];
            } else {
                $data = [
                    'kondisi' => true,
                    'btn' => 'true',
                    'keterangan' => 'Pembelian ' . $nobukti,
                    'kodeerror' => 'TBEPP'
                ];
            }

            goto selesai;
        }

        $pesananfinalheader = DB::table('pesananfinalheader')
            ->from(
                DB::raw("pesananfinalheader as a")
            )
            ->select(
                'a.id',
                'a.nobukti'
            )
            ->where('a.id', '=', $pesananfinalid)
            ->first();

        // dd(isset($pesananfinalheader) ||  !$pesananfinalheader);

        if (isset($pesananfinalheader) && request()->btn == 'DELETE') {
            $data = [
                'kondisi' => true,
                'btn' => true,
                'keterangan' => 'Penjualan ' . $pesananfinalheader->nobukti,
                'kodeerror' => 'TDT'
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

    public function findEditAll()
    {
        $tglpengiriman = date('Y-m-d', strtotime(request()->tglpengirimanjual));

        // dd($tglpengiriman);

        $this->setRequestParameters();
        $headers = DB::table('piutang as piutang')
            ->select(
                "piutang.nominalbayar",
                "penjualanheader.id",
                "penjualanheader.nobukti",
                "penjualanheader.tglbukti",
                "customer.id as customerid",
                "customer.nama as customernama",
                "penjualanheader.alamatpengiriman",
                "penjualanheader.tglpengiriman",
                "penjualanheader.keterangan",
                "top.id as topid",
                "top.text as topnama",
                DB::raw('IFNULL(penjualanheader.servicetax, 0) AS servicetax'),
                DB::raw('IFNULL(penjualanheader.tax, 0) AS tax'),
                DB::raw('IFNULL(penjualanheader.taxamount, 0) AS taxamount'),
                DB::raw('IFNULL(penjualanheader.discount, 0) AS discount'),
                DB::raw('IFNULL(penjualanheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(penjualanheader.total, 0) AS total'),
                "penjualanheader.discount",
                "penjualanheader.tglcetak",
                "pesananfinalheader.id as pesananfinalid",
                "pesananfinalheader.nobukti as nobuktipesananfinal",
                "parameter.id as status",
                "parameter.text as statusnama",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'penjualanheader.created_at',
                'penjualanheader.updated_at'
            )
            ->leftJoin(DB::raw("penjualanheader"), 'piutang.penjualanid', 'penjualanheader.id')
            ->leftJoin(DB::raw("customer"), 'penjualanheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'penjualanheader.status', 'parameter.id')
            ->leftJoin(DB::raw("parameter as top"), 'penjualanheader.top', 'top.id')
            ->leftJoin(DB::raw("pesananfinalheader"), 'penjualanheader.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'penjualanheader.modifiedby', 'modifier.id')
            ->where('penjualanheader.tglpengiriman', $tglpengiriman)
            ->where('piutang.flag', 'J');

        $this->totalRows = $headers->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($headers);
        $this->filter($headers);
        $this->paginate($headers);
        $headers = $headers->get();

        // dd($headers);
        $details = DB::table('penjualandetail')
            ->select(
                "penjualandetail.id",
                "penjualandetail.pesananfinaldetailid",
                "penjualanheader.id as penjualanid",
                "penjualanheader.nobukti as nobuktipesananfinal",
                "penjualandetail.pesananfinaldetailid as pesananfinaldetailid",
                "product.id as productid",
                "product.nama as productnama",
                "penjualandetail.keterangan as keterangandetail",
                "penjualandetail.qty",
                "penjualandetail.qtyreturjual",
                "penjualandetail.qtyreturbeli",
                "penjualandetail.harga",
                DB::raw('(penjualandetail.qty * penjualandetail.harga) AS totalharga'),
                "pesananfinaldetail.nobuktipembelian",
                "satuan.nama as satuannama",
                "satuan.id as satuanid",
                "modifier.id as modified_by_id",
                "modifier.name as modified_by",
                "penjualandetail.created_at",
                "penjualandetail.updated_at",
            )
            ->leftJoin(DB::raw("penjualanheader"), 'penjualandetail.penjualanid', 'penjualanheader.id')
            ->leftJoin(DB::raw("product"), 'penjualandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'penjualandetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("user as modifier"), 'penjualandetail.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("pesananfinaldetail"), 'penjualandetail.pesananfinaldetailid', 'pesananfinaldetail.id')
            ->orderBy('productnama', 'asc')
            ->get();
        // Group details based on penjualanid
        $groupedDetails = $details->groupBy('penjualanid');

        // Combine header and detail data
        $result = $headers->map(function ($header) use ($groupedDetails) {
            return [
                'id' => $header->id,
                'nobukti' => $header->nobukti,
                'tglbukti' => $header->tglbukti,
                'customerid' => $header->customerid,
                'customernama' => $header->customernama,
                'alamatpengiriman' => $header->alamatpengiriman,
                'tglpengiriman' => $header->tglpengiriman,
                'nominalbayar' => $header->nominalbayar,
                'keterangan' => $header->keterangan,
                'servicetax' => $header->servicetax,
                'tax' => $header->tax,
                'topid' => $header->topid,
                'top' => $header->topnama,
                'taxamount' => $header->taxamount,
                'discount' => $header->discount,
                'subtotal' => $header->subtotal,
                'total' => $header->total,
                'tglcetak' => $header->tglcetak,
                'pesananfinalid' => $header->pesananfinalid,
                'nobuktipesananfinal' => $header->nobuktipesananfinal,
                'statusnama' => $header->statusnama,
                'modifiedby' => $header->modifiedby,
                'modifiedby_name' => $header->modifiedby_name,
                'details' => $groupedDetails->get($header->id, []),
            ];
        });
        // Convert the result to an array
        $data = $result->toArray();
        // dd($data);
        return $data;
    }

    // public function processEditAllOld($dataPenjualan)
    // {
    //     $results = [];
    //     foreach ($dataPenjualan as $data) {
    //         if (empty($data)) {
    //             continue;
    //         }

    //         $idToUpdate = $data['id'];

    //         $penjualanHeader = PenjualanHeader::find($idToUpdate);

    //         if ($penjualanHeader) {
    //             $penjualanHeader->tglbukti = date('Y-m-d', strtotime($data['tglbuktieditall']));
    //             $penjualanHeader->nobukti = $data['nobukti'];
    //             $penjualanHeader->customerid = $data['customerid'];
    //             $penjualanHeader->alamatpengiriman = $data['alamatpengiriman'];
    //             $penjualanHeader->tglpengiriman =  date('Y-m-d', strtotime($data['tglpengirimaneditall']));
    //             $penjualanHeader->keterangan = $data['keterangan'];
    //             $penjualanHeader->subtotal = $data['subtotal'] ?? 0;
    //             $penjualanHeader->tax = $data['tax'] ?? 0;
    //             $penjualanHeader->taxamount = $data['taxamount'] ?? 0;
    //             $penjualanHeader->discount = $data['discount'] ?? 0;
    //             $penjualanHeader->total = $data['total'] ?? 0;
    //             $penjualanHeader->save();

    //             // Log the update in LogTrail
    //             (new LogTrail())->processStore([
    //                 'namatabel' => 'penjualanheader',
    //                 'postingdari' => 'EDIT PENJUALAN HEADER DARI EDIT ALL PENJUALAN',
    //                 'idtrans' => $penjualanHeader->id,
    //                 'nobuktitrans' => $penjualanHeader->id,
    //                 'aksi' => 'EDIT',
    //                 'datajson' => $penjualanHeader->toArray(),
    //                 'modifiedby' => auth('api')->user()->id,
    //             ]);


    //             $returDetails = [];
    //             $retur = 0;

    //             foreach ($data['details']['productnama[]'] as $index => $productName) {
    //                 $detailId = $data['details']['iddetail[]'][$index];
    //                 $detail = PenjualanDetail::find($detailId);
    //                 if ($detail) {
    //                     $detail->productid = $data['details']['productid[]'][$index];
    //                     $detail->qty = $data['details']['qty[]'][$index];
    //                     $detail->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
    //                     $detail->qtyretur = $data['details']['qtyretur[]'][$index] ?? 0;
    //                     $detail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
    //                     $detail->harga = $data['details']['harga[]'][$index] ?? 0;
    //                     $detail->save();

    //                     // Log the update in LogTrail
    //                     (new LogTrail())->processStore([
    //                         'namatabel' => 'penjualandetail',
    //                         'postingdari' => 'EDIT PENJUALAN DETAIL DARI EDIT ALL PENJUALAN',
    //                         'idtrans' => $detail->id,
    //                         'nobuktitrans' => $detail->id,
    //                         'aksi' => 'EDIT',
    //                         'datajson' => $detail->toArray(),
    //                         'modifiedby' => auth('api')->user()->id,
    //                     ]);
    //                 }

    //                 //Cek Data ReturJual
    //                 if ($detail->qtyretur != 0) {
    //                     $retur++;
    //                     $returDetail = [
    //                         'penjualandetailid' => $detail->id,
    //                         'productid' => $detail->productid,
    //                         'pesananfinaldetailid' => $detail->pesananfinaldetailid ?? 0,
    //                         'keterangan' => $detail->keterangan ?? '',
    //                         'qty' => $detail->qty ?? 0,
    //                         'qtyretur' => $detail->qtyretur ?? 0,
    //                         'satuanid' => $detail->satuanid ?? '',
    //                         'harga' => $detail->harga ?? 0,
    //                         'modifiedby' => auth('api')->user()->id,
    //                     ];
    //                     $returDetails[] = $returDetail;
    //                 }

    //                 // Update PesananFinalDetail
    //                 $pesananFinalDetail = PesananFinalDetail::find($data['details']['pesananfinaldetailid[]'][$index]);
    //                 if ($pesananFinalDetail) {
    //                     $pesananFinalDetail->productid = $data['details']['productid[]'][$index];
    //                     $pesananFinalDetail->qtyjual = $data['details']['qty[]'][$index];
    //                     $pesananFinalDetail->satuanid = $data['details']['satuanid[]'][$index] ?? 0;
    //                     $pesananFinalDetail->qtyreturjual = $data['details']['qtyretur[]'][$index] ?? 0;
    //                     $pesananFinalDetail->keterangan = $data['details']['keterangandetail[]'][$index] ?? '';
    //                     $pesananFinalDetail->hargajual = $data['details']['harga[]'][$index] ?? 0;

    //                     $pesananFinalDetail->save();

    //                     // Log the update in LogTrail
    //                     (new LogTrail())->processStore([
    //                         'namatabel' => 'pesananfinaldetail',
    //                         'postingdari' => 'EDIT PESANAN FINAL DETAIL DARI EDIT ALL PENJUALAN',
    //                         'idtrans' => $pesananFinalDetail->id,
    //                         'nobuktitrans' => $pesananFinalDetail->id,
    //                         'aksi' => 'EDIT',
    //                         'datajson' => $pesananFinalDetail->toArray(),
    //                         'modifiedby' => auth('api')->user()->id,
    //                     ]);
    //                 }

    //                 // Update Product
    //                 // $product = Product::find($data['details']['productid[]'][$index]);
    //                 // if ($product) {
    //                 //     $product->hargajual = $data['details']['harga[]'][$index];
    //                 //     $product->save();

    //                 //     // Log the update in LogTrail
    //                 //     (new LogTrail())->processStore([
    //                 //         'namatabel' => 'product',
    //                 //         'postingdari' => 'EDIT PRODUCT DARI EDIT ALL PENJUALAN',
    //                 //         'idtrans' => $product->id,
    //                 //         'nobuktitrans' => $product->id,
    //                 //         'aksi' => 'EDIT',
    //                 //         'datajson' => $product->toArray(),
    //                 //         'modifiedby' => auth('api')->user()->id,
    //                 //     ]);
    //                 // }
    //             }

    //             // Update PesananFinalHeader
    //             $pesananFinalHeaderId = $data['pesananfinalid'];
    //             $pesananFinalHeader = PesananFinalHeader::find($pesananFinalHeaderId);

    //             if ($pesananFinalHeader) {
    //                 $pesananFinalHeader->customerid = $data['customerid'];
    //                 $pesananFinalHeader->alamatpengiriman = $data['alamatpengiriman'];
    //                 $pesananFinalHeader->tglpengiriman =  date('Y-m-d', strtotime($data['tglpengirimaneditall']));
    //                 $pesananFinalHeader->keterangan = $data['keterangan'];
    //                 $pesananFinalHeader->subtotal = $data['subtotal'] ?? 0;
    //                 $pesananFinalHeader->tax = $data['tax'] ?? 0;
    //                 $pesananFinalHeader->taxamount = $data['taxamount'] ?? 0;
    //                 $pesananFinalHeader->discount = $data['discount'] ?? 0;
    //                 $pesananFinalHeader->total = $data['total'] ?? 0;

    //                 $pesananFinalHeader->save();

    //                 // Log the update in LogTrail
    //                 (new LogTrail())->processStore([
    //                     'namatabel' => 'pesananfinalheader',
    //                     'postingdari' => 'EDIT PESANAN FINAL HEADER DARI EDIT ALL PENJUALAN',
    //                     'idtrans' => $pesananFinalHeader->id,
    //                     'nobuktitrans' => $pesananFinalHeader->id,
    //                     'aksi' => 'EDIT',
    //                     'datajson' => $pesananFinalHeader->toArray(),
    //                     'modifiedby' => auth('api')->user()->id,
    //                 ]);
    //             }

    //             //Update Piutang
    //             $piutang = Piutang::where('penjualanid', $data['id'])->first();
    //             if ($piutang) {
    //                 $piutang->nominalpiutang = $data['total'];
    //                 $piutang->nominalsisa = $data['total'];

    //                 $piutang->save();

    //                 // Log the update in LogTrail
    //                 (new LogTrail())->processStore([
    //                     'namatabel' => 'piutang',
    //                     'postingdari' => 'EDIT PIUTANG DARI EDIT ALL PENJUALAN',
    //                     'idtrans' => $piutang->id,
    //                     'nobuktitrans' => $piutang->id,
    //                     'aksi' => 'EDIT',
    //                     'datajson' => $piutang->toArray(),
    //                     'modifiedby' => auth('api')->user()->id,
    //                 ]);
    //             }

    //             //Create ReturJual
    //             if ($retur > 0) {
    //                 $totalRetur = 0;
    //                 $details = [];

    //                 foreach ($returDetails as $detail) {
    //                     $details['pesananfinaldetailid'][] = $detail['pesananfinaldetailid'];
    //                     $details['penjualandetailid'][] = $detail['penjualandetailid'];
    //                     $details['productid'][] = $detail['productid'];
    //                     $details['satuanid'][] = $detail['satuanid'];
    //                     $details['keterangan'][] = $detail['keterangan'];
    //                     $details['qty'][] = $detail['qtyretur'];
    //                     $details['harga'][] = $detail['harga'];
    //                     $details['modifiedby'][] = $detail['modifiedby'];
    //                     $totalRetur += $detail['harga'] * $detail['qtyretur'];
    //                 }

    //                 $returHeader = [
    //                     'tglbukti' =>  now(),
    //                     'penjualanid' => $penjualanHeader->id,
    //                     'penjualannobukti' => $penjualanHeader->nobukti,
    //                     'customerid' => $penjualanHeader->customerid,
    //                     'total' => $totalRetur
    //                 ];

    //                 $result = array_merge($returHeader, $details);
    //                 $results[] = $result;
    //             }
    //         }
    //     }

    //     // dd($results);
    //     if (!empty($results)) {
    //         return [
    //             'penjualanHeader' => $dataPenjualan,
    //             'resultRetur' => $results
    //         ];
    //     } else {
    //         return [
    //             'penjualanHeader' => $dataPenjualan,
    //             'resultRetur' => null
    //         ];
    //     }
    // }

    // public function processEditAll($dataPenjualan)
    // {

    //     $dataPenjualan = array_filter($dataPenjualan);

    //     // $results = [];

    //     $tempHeader = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

    //     DB::statement("CREATE TEMPORARY TABLE $tempHeader (
    //         id INT UNSIGNED NULL,
    //         customerid INT,
    //         customernama VARCHAR(100),
    //         nobukti VARCHAR(100),
    //         tglbuktieditall DATE,
    //         pesananfinalid INT NULL,
    //         alamatpengiriman VARCHAR(500),
    //         tglpengirimaneditall DATE,
    //         keterangan VARCHAR(500),
    //         tax VARCHAR(500),
    //         taxamount VARCHAR(500),
    //         subtotal VARCHAR(500),
    //         total VARCHAR(500),
    //         discount VARCHAR(500),
    //         modifiedby VARCHAR(255),
    //         created_at DATETIME,
    //         updated_at DATETIME
    //     )");

    //     $tempDetail = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

    //     DB::statement("CREATE TEMPORARY TABLE $tempDetail (
    //         id INT UNSIGNED NULL,
    //         penjualanid INT NULL,
    //         productid INT NULL,
    //         pesananfinaldetailid INT NULL,
    //         keterangan VARCHAR(500),
    //         qty FLOAT,
    //         qtyretur FLOAT,
    //         satuanid INT NULL,
    //         harga FLOAT,
    //         modifiedby VARCHAR(255),
    //         created_at DATETIME,
    //         updated_at DATETIME

    //     )");

    //     // $retur = 0;
    //     foreach ($dataPenjualan as $data) {
    //         DB::table($tempHeader)->insert([
    //             'id' => $data['id'],
    //             'customerid' => $data['customerid'],
    //             'customernama' => $data['customernama'],
    //             'nobukti' => $data['nobukti'],
    //             'tglbuktieditall' => date('Y-m-d', strtotime($data['tglbuktieditall'])),
    //             'pesananfinalid' => $data['pesananfinalid'] ?? 0,
    //             'alamatpengiriman' => $data['alamatpengiriman'],
    //             'tglpengirimaneditall' => date('Y-m-d', strtotime($data['tglpengirimaneditall'])),
    //             'keterangan' => $data['keterangan'],
    //             'tax' => $data['tax'],
    //             'taxamount' => $data['taxamount'],
    //             'subtotal' => $data['subtotal'],
    //             'total' => $data['total'],
    //             'discount' => $data['discount'],
    //             'modifiedby' => auth('api')->user()->id,
    //             'created_at' => now(),
    //             'updated_at' => now()
    //         ]);

    //         // $returDetails = [];

    //         foreach ($data['details']['productnama[]'] as $index => $productName) {
    //             DB::table($tempDetail)->insert([
    //                 'id' => $data['details']['iddetail[]'][$index],
    //                 'penjualanid' => $data['details']['idheader[]'][$index],
    //                 'productid' => $data['details']['productid[]'][$index],
    //                 'pesananfinaldetailid' => $data['details']['pesananfinaldetailid[]'][$index],
    //                 'keterangan' => $data['details']['keterangandetail[]'][$index] ?? "",
    //                 'qty' => $data['details']['qty[]'][$index],
    //                 'qtyretur' => $data['details']['qtyretur[]'][$index],
    //                 'satuanid' => $data['details']['satuanid[]'][$index],
    //                 'harga' => $data['details']['harga[]'][$index],
    //                 'modifiedby' => auth('api')->user()->id,
    //                 'created_at' => now(),
    //                 'updated_at' => now()
    //             ]);
    //         }
    //     }

    //     $detailsPenjualan = DB::table("$tempDetail")->get();


    //     // update penjualan header
    //     $queryUpdate =  DB::table('penjualanheader as a')
    //         ->join("$tempHeader as b", 'a.id', '=', 'b.id')
    //         ->update([
    //             'a.id' => DB::raw('b.id'),
    //             'a.nobukti' => DB::raw('b.nobukti'),
    //             'a.tglbukti' => DB::raw('b.tglbuktieditall'),
    //             'a.pesananfinalid' => DB::raw('b.pesananfinalid'),
    //             'a.customerid' => DB::raw('b.customerid'),
    //             'a.alamatpengiriman' => DB::raw('b.alamatpengiriman'),
    //             'a.tglpengiriman' => DB::raw('b.tglpengirimaneditall'),
    //             'a.keterangan' => DB::raw('b.keterangan'),
    //             'a.tax' => DB::raw('b.tax'),
    //             'a.taxamount' => DB::raw('b.taxamount'),
    //             'a.subtotal' => DB::raw('b.subtotal'),
    //             'a.total' => DB::raw('b.total'),
    //             'a.discount' => DB::raw('b.discount'),
    //             'a.modifiedby' => DB::raw('b.modifiedby'),
    //             'a.created_at' => DB::raw('b.created_at'),
    //             'a.updated_at' => DB::raw('b.updated_at')

    //         ]);

    //     // update penjualan detail
    //     DB::table('penjualandetail as a')
    //         ->join("penjualanheader as b", 'a.penjualanid', '=', 'b.id')
    //         ->join("$tempDetail as c", 'a.id', '=', 'c.id')
    //         ->update([
    //             'a.id' => DB::raw('c.id'),
    //             'a.penjualanid' => DB::raw('c.penjualanid'),
    //             'a.productid' => DB::raw('c.productid'),
    //             'a.pesananfinaldetailid' => DB::raw('c.pesananfinaldetailid'),
    //             'a.keterangan' => DB::raw('c.keterangan'),
    //             'a.qty' => DB::raw('c.qty'),
    //             'a.qtyretur' => DB::raw('c.qtyretur'),
    //             'a.satuanid' => DB::raw('c.satuanid'),
    //             'a.harga' => DB::raw('c.harga'),
    //             'a.modifiedby' => DB::raw('c.modifiedby'),
    //             'a.created_at' => DB::raw('c.created_at'),
    //             'a.updated_at' => DB::raw('c.updated_at')
    //         ]);

    //     $queryTempHeader =  DB::table("$tempHeader as a")
    //         ->select(
    //             'a.id',
    //             'a.nobukti',
    //             'a.customerid',
    //             'b.satuanid',
    //             'b.keterangan',
    //             'b.qty',
    //             'b.qtyretur',
    //             'b.harga',
    //             'b.modifiedby',
    //             DB::raw('(b.harga * b.qtyretur) AS totalRetur'),
    //         )
    //         ->join("$tempDetail as c", 'a.id', '=', 'c.penjualanid')
    //         ->leftJoin("penjualandetail as b", 'c.id', '=', 'b.id')
    //         ->where("b.qtyretur", "!=", "0");


    //     $getDataHeader = $queryTempHeader->get();

    //     $detailHeader = [];
    //     $returDetail = [];
    //     foreach ($getDataHeader as $dataHeader) {
    //         $queryTempDetailRetur =  DB::table("$tempDetail as a")
    //             ->select(
    //                 'b.pesananfinaldetailid',
    //                 'b.id',
    //                 'b.productid',
    //                 'b.satuanid',
    //                 'b.keterangan',
    //                 'b.qty',
    //                 'b.qtyretur',
    //                 'b.harga',
    //                 'b.modifiedby',
    //                 'c.tglbuktieditall as tglbukti',
    //                 'c.id as penjualanid',
    //                 'c.nobukti',
    //                 'c.customerid',
    //                 DB::raw('(b.harga * b.qtyretur) AS totalRetur'),
    //             )
    //             ->leftJoin("$tempHeader as c", 'c.id', '=', 'a.penjualanid')
    //             ->leftJoin("penjualandetail as b", 'a.id', '=', 'b.id')
    //             ->where("b.qtyretur", "!=", "0")
    //             ->where("a.penjualanid", "=", $dataHeader->id);

    //         $detailHeader =  $queryTempDetailRetur->get()->toArray();

    //         $newDataDetail = [];


    //         $totalRetur = 0;
    //         foreach ($detailHeader as $row => $value) {
    //             $newDataDetail['pesananfinaldetailid'][$row] = $value->pesananfinaldetailid;
    //             $newDataDetail['penjualandetailid'][$row] = $value->id;
    //             $newDataDetail['productid'][$row] = $value->productid;
    //             $newDataDetail['satuanid'][$row] = $value->satuanid;
    //             $newDataDetail['keterangan'][$row] = $value->keterangan;
    //             $newDataDetail['qty'][$row] = $value->qty;
    //             $newDataDetail['qtyretur'][$row] = $value->qtyretur;
    //             $newDataDetail['harga'][$row] = $value->harga;
    //             $newDataDetail['modifiedby'][$row] = $value->modifiedby;
    //             $totalRetur += $value->harga * $value->qtyretur;
    //         }
    //     }

    //     $header = [
    //         'tglbukti' =>  now(),
    //         'penjualanid' => $dataHeader->id,
    //         'penjualannobukti' => $dataHeader->nobukti,
    //         'customerid' => $dataHeader->customerid,
    //         'total' => $totalRetur

    //     ];



    //     $returDetail[] = array_merge($header, $newDataDetail);
    //     $results[] = $returDetail;

    //     // update pesanan final header
    //     DB::table("pesananfinalheader as a")
    //         ->leftJoin("$tempHeader as b", 'a.id', '=', 'b.pesananfinalid')
    //         ->where("b.pesananfinalid", "!=", "0")
    //         ->update([
    //             'a.alamatpengiriman' => DB::raw('b.alamatpengiriman'),
    //             'a.tglpengiriman' => DB::raw('b.tglpengirimaneditall'),
    //             'a.keterangan' => DB::raw('b.keterangan'),
    //             'a.tax' => DB::raw('b.tax'),
    //             'a.taxamount' => DB::raw('b.taxamount'),
    //             'a.subtotal' => DB::raw('b.subtotal'),
    //             'a.total' => DB::raw('b.total'),
    //             'a.discount' => DB::raw('b.discount'),
    //             'a.modifiedby' => DB::raw('b.modifiedby'),
    //             'a.created_at' => DB::raw('b.created_at'),
    //             'a.updated_at' => DB::raw('b.updated_at')
    //         ]);

    //     // update pesanan final detail
    //     DB::table("pesananfinaldetail as a")
    //         ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.pesananfinaldetailid')
    //         ->where("b.pesananfinaldetailid", "!=", "0")
    //         ->update([
    //             'a.productid' => DB::raw('b.productid'),
    //             'a.qtyjual' => DB::raw('b.qty'),
    //             'a.satuanid' => DB::raw('b.satuanid'),
    //             'a.qtyreturjual' => DB::raw('b.qtyretur'),
    //             'a.keterangan' => DB::raw('b.keterangan'),
    //             'a.hargajual' => DB::raw('b.harga'),
    //             'a.modifiedby' => DB::raw('b.modifiedby'),
    //             'a.created_at' => DB::raw('b.created_at'),
    //             'a.updated_at' => DB::raw('b.updated_at')
    //         ]);

    //     DB::table('penjualandetail as a')
    //         ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.id')
    //         ->whereNull('b.id')
    //         ->delete();


    //     $insertAddRowQuery =  DB::table($tempDetail)
    //         ->where("id", '=', '0');


    //     DB::table('penjualandetail')->insertUsing(["id", "penjualanid", "productid", "pesananfinaldetailid", "keterangan", "qty", "qtyretur", "satuanid", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

    //     $query =  DB::table('piutang as a')
    //         ->join("$tempHeader as b", 'a.penjualanid', '=', 'b.id')
    //         ->update([
    //             'a.nominalpiutang' => DB::raw('b.total'),
    //             'a.nominalsisa' => DB::raw('b.total'),
    //             'a.updated_at' => DB::raw('b.updated_at')
    //         ]);

    //     foreach ($dataPenjualan as $data) {
    //         if (empty($data)) {
    //             continue;
    //         }

    //         $idToUpdate = $data['id'];

    //         $penjualanHeader = PenjualanHeader::find($idToUpdate);
    //         if ($penjualanHeader) {
    //             $returDetails = [];
    //             $retur = 0;

    //             foreach ($data['details']['productnama[]'] as $index => $productName) {
    //                 $detailId = $data['details']['iddetail[]'][$index];
    //                 $detail = PenjualanDetail::find($detailId);

    //                 if ($detail) {
    //                     if ($detail->qtyretur != 0) {
    //                         $retur++;
    //                         $returDetail = [
    //                             'penjualandetailid' => $detail->id,
    //                             'productid' => $detail->productid,
    //                             'pesananfinaldetailid' => $detail->pesananfinaldetailid ?? 0,
    //                             'keterangan' => $detail->keterangan ?? '',
    //                             'qty' => $detail->qty ?? 0,
    //                             'qtyretur' => $detail->qtyretur ?? 0,
    //                             'satuanid' => $detail->satuanid ?? '',
    //                             'harga' => $detail->harga ?? 0,
    //                             'modifiedby' => auth('api')->user()->id,
    //                         ];
    //                         $returDetails[] = $returDetail;

    //                         // dd($returDetails);
    //                     }
    //                 }
    //             }

    //             //Create ReturJual
    //             if ($retur > 0) {
    //                 $totalRetur = 0;
    //                 $details = [];

    //                 foreach ($returDetails as $detail) {
    //                     $details['pesananfinaldetailid'][] = $detail['pesananfinaldetailid'];
    //                     $details['penjualandetailid'][] = $detail['penjualandetailid'];
    //                     $details['productid'][] = $detail['productid'];
    //                     $details['satuanid'][] = $detail['satuanid'];
    //                     $details['keterangan'][] = $detail['keterangan'];
    //                     $details['qty'][] = $detail['qtyretur'];
    //                     $details['harga'][] = $detail['harga'];
    //                     $details['modifiedby'][] = $detail['modifiedby'];
    //                     $totalRetur += $detail['harga'] * $detail['qtyretur'];
    //                 }

    //                 $returHeader = [
    //                     'tglbukti' =>  now(),
    //                     'penjualanid' => $penjualanHeader->id,
    //                     'penjualannobukti' => $penjualanHeader->nobukti,
    //                     'customerid' => $penjualanHeader->customerid,
    //                     'total' => $totalRetur
    //                 ];

    //                 $result = array_merge($returHeader, $details);
    //                 $results[] = $result;
    //             }
    //         }
    //     }

    //     if (!empty($results)) {
    //         return [
    //             'penjualanHeader' => $dataPenjualan,
    //             'resultRetur' => $results
    //         ];
    //     } else {
    //         return [
    //             'penjualanHeader' => $dataPenjualan,
    //             'resultRetur' => null
    //         ];
    //     }
    // }

    public function processEditAll($dataPenjualan)
    {
        // dd($dataPenjualan);
        $dataPenjualan = array_filter($dataPenjualan);

        // dd($dataPenjualan);

        // $results = [];

        $tempHeader = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempHeader (
            id INT UNSIGNED NULL,
            customerid INT,
            customernama VARCHAR(100),
            top INT,
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
            penjualanid INT NULL,
            productid INT NULL,
            pesananfinaldetailid INT NULL,
            keterangan VARCHAR(500),
            qty FLOAT,
            qtyreturjual FLOAT,
            qtyreturbeli FLOAT,
            satuanid INT NULL,
            harga FLOAT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
         
        )");

        // dd($dataPenjualan);
        // $retur = 0;
        foreach ($dataPenjualan as $data) {
            DB::table($tempHeader)->insert([
                'id' => $data['id'],
                'customerid' => $data['customerid'],
                'customernama' => $data['customernama'],
                'top' => $data['top'],
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
                    'id' => $data['details']['iddetail[]'][$index],
                    'penjualanid' => $data['details']['idheader[]'][$index],
                    'productid' => $data['details']['productid[]'][$index],
                    'pesananfinaldetailid' => $data['details']['pesananfinaldetailid[]'][$index],
                    'keterangan' => $data['details']['keterangandetail[]'][$index] ?? "",
                    'qty' => $data['details']['qty[]'][$index],
                    'qtyreturjual' => $data['details']['qtyreturjual[]'][$index],
                    'qtyreturbeli' => $data['details']['qtyreturbeli[]'][$index],
                    'satuanid' => $data['details']['satuanid[]'][$index],
                    'harga' => $data['details']['harga[]'][$index],
                    'modifiedby' => auth('api')->user()->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $detailsPenjualan = DB::table("$tempDetail")->get();


        // update penjualan header
        $queryUpdate =  DB::table('penjualanheader as a')
            ->join("$tempHeader as b", 'a.id', '=', 'b.id')
            ->update([
                'a.id' => DB::raw('b.id'),
                'a.nobukti' => DB::raw('b.nobukti'),
                'a.tglbukti' => DB::raw('b.tglbuktieditall'),
                'a.pesananfinalid' => DB::raw('b.pesananfinalid'),
                'a.customerid' => DB::raw('b.customerid'),
                'a.top' => DB::raw('b.top'),
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

        // update penjualan detail
        DB::table('penjualandetail as a')
            ->join("penjualanheader as b", 'a.penjualanid', '=', 'b.id')
            ->join("$tempDetail as c", 'a.id', '=', 'c.id')
            ->update([
                'a.id' => DB::raw('c.id'),
                'a.penjualanid' => DB::raw('c.penjualanid'),
                'a.productid' => DB::raw('c.productid'),
                'a.pesananfinaldetailid' => DB::raw('c.pesananfinaldetailid'),
                'a.keterangan' => DB::raw('c.keterangan'),
                'a.qty' => DB::raw('c.qty'),
                'a.qtyreturjual' => DB::raw('c.qtyreturjual'),
                'a.qtyreturbeli' => DB::raw('c.qtyreturbeli'),
                'a.satuanid' => DB::raw('c.satuanid'),
                'a.harga' => DB::raw('c.harga'),
                'a.modifiedby' => DB::raw('c.modifiedby'),
                'a.created_at' => DB::raw('c.created_at'),
                'a.updated_at' => DB::raw('c.updated_at')
            ]);

        // update pesanan final header
        DB::table("pesananfinalheader as a")
            ->leftJoin("$tempHeader as b", 'a.id', '=', 'b.pesananfinalid')
            ->where("b.pesananfinalid", "!=", "0")
            ->update([
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

        // update pesanan final detail
        DB::table("pesananfinaldetail as a")
            ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.pesananfinaldetailid')
            ->where("b.pesananfinaldetailid", "!=", "0")
            ->update([
                'a.productid' => DB::raw('b.productid'),
                'a.qtyjual' => DB::raw('b.qty'),
                'a.satuanid' => DB::raw('b.satuanid'),
                'a.qtyreturjual' => DB::raw('b.qtyreturjual'),
                'a.qtyreturbeli' => DB::raw('b.qtyreturbeli'),
                'a.keterangan' => DB::raw('b.keterangan'),
                'a.hargajual' => DB::raw('b.harga'),
                'a.modifiedby' => DB::raw('b.modifiedby'),
                'a.created_at' => DB::raw('b.created_at'),
                'a.updated_at' => DB::raw('b.updated_at')
            ]);

        DB::table('penjualandetail as a')
            ->join("$tempHeader as b", 'a.penjualanid', '=', 'b.id')
            ->leftJoin("$tempDetail as c", 'a.id', '=', 'c.id')
            ->whereNull('c.id')
            ->delete();


        $insertAddRowQuery =  DB::table($tempDetail)
            ->where("id", '=', '0');


        $queryNew = DB::table('penjualandetail')->insertUsing(["id", "penjualanid", "productid", "pesananfinaldetailid", "keterangan", "qty", "qtyreturjual", "qtyreturbeli", "satuanid", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

        // Mendapatkan ID baru dari baris yang baru dimasukkan
        $newlyInsertedIds = DB::table('penjualandetail')->pluck('id');

        // dd($newlyInsertedIds);
        $updatedDataPenjualan = [];

        foreach ($dataPenjualan as $penjualan) {
            $updatedPenjualan = $penjualan;

            foreach ($penjualan['details']['productnama[]'] as $index => $productName) {
                // Memeriksa apakah ID detail adalah 0 dan memperbarui jika ya
                if ($penjualan['details']['iddetail[]'][$index] == "0") {
                    $updatedPenjualan['details']['iddetail[]'][$index] = $newlyInsertedIds->pop();
                }
            }

            // Menambahkan data penjualan yang telah diperbarui ke dalam array baru
            $updatedDataPenjualan[] = $updatedPenjualan;
        }

        $query =  DB::table('piutang as a')
            ->join("$tempHeader as b", 'a.penjualanid', '=', 'b.id')
            ->update([
                'a.nominalpiutang' => DB::raw('b.total'),
                'a.nominalsisa' => DB::raw('b.total'),
                'a.updated_at' => DB::raw('b.updated_at')
            ]);

        // dd($dataPenjualan);

        foreach ($dataPenjualan as $data) {
            if (empty($data)) {
                continue;
            }

            $idToUpdate = $data['id'];

            $penjualanHeader = PenjualanHeader::find($idToUpdate);
            if ($penjualanHeader) {
                $returDetails = [];
                $retur = 0;

                foreach ($data['details']['productnama[]'] as $index => $productName) {
                    $detailId = $data['details']['iddetail[]'][$index];
                    $detail = PenjualanDetail::find($detailId);

                    if ($detail) {
                        $hpp = DB::table('hpp')
                            ->select('*')
                            ->where('pengeluarandetailid', $detail->id)
                            ->first();

                        if ($detail->qtyreturjual != 0) {
                            $retur++;
                            $returDetail = [
                                'penjualandetailid' => $detail->id,
                                'productid' => $detail->productid,
                                'pesananfinaldetailid' => $detail->pesananfinaldetailid ?? 0,
                                'keterangan' => $detail->keterangan ?? '',
                                'qty' => $detail->qty ?? 0,
                                'qtyreturjual' => $detail->qtyreturjual ?? 0,
                                'qtyreturbeli' => $detail->qtyreturbeli ?? 0,
                                'satuanid' => $detail->satuanid ?? '',
                                'hargajual' => $detail->harga ?? 0,
                                'hargabeli' => $hpp->penerimaanharga ?? 0,
                                'modifiedby' => auth('api')->user()->id,
                            ];
                            $returDetails[] = $returDetail;

                            // dd($returDetails);
                        }
                    }
                }

                //Create ReturJual
                if ($retur > 0) {
                    $totalReturJual = 0;
                    $totalReturBeli = 0;
                    $details = [];

                    foreach ($returDetails as $detail) {
                        $details['pesananfinaldetailid'][] = $detail['pesananfinaldetailid'];
                        $details['penjualandetailid'][] = $detail['penjualandetailid'];
                        $details['productid'][] = $detail['productid'];
                        $details['satuanid'][] = $detail['satuanid'];
                        $details['keterangan'][] = $detail['keterangan'];
                        $details['qtyreturjual'][] = $detail['qtyreturjual'];
                        $details['qtyreturbeli'][] = $detail['qtyreturbeli'];
                        $details['hargajual'][] = $detail['hargajual'];
                        $details['hargabeli'][] = $detail['hargabeli'];
                        $details['modifiedby'][] = $detail['modifiedby'];
                        $totalReturJual += $detail['hargajual'] * $detail['qtyreturjual'];
                        $totalReturBeli += $detail['hargabeli'] * $detail['qtyreturbeli'];
                    }

                    $returHeader = [
                        'tglbukti' =>  now(),
                        'penjualanid' => $penjualanHeader->id,
                        'penjualannobukti' => $penjualanHeader->nobukti,
                        'customerid' => $penjualanHeader->customerid,
                        'totaljual' => $totalReturJual,
                        'totalbeli' => $totalReturBeli,
                        'flag' => 'generated'
                    ];

                    $result = array_merge($returHeader, $details);
                    $results[] = $result;
                }
            }

            foreach ($data['details']['productnama[]'] as $index => $productName) {
                $detailId = $data['details']['iddetail[]'][$index];
                $detail = PenjualanDetail::find($detailId);

                $hpp = DB::table('hpp')
                    ->select('*')
                    ->where('pengeluaranid', $detail->penjualanid)
                    ->where('pengeluarandetailid', $detailId)
                    ->first();

                // DATA GENERATED DARI PESANAN FINAL
                if ($data['details']['pesananfinaldetailid[]'][$index] !== 0) {

                    //UPDATE QTY PEMBELIAN DETAIL 
                    $updatePembelianDetail = PembelianDetail::where('id', $hpp->penerimaandetailid)->first();
                    if ($updatePembelianDetail) {
                        $updatePembelianDetail->qty = $data['details']['qty[]'][$index];
                        $updatePembelianDetail->qtypesanan = $data['details']['qty[]'][$index];
                        $updatePembelianDetail->qtyterpakai = $data['details']['qty[]'][$index];
                        $updatePembelianDetail->save();
                    }

                    //UPDATE HUTANG 
                    $pembelianDetails = DB::table('pembeliandetail')
                        ->select('*')
                        ->where('pembelianid', $hpp->penerimaanid)
                        ->get();

                    $totalharga = [];
                    foreach ($pembelianDetails as $detail) {
                        $totalharga[] = $detail->harga * $detail->qty;
                    }
                    $total = array_sum($totalharga);

                    $hutang = Hutang::where('pembelianid', $hpp->penerimaanid)->first();
                    if ($hutang) {
                        if ($hutang->nominalhutang == $hutang->nominalsisa) {
                            $hutang->nominalhutang = $total;
                            $hutang->nominalsisa = $total;
                        } else if ($hutang->nominalhutang == $hutang->nominalbayar) {
                            $hutang->nominalhutang = $total;
                            $hutang->nominalbayar = $total;
                        }
                    }

                    //DELETE KARTU STOK
                    $kartuStok = KartuStok::where('penerimaandetailid', $hpp->penerimaandetailid)->first();
                    $tglbuktiBeli = $kartuStok->tglbukti;
                    $nobuktiBeli = $kartuStok->nobukti;
                    if ($kartuStok) {
                        $kartuStok->delete();
                    }

                    //STORE KARTU STOK
                    $createKartuStok = (new KartuStok())->processStore([
                        "tglbukti" => $tglbuktiBeli,
                        "penerimaandetailid" => $updatePembelianDetail->id,
                        "pengeluarandetailid" => 0,
                        "nobukti" => $nobuktiBeli,
                        "productid" => $updatePembelianDetail['productid'],
                        "qtypenerimaan" =>  $data['details']['qty[]'][$index],
                        "totalpenerimaan" =>  $data['details']['qty[]'][$index] * $updatePembelianDetail['harga'],
                        "qtypengeluaran" => 0,
                        "totalpengeluaran" => 0,
                        "flag" => 'B',
                        "seqno" => 1
                    ]);
                }
            }
        }

        if (!empty($results)) {
            return [
                'penjualanHeader' => $updatedDataPenjualan,
                'resultRetur' => $results
            ];
        } else {
            return [
                'penjualanHeader' => $updatedDataPenjualan,
                'resultRetur' => null
            ];
        }
    }

    public function processEditHpp($penjualanHeader, $data)
    {
        //MENGUBAH URUTAN PENJUALAN HEADER
        usort($penjualanHeader, function ($a, $b) {
            return $a['id'] - $b['id'];
        });

        //MENGUBAH URUTAN DATA
        if ($data != null) {
            usort($data, function ($a, $b) {
                return $a['penjualanid'] - $b['penjualanid'];
            });
        }

        $dataPenjualans = [];
        foreach ($penjualanHeader as $row) {
            $details = $row['details'];
            unset($details['undefined']);

            foreach ($details as $fetch => $val) {
                for ($i = 0; $i < count($val); $i++) {
                    $dataPenjualans[$fetch][] = $val[$i];
                }
            }
        }
        $dataPenjualans['idheader[]'] = array_values(array_unique($dataPenjualans['idheader[]']));

        $hppRow = DB::table('hpp')
            ->select('*')
            ->where('pengeluaranid', $penjualanHeader[0]['id'])
            ->where('flag', 'PJ')
            ->first();

        $query = DB::table('penjualandetail')
            ->select('*', 'penjualandetail.id as pengeluarandetailid', 'hpp.id as hppid', 'penjualandetail.productid')
            ->leftJoin('hpp', 'hpp.pengeluarandetailid', 'penjualandetail.id')
            ->whereIn("penjualanid", $dataPenjualans['idheader[]'])
            ->get();

        $hasNullHppId = $query->contains(function ($item, $key) {
            return $item->hppid === null;
        });

        $filteredData = $query->filter(function ($item) {
            return $item->hppid !== null;
        });

        $firstNonNullHppIdData = $filteredData->first();

        $combinedArray = array_merge([$firstNonNullHppIdData], $query->reject(function ($item) {
            return $item->hppid !== null;
        })->toArray());

        //MEMBUAT DATA UNTUK CREATE HPP
        if ($hasNullHppId) {

            $fetchDataHpp = collect();
            foreach ($combinedArray as $detail => $value) {
                $hpp = DB::table('hpp')
                    ->select('*')
                    ->where('hpp.pengeluarandetailid', '=', $value->pengeluarandetailid)
                    ->where('flag', 'PJ')
                    ->first();

                if ($hpp) {
                    $fetchDataHppIf  = DB::table('hpp')
                        ->select(
                            'hpp.pengeluaranid',
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
                            DB::raw("CASE
                                    WHEN hpp.flag = 'PJ' THEN penjualandetail.id
                                    WHEN hpp.flag = 'RB' THEN returbelidetail.id
                                    ELSE NULL
                                END AS pengeluarandetailid
                            "),
                            'hpp.penerimaanid',
                            'pembelianheader.nobukti as penerimaannobukti',
                            'hpp.penerimaandetailid',
                            DB::raw("CASE
                                    WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                                    WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                                    ELSE NULL
                                END AS pengeluaranqty
                            "),
                            'hpp.penerimaanharga',
                            'hpp.penerimaantotal',
                            DB::raw("CASE
                                    WHEN hpp.flag = 'PJ' THEN penjualandetail.harga
                                    WHEN hpp.flag = 'RB' THEN returbelidetail.harga
                                    ELSE NULL
                                END AS pengeluaranhargahpp
                            "),
                            DB::raw("CASE
                                    WHEN hpp.flag = 'PJ' THEN penjualandetail.harga * (pengeluaranqty - penjualandetail.qtyreturjual)
                                    WHEN hpp.flag = 'RB' THEN returbelidetail.harga * returbelidetail.qty
                                    ELSE NULL
                                END AS pengeluarantotalhpp
                            "),
                            'hpp.productid',
                        )
                        ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
                        ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
                        ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
                        ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
                        ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
                        ->where('hpp.id', '>=', $hpp->id)
                        ->get();

                    $fetchDataHpp = $fetchDataHpp->concat($fetchDataHppIf);
                } else {
                    $fetchDataHppElse = DB::table('penjualandetail')
                        ->select(
                            'penjualanheader.id as pengeluaranid',
                            'penjualanheader.tglbukti',
                            "penjualanheader.nobukti as pengeluarannobukti",
                            "penjualandetail.id as pengeluarandetailid",
                            "penjualandetail.qty as pengeluaranqty",
                            "product.hargabeli as penerimaanharga",
                            DB::raw('penjualandetail.qty * product.hargabeli as penerimaantotal'),
                            "penjualandetail.harga as pengeluaranhargahpp",
                            DB::raw('penjualandetail.qty * penjualandetail.harga as pengeluarantotalhpp'),
                            "penjualandetail.productid",
                        )
                        ->leftJoin("penjualanheader", "penjualandetail.penjualanid", "penjualanheader.id")
                        ->leftJoin("pesananfinaldetail", "penjualandetail.pesananfinaldetailid", "pesananfinaldetail.id")
                        ->leftJoin("pesananfinalheader", "pesananfinalheader.id", "pesananfinaldetail.pesananfinalid")
                        ->leftJoin("product", "penjualandetail.productid", "product.id")
                        ->where("penjualanid", $value->penjualanid)
                        ->where("penjualandetail.productid", $value->productid)
                        ->first();

                    $fetchDataHppElse = (object) [
                        'pengeluaranid' => $fetchDataHppElse->pengeluaranid,
                        'tglbukti' => $fetchDataHppElse->tglbukti,
                        'pengeluarannobukti' => $fetchDataHppElse->pengeluarannobukti,
                        'pengeluarandetailid' => $fetchDataHppElse->pengeluarandetailid,
                        'penerimaanid' => null,
                        'penerimaannobukti' => null,
                        'penerimaandetailid' => null,
                        'pengeluaranqty' => $fetchDataHppElse->pengeluaranqty,
                        'penerimaanharga' => $fetchDataHppElse->penerimaanharga,
                        'penerimaantotal' => $fetchDataHppElse->penerimaantotal,
                        'pengeluaranhargahpp' => $fetchDataHppElse->pengeluaranhargahpp,
                        'pengeluarantotalhpp' => $fetchDataHppElse->pengeluarantotalhpp,
                        'productid' => $fetchDataHppElse->productid,
                    ];
                    $fetchDataHpp = $fetchDataHpp->concat([$fetchDataHppElse]);
                }
            }
        } else {
            $filteredData = DB::table('hpp')
                ->select(
                    'hpp.id',
                    DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualanheader.tglbukti
                            WHEN hpp.flag = 'RB' THEN returbeliheader.tglbukti
                            ELSE NULL
                        END AS tglbukti
                    "),
                    'hpp.pengeluaranid',
                    'hpp.flag',
                    DB::raw("CASE
                            WHEN hpp.flag = 'PJ' THEN penjualanheader.nobukti
                            WHEN hpp.flag = 'RB' THEN returbeliheader.nobukti
                            ELSE NULL
                        END AS pengeluarannobukti
                    "),
                    'hpp.penerimaanid',
                    'pembelianheader.nobukti as penerimaannobukti',
                    'hpp.penerimaandetailid',
                    'hpp.penerimaanharga',
                    'hpp.penerimaantotal',
                    'hpp.productid',
                    DB::raw("
                    CASE
                        WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                        WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                        ELSE NULL
                    END AS pengeluaranqty
                "),
                    DB::raw("
                    CASE
                        WHEN hpp.flag = 'PJ' THEN penjualandetail.id
                        WHEN hpp.flag = 'RB' THEN returbelidetail.id
                        ELSE NULL
                    END AS pengeluarandetailid
                "),
                    DB::raw("
                    CASE
                        WHEN hpp.flag = 'PJ' THEN penjualandetail.harga
                        WHEN hpp.flag = 'RB' THEN returbelidetail.harga
                        ELSE NULL
                    END AS pengeluaranhargahpp
                "),
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
                ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
                ->where('hpp.id', '>=', $hppRow->id)
                ->orderBy('id', 'asc')
                ->get();

            $hasNull = $filteredData->contains(function ($item, $key) {
                return $item->pengeluarandetailid === null;
            });

            if ($hasNull) {
                $fetchDataHpp = $filteredData->filter(function ($item, $key) {
                    return $item->pengeluarandetailid !== null;
                });
            } else {
                $fetchDataHpp = $filteredData;
            }
        }

        //DELETE HPP & KARTU STOK
        $fetchHpp = DB::table('hpp')
            ->select(
                'pengeluaranid',
                DB::raw("CASE
                        WHEN hpp.flag = 'PJ' THEN penjualanheader.nobukti
                        WHEN hpp.flag = 'RB' THEN returbeliheader.nobukti
                        ELSE NULL
                    END AS nobuktipenjualan
                "),
                'pengeluarandetailid',
                'penerimaanid',
                'pembelianheader.nobukti as nobuktipembelian',
                'penerimaandetailid',
                // DB::raw("
                //     CASE
                //         WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                //         WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                //         ELSE NULL
                //     END AS pengeluaranqty
                // "),
                'hpp.pengeluaranqty',
                'penerimaantotal',
                'hpp.productid',
            )
            ->leftJoin('penjualanheader', 'hpp.pengeluaranid', 'penjualanheader.id')
            ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
            ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
            ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
            ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
            ->where('hpp.id', '>=', $hppRow->id)
            ->get();

        // dd($fetchHpp);

        $pengeluaranid = $fetchHpp->pluck('pengeluaranid')->toArray();
        $nobuktipenjualan = $fetchHpp->pluck('nobuktipenjualan')->toArray();
        $pengeluarandetailid = $fetchHpp->pluck('pengeluarandetailid')->toArray();
        $penerimaanid = $fetchHpp->pluck('penerimaanid')->toArray();
        $nobuktipembelian = $fetchHpp->pluck('nobuktipembelian')->toArray();
        $penerimaandetailid = $fetchHpp->pluck('penerimaandetailid')->toArray();
        $pengeluaranqty = $fetchHpp->pluck('pengeluaranqty')->toArray();
        $penerimaantotal = $fetchHpp->pluck('penerimaantotal')->toArray();
        $productid = $fetchHpp->pluck('productid')->toArray();

        $result = [
            'pengeluaranid' => $pengeluaranid,
            'nobuktipenjualan' => $nobuktipenjualan,
            'pengeluarandetailid' => $pengeluarandetailid,
            'penerimaanid' => $penerimaanid,
            'nobuktipembelian' => $nobuktipembelian,
            'penerimaandetailid' => $penerimaandetailid,
            'pengeluaranqty' => $pengeluaranqty,
            'penerimaantotal' => $penerimaantotal,
            'productid' => $productid,
        ];

        //DELETE RETURJUAL DI KARTU STOK
        if ($data != null) {
            foreach ($data as $row) {
                $dataReturJual = ReturJualHeader::where('penjualanid', $row['penjualanid'])->where('flag', 'generated')->first();
                if ($dataReturJual) {
                    $kartuStok = KartuStok::where('nobukti', $dataReturJual->nobukti)->delete();
                }
            }
        }

        //HAPUS HPP, KARTU STOK, UPDATE QTY TERPAKAI DI PEMBELIAN
        for ($i = 0; $i < count($result['pengeluaranid']); $i++) {
            $pembelian = DB::table('pembeliandetail')
                ->select(
                    'qty',
                    'qtyterpakai',
                    'productid'
                )
                ->where('id', $result['penerimaandetailid'][$i])
                ->first();

            if ($pembelian) {
                $qtyterpakai = $pembelian->qtyterpakai - $result['pengeluaranqty'][$i] ?? 0;
                $pembelianDetail = PembelianDetail::where('id', $result['penerimaandetailid'][$i])->first();
                $pembelianDetail->qtyterpakai = $qtyterpakai;
                $pembelianDetail->save();
            }

            $hpp = HPP::where('pengeluaranid', $result['pengeluaranid'][$i])->first();
            if ($hpp) {
                $hpp->delete();
            }

            $kartuStok = KartuStok::where('nobukti', $result['nobuktipenjualan'][$i])->first();
            if ($kartuStok) {
                $kartuStok->delete();
            }
        }

        //CREATE ULANG HPP
        foreach ($fetchDataHpp as $row) {

            $flag = null;
            $flagkartustok = null;
            $seqno = 0;

            if ($row->pengeluarannobukti !== null) {
                if (strpos($row->pengeluarannobukti, 'J') === 0) {
                    $flag = 'PJ';
                    $flagkartustok = 'J';
                    $seqno = 2;
                } elseif (strpos($row->pengeluarannobukti, 'RB') === 0) {
                    $flag = 'RB';
                    $flagkartustok = 'RB';
                    $seqno = 4;
                }
            }

            $dataHpp = [
                "pengeluaranid" => $row->pengeluaranid,
                "tglbukti" => $row->tglbukti,
                "pengeluarannobukti" => $row->pengeluarannobukti,
                "pengeluarandetailid" => $row->pengeluarandetailid,
                "pengeluarannobukti" => $row->pengeluarannobukti,
                "productid" => $row->productid,
                "qtypengeluaran" => $row->pengeluaranqty,
                "hargapengeluaranhpp" => $row->pengeluaranhargahpp,
                "totalpengeluaranhpp" => $row->pengeluarantotalhpp,
                "flag" => $flag,
                "flagkartustok" => $flagkartustok,
                "seqno" => $seqno
            ];

            $hpp = (new HPP())->processStore($dataHpp);
        }

        //MEMBUAT TOTAL HARGA RETUR BELI
        if ($data != null) {
            $totals = [];

            foreach ($data as $item) {
                foreach ($item['productid'] as $key => $productid) {
                    $qty = $item['qtyreturbeli'][$key];

                    if (isset($totals[$productid])) {
                        $totals[$productid] += $qty;
                    } else {
                        $totals[$productid] = $qty;
                    }
                }
            }
        }

        //HAPUS RETUR BELI HEADER 
        if ($data !=  null) {
            $penjualanDetails = DB::table('penjualandetail')
                ->select('id')
                ->whereIn('penjualanid', $dataPenjualans['idheader[]'])
                ->get();

            $penjualanDetailIds = $penjualanDetails->pluck('id')->toArray();
            $returjualDetailss = DB::table('returjualdetail')
                ->select('id as returjualdetailid', 'penjualandetailid')
                ->whereIn('penjualandetailid', $penjualanDetailIds)
                ->get();
            $penjualandetailidss = [];
            foreach ($data as $item) {
                $penjualandetailidss[] = ['id' => $item['penjualandetailid'][0]];
            }
            $returjualDetailIds = $returjualDetailss->pluck('penjualandetailid')->toArray();
            $missingIds = array_diff($returjualDetailIds, array_column($penjualandetailidss, 'id'));
            $returjualDetailss2 = DB::table('returjualdetail')
                ->select('id as returjualdetailid', 'penjualandetailid')
                ->whereIn('penjualandetailid', $missingIds)
                ->get();
            $returjualDetailIds2 = $returjualDetailss2->pluck('returjualdetailid')->toArray();
            $returbeliDetailss = DB::table('returbelidetail')
                ->select('returbelidetail.id', 'pembelianid', 'returbeliheader.id as returbeliid')
                ->leftJoin('returbeliheader', 'returbelidetail.returbeliid', 'returbeliheader.id')
                ->whereIn('returjualdetailid', $returjualDetailIds2)
                ->get();

            foreach ($returbeliDetailss as $value) {

                $returbeliDetailFetch = DB::table('returbelidetail')
                    ->select('*')
                    ->where('returbeliid', $value->returbeliid)
                    ->get();

                if ($returbeliDetailFetch) {
                    if ($returbeliDetailFetch->count() === 1) {
                        (new ReturBeliHeader())->processDestroy($value->returbeliid, "DELETE RETURBELI HEADER");
                    } else {
                        DB::table('kartustok')->where('pengeluarandetailid', $value->id)->delete();
                        DB::table('hpp')->where('pengeluarandetailid', $value->id)->delete();
                        DB::table('returbelidetail')->where('id', $value->id)->delete();
                    }
                }
            }
        }

        //CREATE RETURJUAL DAN RETURBELI
        if ($data != null) {
            foreach ($data as $row) {
                //CREATE / UPDATE KARTU STOK
                $dataReturJual = ReturJualHeader::where('penjualanid', $row['penjualanid'])->where('flag', 'generated')->first();

                if ($dataReturJual) {

                    $returJualDetail = DB::table('returjualdetail')
                        ->select('*')
                        ->where('returjualid', $dataReturJual->id)
                        ->get();

                    //UPDATE QTY TERPAKAI DARI RETUR JUAL
                    for ($i = 0; $i < count($row['productid']); $i++) {
                        $pembelian = DB::table('hpp')
                            ->select(
                                'hpp.id',
                                'pengeluaranid',
                                'hpp.productid',
                                'qtyterpakai',
                                'penerimaandetailid'
                            )
                            ->leftJoin('pembeliandetail', 'hpp.penerimaandetailid', 'pembeliandetail.id')
                            ->where('pengeluarandetailid', $row['penjualandetailid'][$i])
                            ->first();

                        if ($pembelian) {
                            $qtyterpakai = $pembelian->qtyterpakai - $row['qtyreturbeli'][$i];
                            $pembelianDetail = PembelianDetail::where('id', $pembelian->penerimaandetailid)->first();
                            $pembelianDetail->qtyterpakai = $qtyterpakai;
                            $pembelianDetail->save();
                        }
                    }

                    if (!isset($row['id'])) {
                        $row['id'] = [];
                    }

                    $returJualDetailIds = $returJualDetail->pluck('id', 'penjualandetailid')->toArray();
                    foreach ($row['penjualandetailid'] as $penjualandetailid) {
                        $row['id'][$penjualandetailid] = isset($returJualDetailIds[$penjualandetailid]) ? $returJualDetailIds[$penjualandetailid] : 0;
                    }
                    $row['id'] = array_values($row['id']);

                    $returJualHeader = ReturJualHeader::findOrFail($dataReturJual->id);
                    $returJual = (new ReturJualHeader())->processUpdate($returJualHeader, $row);
                } else {
                    $returJual = (new ReturJualHeader())->processStore($row);
                }

                $allReturDetails = [];
                $totalRetur = 0;
                $withRetur = [];
                $withoutRetur = [];

                $fetchPenjualan = DB::table('penjualandetail')
                    ->select('*')
                    ->leftJoin('hpp', 'penjualandetail.id', 'hpp.pengeluarandetailid')
                    ->where('penjualanid', $row['penjualanid'])
                    ->get();

                foreach ($fetchPenjualan as $fetch) {
                    $returBeliHeader = DB::table('returbeliheader')
                        ->select('*')
                        ->where('pembelianid', $fetch->penerimaanid)
                        ->where('flag', 'generated')
                        ->first();

                    if ($returBeliHeader) {
                        if ($fetch->qtyreturbeli != null) {
                        } else {

                            $fetchReturDetail = DB::table('returbelidetail')
                                ->select('*')
                                ->where('returbeliid', $returBeliHeader->id)
                                ->get();

                            if ($fetchReturDetail) {
                                if ($fetchReturDetail->count() === 1) {
                                    foreach ($fetchReturDetail as $value) {
                                        if ($fetch->productid == $value->productid) {
                                            $returHeaderFetch = (new ReturBeliHeader())->processDestroy($value->returbeliid, "DELETE RETURBELI HEADER");
                                        }
                                    }
                                } else {
                                    foreach ($fetchReturDetail as $value) {
                                        if ($fetch->productid) {
                                            $returDetail = DB::table('returbelidetail')
                                                ->select('*', 'returbelidetail.id as returbelidetailid')
                                                ->leftJoin('returbeliheader', 'returbelidetail.returbeliid', 'returbeliheader.id')
                                                ->where('pembelianid', $fetch->penerimaanid)
                                                ->where('productid', $fetch->productid)
                                                ->first();

                                            if ($returDetail) {
                                                DB::table('kartustok')->where('pengeluarandetailid', $returDetail->returbelidetailid)->delete();

                                                DB::table('hpp')->where('pengeluarandetailid', $returDetail->returbelidetailid)->delete();

                                                DB::table('returbelidetail')->where('id', $returDetail->returbelidetailid)->delete();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                    }
                }

                for ($i = 0; $i < count($row['penjualandetailid']); $i++) {
                    $fetchHpp = DB::table('hpp')
                        ->select('*')
                        ->where('pengeluarandetailid', $row['penjualandetailid'][$i])
                        ->first();

                    $dataReturBeli = ReturBeliHeader::where('pembelianid', $fetchHpp->penerimaanid)->where('flag', 'generated')->first();

                    $penjualanDetailId = $row['penjualandetailid'][$i];
                    $returJualDetail = DB::table('returjualdetail')
                        ->select('*')
                        ->where('penjualandetailid', $penjualanDetailId)
                        ->first();

                    //CREATE / UPDATE RETUR BELI
                    if ($dataReturBeli) {
                        $getReturDetail = DB::table("returbelidetail")->where('pembeliandetailid', $fetchHpp->penerimaandetailid)->first();

                        if ($getReturDetail) {
                            $productid = $row['productid'][$i];
                            $updateQty = $totals[$productid] ?? 0;

                            $returDetail = [
                                'pembelianid' => $fetchHpp->penerimaanid,
                                'nobuktirb' => $dataReturBeli->nobukti,
                                'id' => $getReturDetail->id ?? 0,
                                'pembeliandetailid' => $fetchHpp->penerimaandetailid,
                                'productid' => $row['productid'][$i],
                                'pesananfinaldetailid' => $row['pesananfinaldetailid'][$i] ?? 0,
                                'keterangan' => $row['keterangandetail'][$i] ?? '',
                                'qtyreturbeli' => $updateQty ?? 0,
                                'satuanid' => $row['satuanid'][$i] ?? 0,
                                'harga' => $row['hargabeli'][$i] ?? 0,
                                'modifiedby' => auth('api')->user()->id,
                                'returjualdetailid' => $returJualDetail->id
                            ];
                            $totalRetur += $returDetail['harga'] * $returDetail['qtyreturbeli'];
                        } else {
                            $returDetail = [
                                'pembelianid' => $fetchHpp->penerimaanid,
                                'nobuktirb' => $dataReturBeli->nobukti,
                                'id' => 0,
                                'pembeliandetailid' => $fetchHpp->penerimaandetailid,
                                'productid' => $row['productid'][$i],
                                'pesananfinaldetailid' => $row['pesananfinaldetailid'][$i] ?? 0,
                                'keterangan' => $row['keterangandetail'][$i] ?? '',
                                'qtyreturbeli' => $row['qtyreturbeli'][$i] ?? 0,
                                'satuanid' => $row['satuanid'][$i] ?? 0,
                                'harga' => $row['hargabeli'][$i] ?? 0,
                                'modifiedby' => auth('api')->user()->id,
                                'returjualdetailid' => $returJualDetail->id
                            ];
                            $totalRetur += $returDetail['harga'] * $returDetail['qtyreturbeli'];
                        }
                        $allReturDetails['withRetur'][] = $returDetail;
                    } else {
                        $returDetail = [
                            'pembelianid' => $fetchHpp->penerimaanid,
                            'pembeliandetailid' => $fetchHpp->penerimaandetailid,
                            'productid' => $row['productid'][$i],
                            'pesananfinaldetailid' => $row['pesananfinaldetailid'][$i] ?? 0,
                            'keterangan' => $row['keterangandetail'][$i] ?? '',
                            'qtyreturbeli' => $row['qtyreturbeli'][$i] ?? 0,
                            'satuanid' => $row['satuanid'][$i] ?? 0,
                            'harga' => $row['hargabeli'][$i] ?? 0,
                            'modifiedby' => auth('api')->user()->id,
                            'returjualdetailid' => $returJualDetail->id
                        ];
                        $allReturDetails['withoutRetur'][] = $returDetail;
                        $totalRetur += $returDetail['harga'] * $returDetail['qtyreturbeli'];
                    }

                    if ($dataReturBeli) {
                        $withRetur[] = $returDetail;
                    } else {
                        $withoutRetur[] = $returDetail;
                    }
                }

                $allReturDetails['withRetur'] = $withRetur;
                $allReturDetails['withoutRetur'] = $withoutRetur;

                //RETUR EXIST
                if (!empty($allReturDetails['withRetur'])) {
                    $returHeaders = [];
                    $totalAll = 0;
                    foreach ($allReturDetails['withRetur'] as $detail) {
                        $pembelianId = $detail['pembelianid'];
                        if (!isset($returHeaders[$pembelianId])) {
                            $pembelianHeader = DB::table('pembelianheader')
                                ->select('*')
                                ->where('id', $detail['pembelianid'])
                                ->first();
                            $returHeaders[$detail['pembelianid']] = [
                                'tglbukti' =>  now(),
                                'pembelianid' => $pembelianId,
                                'pembeliannobukti' => $pembelianHeader->nobukti,
                                'supplierid' => $pembelianHeader->supplierid,
                                'total' => $totalRetur,
                                'flag' => 'generated',
                                'details' => [],
                            ];
                        }

                        $returHeaders[$pembelianId]['details'][] = [
                            'id' => $detail['id'],
                            'pesananfinaldetailid' => $detail['pesananfinaldetailid'],
                            'pembeliandetailid' => $detail['pembeliandetailid'],
                            'productid' => $detail['productid'],
                            'satuanid' => $detail['satuanid'],
                            'keterangan' => $detail['keterangan'],
                            'qty' => $detail['qtyreturbeli'],
                            'harga' => $detail['harga'],
                            'modifiedby' => $detail['modifiedby']
                        ];

                        $totalAll +=  $detail['qtyreturbeli'] * $detail['harga'];

                        $resultWithRetur = [];
                        foreach ($returHeaders as $returHeader) {
                            $details = array_column($returHeader['details'], null);
                            unset($returHeader['details']);
                            $resultWithRetur[] = array_merge($returHeader, [
                                'id' => array_column($details, 'id'),
                                'pesananfinaldetailid' => array_column($details, 'pesananfinaldetailid'),
                                'pembeliandetailid' => array_column($details, 'pembeliandetailid'),
                                'returjualdetailid' => array_column($details, 'returjualdetailid'),
                                'productid' => array_column($details, 'productid'),
                                'satuanid' => array_column($details, 'satuanid'),
                                'keterangan' => array_column($details, 'keterangan'),
                                'qty' => array_column($details, 'qty'),
                                'harga' => array_column($details, 'harga'),
                                'modifiedby' => array_column($details, 'modifiedby')
                            ]);
                        }
                    }

                    foreach ($resultWithRetur as $result) {
                        $dataRetur = ReturBeliHeader::where('pembelianid', $result['pembelianid'])->where('flag', 'generated')->first();

                        if ($dataRetur != '') {
                            $returBeli = new ReturBeliHeader();
                            $returBeli = $returBeli->find($dataRetur->id);
                            $result['total'] = $totalAll;
                            $returBeli = (new ReturBeliHeader())->processUpdate($dataRetur, $result);
                        }
                    }
                }

                //RETUR NOT EXIST
                if (!empty($allReturDetails['withoutRetur'])) {
                    $returHeaders = [];
                    foreach ($allReturDetails['withoutRetur'] as $detail) {
                        $pembelianHeader = DB::table('pembelianheader')
                            ->select('*')
                            ->where('id', $detail['pembelianid'])
                            ->first();
                        $returHeaders[$detail['pembelianid']] = [
                            'tglbukti' =>  now(),
                            'pembelianid' => $pembelianHeader->id,
                            'pembeliannobukti' => $pembelianHeader->nobukti,
                            'supplierid' => $pembelianHeader->supplierid,
                            'total' => $detail['qtyreturbeli'] *  $detail['harga'],
                            'flag' => 'generated',
                        ];
                    }
                    foreach ($allReturDetails['withoutRetur'] as $detail) {
                        $returHeader = $returHeaders[$detail['pembelianid']];

                        $resultWithoutRetur = array_merge($returHeader, [
                            'pesananfinaldetailid' => [$detail['pesananfinaldetailid']],
                            'pembeliandetailid' => [$detail['pembeliandetailid']],
                            'returjualdetailid' => [$detail['returjualdetailid']],
                            'productid' => [$detail['productid']],
                            'satuanid' => [$detail['satuanid']],
                            'keterangan' => [$detail['keterangan']],
                            'qty' => [$detail['qtyreturbeli']],
                            'harga' => [$detail['harga']],
                            'modifiedby' => [$detail['modifiedby']]
                        ]);
                        // dd($resultWithoutRetur);
                        $returBeli = (new ReturBeliHeader())->processStore($resultWithoutRetur);
                        // dd($returBeli);
                        // 
                    }
                    // dump($returBeli);
                }
            }

            $valReturHeader = DB::table('returjualheader')
                ->select('*')
                ->whereIn('penjualanid', $dataPenjualans['idheader[]'])
                ->where('flag', 'generated')
                ->get();

            $retursNew = [];
            foreach ($data as $row) {
                $retursNew[] = $row['penjualanid'];
            }

            $returs = $valReturHeader->pluck('penjualanid')->toArray();
            $difference = array_diff($returs, $retursNew);
            if ($difference) {
                foreach ($difference as $diff) {
                    $returHeader = DB::table('returjualheader')
                        ->select('*')
                        ->where('penjualanid', $diff)
                        ->where('flag', 'generated')
                        ->first();

                    $returJual = (new ReturJualHeader())->processDestroy($returHeader->id, "DELETE RETUR JUAL HEADER");
                }
            }
        } else {
            $dataReturJual = DB::table('returjualheader')
                ->select('*')
                ->whereIn('penjualanid', $dataPenjualans['idheader[]'])
                ->where('flag', 'generated')
                ->get();

            if ($dataReturJual) {
                foreach ($dataReturJual as $value) {
                    $dataReturJualDetail = DB::table('returjualdetail')
                        ->select('*')
                        ->where('returjualid', $value->id)
                        ->get();

                    $result = [
                        'tglbukti' => $value->tglbukti,
                        'penjualanid' => $value->penjualanid,
                        // 'penjualannobukti' => $penjualanHeader->nobukti,
                        'returjualnobukti' => $value->nobukti,
                        'customerid' => $value->customerid,
                        'total' => $value->total,
                        'flag' => $value->flag,
                        'penjualandetailid' => [],
                        'productid' => [],
                        'satuanid' => [],
                        'keterangan' => [],
                        'qty' => [],
                        'harga' => [],
                        'modifiedby' => [],
                    ];

                    foreach ($dataReturJualDetail as $detail) {
                        $result['penjualandetailid'][] = $detail->penjualandetailid;
                        $result['returjualdetailid'][] = $detail->id;
                        $result['productid'][] = $detail->productid;
                        $result['satuanid'][] = $detail->satuanid;
                        $result['keterangan'][] = $detail->keterangan;
                        $result['qty'][] = $detail->qty;
                        $result['harga'][] = $detail->harga;
                        $result['modifiedby'][] = $detail->modifiedby;
                    }

                    for ($i = 0; $i < count($result['returjualdetailid']); $i++) {
                        $fetchHpp = DB::table('hpp')
                            ->select('*')
                            ->where('pengeluarandetailid', $result['penjualandetailid'][$i])
                            ->first();

                        $dataReturBeli = ReturBeliHeader::where('pembelianid', $fetchHpp->penerimaanid)->where('flag', 'generated')->first();

                        $dataReturBeliDetail = DB::table('returbelidetail')
                            ->select('*', 'returbelidetail.id as returbelidetailid')
                            ->leftJoin('returbeliheader', 'returbelidetail.returbeliid', 'returbeliheader.id')
                            ->where('returbeliid', $dataReturBeli->id)
                            ->get();

                        foreach ($dataReturBeliDetail as $valuebeli) {
                            if ($valuebeli->returjualdetailid === $result['returjualdetailid'][$i]) {

                                //DELETE RETUR BELI DETAIL
                                $details = ReturBeliDetail::where('id', $valuebeli->returbelidetailid)->first();
                                if ($details) {
                                    $details->delete();
                                }

                                //UPDATE QTY RETUR PEMBELIAN DETAIL
                                $pembelianDetails =  PembelianDetail::where('id', $valuebeli->pembeliandetailid)->first();
                                if ($pembelianDetails) {
                                    $pembelianDetails->qtyretur = 0;
                                    $pembelianDetails->save();
                                }
                            }
                        }

                        $fetchReturBeli = ReturBeliDetail::where('returbeliid', $dataReturBeli->id)->first();
                        if ($fetchReturBeli === null) {
                            $returBeli = (new ReturBeliHeader())->processDestroy($dataReturBeli->id, "DELETE RETUR BELI HEADER");
                        } else {
                            //UPDATE TOTAL RETUR BELI HEADER
                            $total = 0;
                            $detail = DB::table('returbelidetail')
                                ->select('*')
                                ->where('returbeliid', $dataReturBeli->id)
                                ->get();

                            foreach ($detail as $item) {
                                $total += $item->qty * $item->harga;
                            }
                            $dataReturBeli->total = $total;
                            if (!$dataReturBeli->save()) {
                                throw new \Exception("Error updating Retur Beli Header.");
                            }

                            //UPDATE NOMINAL PIUTANG
                            $piutang = DB::table('piutang')
                                ->where('penjualanid', $dataReturBeli->id)
                                ->update([
                                    'nominalpiutang' => $dataReturBeli->total,
                                    'nominalsisa' => $dataReturBeli->total,
                                    'updated_at' => $dataReturBeli->updated_at,
                                ]);
                        }
                    }
                    //DELETE RETUR JUAL HEADER
                    $returJual = (new ReturJualHeader())->processDestroy($value->id, "DELETE RETUR JUAL HEADER");
                }
            }
        }

        // $test = DB::table('returbeliheader as a')
        //     ->select('*')
        //     ->leftJoin('returbelidetail as b', 'a.id', 'b.returbeliid')
        //     ->get();
        // dd($test);

        //  $test = DB::table('pembeliandetail')
        //     ->select('*')
        //     ->leftJoin('pembelianheader', 'pembeliandetail.pembelianid', 'pembelianheader.id')
        //     ->where('pembelianheader.tglbukti', "2024-04-22")
        //     ->get();

        // dd($test);

        return $penjualanHeader;
    }

    public function getSumQty()
    {
        $returDetail = DB::table('returjualdetail')
            ->select(
                'returjualdetail.productid',
                DB::raw('SUM(returjualdetail.qty) as totalqtyretur')
            )
            ->where('returjualdetail.penjualandetailid', request()->iddetail)
            ->whereNotIn('returjualdetail.returjualid', function ($query) {
                $query->select('id')
                    ->from('returjualheader')
                    ->where('flag', '=', 'GENERATED');
            })
            ->groupBy('returjualdetail.productid')
            ->first();

        $retur = $returDetail ?? (object) ['productid' => 0, 'totalqtyretur' => 0];

        return $retur;
    }


    public function disabledQtyRetur($id)
    {
        $query = DB::table("penjualanheader")
            ->select(
                "pesananfinaldetail.id",
                "pesananfinaldetail.nobuktipembelian",

            )
            ->join('pesananfinalheader', 'pesananfinalheader.id', 'penjualanheader.pesananfinalid')
            ->join('pesananfinaldetail', 'pesananfinalheader.id', 'pesananfinaldetail.pesananfinalid')
            ->where("penjualanheader.id", $id);


        $getData = $query->get()->toArray();



        return $getData;
    }

    public function disabledQtyReturEditALl($data)
    {
        $tglpengiriman = date('Y-m-d', strtotime(request()->date));

        foreach ($data as $penjualan) {
            foreach ($penjualan['details'] as $detail) {
                $query = DB::table("penjualanheader")
                    ->select(
                        "pesananfinaldetail.id",
                        "penjualanheader.id as penjualanid",
                        "pesananfinaldetail.nobuktipembelian",
                    )
                    ->join('pesananfinalheader', 'pesananfinalheader.id', 'penjualanheader.pesananfinalid')
                    ->join('pesananfinaldetail', 'pesananfinalheader.id', 'pesananfinaldetail.pesananfinalid')
                    ->where('penjualanheader.tglpengiriman', '=', $tglpengiriman)
                    ->where('penjualanheader.pesananfinalid', '!=', 0);
            }
        }
        // die;

        $getData = $query->get()->toArray();



        return $getData;
    }

    public function getInvoice($id)
    {
        $this->setRequestParameters();

        $query = DB::table($this->table . ' as penjualanheader')
            ->select(
                "penjualanheader.id",
                "penjualanheader.nobukti",
                "penjualanheader.tglbukti",
                "customer.id as customerid",
                "customer.nama as customernama",
                "customer.telepon as customertelp",
                "penjualanheader.alamatpengiriman",
                "penjualanheader.tglpengiriman",
                "penjualanheader.keterangan",
                DB::raw('IFNULL(penjualanheader.servicetax, 0) AS servicetax'),
                DB::raw('IFNULL(penjualanheader.tax, 0) AS tax'),
                DB::raw('IFNULL(penjualanheader.taxamount, 0) AS taxamount'),
                DB::raw('IFNULL(penjualanheader.discount, 0) AS discount'),
                DB::raw('IFNULL(penjualanheader.subtotal, 0) AS subtotal'),
                DB::raw('IFNULL(penjualanheader.total, 0) AS total'),
                DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(penjualanheader.nobukti, '/', -1), '/', 1) as nobuktinew"),
                "penjualanheader.discount",
                "penjualanheader.tglcetak",
                "pesananfinalheader.id as pesananfinalid",
                "pesananfinalheader.nobukti as pesananfinalnobukti",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'penjualanheader.created_at',
                'penjualanheader.updated_at'
            )
            ->where("$this->table.id", $id)
            ->leftJoin(DB::raw("customer"), 'penjualanheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'penjualanheader.status', 'parameter.id')
            ->leftJoin(DB::raw("pesananfinalheader"), 'penjualanheader.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'penjualanheader.modifiedby', 'modifier.id');

        if (request()->tgldariheader && request()->tglsampaiheader) {
            $query->whereBetween($this->table . '.tglpengiriman', [date('Y-m-d', strtotime(request()->tgldariheader)), date('Y-m-d', strtotime(request()->tglsampaiheader))]);
        }

        $data = $query->first();
        return $data;
    }

    public function getReportProfit()
    {
        // dd('tests');
        $dari = date('Y-m-d', strtotime(request()->dari));
        $sampai = date('Y-m-d', strtotime(request()->sampai));

        // dd($dari, $sampai);
        $penjualanHeader = DB::table('penjualanheader')
            ->select('*', 'penjualanheader.id as id')
            ->whereBetween('tglpengiriman', [$dari, $sampai])
            ->leftJoin('customer', 'penjualanheader.customerid', 'customer.id')
            ->get();

        // dd($penjualanHeader);

        $results = [];
        $sumProfit = 0;
        $sumPengeluaran = 0;
        $sumPenerimaan = 0;
        foreach ($penjualanHeader as $penjualan) {

            // dd($penjualan);
            $hpp = DB::table('hpp')
                ->select('*')
                ->where('pengeluaranid', $penjualan->id)
                ->get();

            // dd($hpp);
            $totalpengeluaran = $hpp->sum('pengeluarantotal');
            $totalpenerimaan = $hpp->sum('penerimaantotal');
            $totalprofit = $totalpengeluaran - $totalpenerimaan;

            $totalPenjualan = $penjualan->total;
            $presentasiProfit = round(($totalprofit/$totalPenjualan)*100);


            $sumProfit += $totalprofit;
            $sumPengeluaran += $totalPenjualan;
            $sumPenerimaan += $totalpenerimaan;

            $presentasiProfitKeseluruhan = round(($sumProfit/$sumPengeluaran)*100);


            $results[] = [
                
                'dari' => $dari,
                'sampai' => $sampai,
                'pengeluaranid' => $penjualan->id,
                'pengeluarannobukti' => $penjualan->nobukti,
                'pengeluarantglbukti' => $penjualan->tglbukti,
                'customerid' => $penjualan->customerid,
                'customernama' => $penjualan->nama,
                'totalpengeluaran' => $totalPenjualan,
                'totalpenerimaan' => $totalpenerimaan,
                'totalprofit' => $totalprofit,
                'presentasiprofit' => $presentasiProfit,
                'presentasikeseluruhan' => $presentasiProfitKeseluruhan,
            ];
          
        }

     
        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'data' => $results
        ];
    }

    public function getReportProfitDetail()
    {
        // dd('tests');
        $dari = date('Y-m-d', strtotime(request()->dari));
        $sampai = date('Y-m-d', strtotime(request()->sampai));

        // dd($dari, $sampai);
        $penjualanHeader = DB::table('penjualanheader')
            ->select('*', 'penjualanheader.id as id')
            ->whereBetween('tglpengiriman', [$dari, $sampai])
            ->leftJoin('customer', 'penjualanheader.customerid', 'customer.id')
            ->get();



        $results = [];

        foreach ($penjualanHeader as $penjualan) {
            $penjualanDetail = new PenjualanDetail();

            $dataDetail = DB::table('penjualandetail')
                ->select(
                    "penjualandetail.id",
                    "penjualanheader.id as penjualanid",
                    "penjualanheader.nobukti as nobuktipesananfinal",
                    "product.id as productid",
                    "product.nama as productnama",
                    "penjualandetail.pesananfinaldetailid",
                    "penjualandetail.keterangan as keterangandetail",
                    "penjualandetail.qty",
                    "penjualandetail.qtyreturjual",
                    "penjualandetail.harga",
                    DB::raw('IFNULL(hpp.pengeluaranharga, 0) AS pengeluaran'),
                    DB::raw('IFNULL(hpp.penerimaanharga, 0) AS penerimaan'),
                    DB::raw('IFNULL(hpp.penerimaantotal, 0) AS penerimaantotal'),
                    DB::raw('IFNULL(hpp.pengeluarantotal, 0) AS pengeluarantotal'),
                    "hpp.pengeluarantotal as pengeluarantotal",
                    DB::raw('(IFNULL(hpp.pengeluarantotal, 0) - IFNULL(hpp.penerimaantotal, 0)) AS profitdetail'),
                    DB::raw('(IFNULL(penjualandetail.qty, 0) * IFNULL(penjualandetail.harga, 0)) AS totalharga'),
                    "satuan.nama as satuannama",
                    "satuan.id as satuanid",
                    "modifier.id as modified_by_id",
                    "modifier.name as modified_by",
                    "penjualandetail.created_at",
                    "penjualandetail.updated_at",
                )
                ->leftJoin(DB::raw("penjualanheader"), 'penjualandetail.penjualanid', 'penjualanheader.id')
                ->leftJoin(DB::raw("product"), 'penjualandetail.productid', 'product.id')
                ->leftJoin(DB::raw("satuan"), 'penjualandetail.satuanid', 'satuan.id')
                ->leftJoin(DB::raw("hpp"), 'penjualandetail.id', 'hpp.pengeluarandetailid')
                ->leftJoin(DB::raw("user as modifier"), 'penjualandetail.modifiedby', 'modifier.id')
                ->orderBy('product.nama', 'asc')
                ->where('penjualanid', '=', $penjualan->id);

         
            // dump($dataDetail);
            $hpp = DB::table('hpp')
                ->select('*')
                ->where('pengeluaranid', $penjualan->id)
                ->get();

            if (count($hpp)) {
                $dataDetail->where('hpp.flag', '=', 'PJ');
            }

            $dataDetail = $dataDetail->get();

            $totalpengeluaran = $hpp->sum('pengeluarantotal');
            $totalpenerimaan = $hpp->sum('penerimaantotal');
            $totalprofit = $totalpengeluaran - $totalpenerimaan;

            $results[] = [
                'dari' => $dari,
                'sampai' => $sampai,
                'pengeluaranid' => $penjualan->id,
                'pengeluarannobukti' => $penjualan->nobukti,
                'pengeluarantglbukti' => $penjualan->tglbukti,
                'customerid' => $penjualan->customerid,
                'customernama' => $penjualan->nama,
                'totalpengeluaran' => $totalpengeluaran,
                'totalpenerimaan' => $totalpenerimaan,
                'totalprofit' => $totalprofit,
                'details' => $dataDetail
            ];
        }
        // die;


        // dd('test');
      
        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'data' => $results
        ];
    }
}
