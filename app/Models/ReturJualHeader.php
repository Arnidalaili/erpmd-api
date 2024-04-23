<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\RunningNumberService;
use Illuminate\Database\Console\DumpCommand;
use Illuminate\Support\Facades\DB;


class ReturJualHeader extends MyModel
{
    use HasFactory;

    protected $table = 'returjualheader';

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
        $query = DB::table($this->table . ' as returjualheader')
            ->select(
                "returjualheader.id",
                "returjualheader.nobukti",
                "returjualheader.tglbukti",
                "returjualheader.penjualanid",
                "penjualanheader.nobukti as penjualannobukti",
                "returjualheader.customerid",
                "customer.nama as customernama",
                "returjualheader.keterangan",
                "returjualheader.total",
                "returjualheader.tglcetak",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'returjualheader.created_at',
                'returjualheader.updated_at'
            )
            ->leftJoin(DB::raw("customer"), 'returjualheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'returjualheader.status', 'parameter.id')
            ->leftJoin(DB::raw("penjualanheader"), 'returjualheader.penjualanid', 'penjualanheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'returjualheader.modifiedby', 'modifier.id');

        if (request()->tgldari && request()->tglsampai) {
            $query->whereBetween($this->table . '.tglbukti', [date('Y-m-d', strtotime(request()->tgldari)), date('Y-m-d', strtotime(request()->tglsampai))]);
        }

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);

        $data = $query->get();

        // dd($data);
        return $data;
    }

    public function findAll($id)
    {
        $query = DB::table('returjualheader')
            ->select(
                "returjualheader.id",
                "returjualheader.nobukti",
                "returjualheader.tglbukti",
                "returjualheader.penjualanid",
                "penjualanheader.nobukti as penjualannobukti",
                "returjualheader.customerid",
                "customer.nama as customernama",
                "returjualheader.keterangan",
                "returjualheader.total",
                "returjualheader.tglcetak",
                "parameter.id as status",
                "parameter.text as statusnama",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'returjualheader.created_at',
                'returjualheader.updated_at'
            )
            ->leftJoin(DB::raw("customer"), 'returjualheader.customerid', 'customer.id')
            ->leftJoin(DB::raw("parameter"), 'returjualheader.status', 'parameter.id')
            ->leftJoin(DB::raw("penjualanheader"), 'returjualheader.penjualanid', 'penjualanheader.id')
            ->leftJoin(DB::raw("user as modifier"), 'returjualheader.modifiedby', 'modifier.id')
            ->where('returjualheader.id', $id);
        $data = $query->first();

        // dd($data);
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('returjualheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returjualheader.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(returjualheader.nobukti, '/', 1)"), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'penjualannobukti') {
            return $query->orderBy('penjualanheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'customernama') {
            return $query->orderBy('customer.nama', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'penjualannobukti') {
                            $query = $query->where('penjualanheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'customernama') {
                            $query = $query->where('customer.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    $query = $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'penjualannobukti') {
                                $query = $query->orWhere('penjualanheader.nobukti', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'customernama') {
                                $query = $query->orWhere('customer.nama', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'statusmemo') {
                                $query = $query->orWhere('parameter.memo', 'LIKE', "%$filters[data]%");
                            } else if ($filters['field'] == 'modifiedby_name') {
                                $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                            } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else {
                                $query = $query->OrwhereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                            }
                        }
                    });
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
            $this->table.penjualanid,
            penjualanheader.nobukti as penjualannobukti,
            $this->table.customerid,
            customer.nama as customernama,
            $this->table.keterangan,
            $this->table.total,
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
            ->leftJoin(DB::raw("parameter"), 'returjualheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'returjualheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("penjualanheader"), 'returjualheader.penjualanid', 'penjualanheader.id')
            ->leftJoin(DB::raw("customer"), 'returjualheader.customerid', 'customer.id');
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
            penjualanid INT,
            penjualannobukti VARCHAR(100),
            customerid INT,
            customernama VARCHAR(100),
            keterangan VARCHAR(500),
            total FLOAT,
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
            "id", "tglbukti", "nobukti", "penjualanid", "penjualannobukti", "customerid", "customernama",
            "keterangan", "total", "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
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

    public function editingAt($id, $btn)
    {
        $returJualHeader = ReturJualHeader::find($id);
        if ($btn == 'EDIT') {
            $returJualHeader->editingby = auth('api')->user()->name;
            $returJualHeader->editingat = date('Y-m-d H:i:s');
        } else {

            if ($returJualHeader->editingby == auth('api')->user()->name) {
                $returJualHeader->editingby = '';
                $returJualHeader->editingat = null;
            }
        }
        if (!$returJualHeader->save()) {
            throw new \Exception("Error Update retur jual header.");
        }

        return $returJualHeader;
    }

    public function processData($data)
    {
        $satuanIds = [];
        $ids = [];
        $qtys = [];
        $keteranganDetails = [];
        $hargas = [];
        $penjualanDetails = [];
        $pesananFinalDetails = [];

        foreach ($data as $detail) {
            $productIds = request()->productid;
            $satuanIds[] = $detail['satuanid'];
            $qtys[] = $detail['qty'];
            $ids[] = $detail['id'];
            $keteranganDetails[] = $detail['keterangandetail'];
            $hargas[] = $detail['harga'];
            $penjualanDetails[] = $detail['penjualandetailid'] ?? 0;
            $pesananFinalDetails[] = $detail['pesananfinaldetailid'] ?? 0;
        }

        $data = [
            "tglbukti" => request()->tglbukti,
            "penjualanid" => request()->penjualanid,
            "customerid" => request()->customerid,
            "keterangan" => request()->keterangan,
            "total" => request()->total,
            "status" => request()->status ?? 1,
            "productid" =>  $productIds,
            "satuanid" => $satuanIds,
            "qty" => $qtys,
            "id" => $ids,
            "keterangandetail" => $keteranganDetails,
            "harga" => $hargas,
            "penjualandetailid" => $penjualanDetails,
            "pesananfinaldetailid" => $pesananFinalDetails
        ];
        // dd($data);
        return $data;
    }

    public function getPenjualanDetail($penjualanid)
    {
        $query = DB::table('penjualandetail')->from(DB::raw("penjualandetail"))
            ->select(
                'penjualandetail.productid as id',
                'product.nama as productnama',
                'penjualandetail.satuanid',
                'satuan.nama as satuannama',
                'penjualandetail.qtyretur as qtysdhretur',
                'penjualandetail.qty as qtypesanan',
                'penjualandetail.harga',
                'penjualandetail.id as penjualandetailid',
                'penjualandetail.pesananfinaldetailid',
            )
            ->leftJoin('satuan', 'penjualandetail.satuanid', 'satuan.id')
            ->leftJoin('product', 'penjualandetail.productid', 'product.id')
            ->where('penjualanid', $penjualanid);

        $data = $query->get();
        // dd($data);

        return $data;
    }

    public function getEditPenjualanDetail($penjualanid, $id, $penjualandetailid)
    {
        $tempDetail = $this->createTempPenjualanDetail($penjualanid, $id, $penjualandetailid);
        $tempRetur = $this->createTempReturDetail($id, $penjualandetailid);

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            returjualid INT UNSIGNED,
            returjualnobukti VARCHAR(100),
            productid INT,
            productnama VARCHAR(100),
            keterangandetail VARCHAR(250),
            qty FLOAT, 
            qtypesanan FLOAT,
            qtysdhretur FLOAT,
            harga FLOAT, 
            totalharga FLOAT,
            satuanid INT,
            satuannama VARCHAR(100),
            penjualandetailid INT UNSIGNED,
            pesananfinaldetailid INT UNSIGNED
        )");

        $retur = DB::table($tempRetur)
            ->select(
                DB::raw("id,returjualid, returjualnobukti, productid, productnama, keterangandetail, qty, qtypesanan, qtysdhretur, harga, totalharga, satuanid, satuannama, penjualandetailid, pesananfinaldetailid")
            );

        // dd($retur->get());

        DB::table($temp)->insertUsing([
            "id", "returjualid", "returjualnobukti", "productid", "productnama", "keterangandetaiL", "qty", "qtypesanan", "qtysdhretur", "harga", "totalharga", "satuanid", "satuannama", "penjualandetailid", "pesananfinaldetailid"
        ], $retur);

        // dd(DB::table($temp)->get());

        $penjualans = DB::table("$tempDetail as a")
            ->select(
                DB::raw("a.id,0 as returjualid, null as returjualnobukti, a.productid, a.productnama, null as keterangandetail, 0 as qty, a.qtypesanan, a.qtysdhretur, a.harga, null as totalharga, a.satuanid, a.satuannama, a.penjualandetailid, a.pesananfinaldetailid ")
            )
            ->leftJoin(DB::raw("$tempRetur as b"), "a.id", "b.productid");

        // dd($penjualans->get());

        DB::table($temp)->insertUsing([
            "id", "returjualid", "returjualnobukti", "productid", "productnama", "keterangandetail", "qty", "qtypesanan", "qtysdhretur", "harga", "totalharga", "satuanid", "satuannama", "penjualandetailid", "pesananfinaldetailid"
        ], $penjualans);
        // dd(DB::table($temp)->get());

        $data = DB::table($temp)
            ->select(DB::raw("$temp.id as id,$temp.returjualid as returjualid, $temp.id as productid,returjualnobukti, productid, productnama, keterangandetail, qty, qtypesanan, qtysdhretur, harga, totalharga, satuanid, satuannama, penjualandetailid, pesananfinaldetailid"))
            ->get();

        // dd($data);

        return $data;
    }

    public function createTempReturDetail($id)
    {
        $tempo = 'tempRetur' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempo (
            id BIGINT,
            returjualid INT UNSIGNED,
            returjualnobukti VARCHAR(100),
            productid INT,
            productnama VARCHAR(100),
            keterangandetail VARCHAR(100),
            qty FLOAT, 
            harga FLOAT, 
            totalharga FLOAT,
            satuanid INT,
            satuannama VARCHAR(100),
            qtypesanan INT,
            qtysdhretur INT,
            penjualandetailid INT UNSIGNED, 
            pesananfinaldetailid INT UNSIGNED
        )");

        // dd($id);
        $fetch = DB::table('returjualdetail')
            ->select(
                "returjualdetail.id",
                "returjualdetail.id as returjualid",
                "returjualheader.nobukti as returjualnobukti",
                "returjualdetail.productid",
                "product.nama as productnama",
                "returjualdetail.keterangan as keterangandetail",
                "returjualdetail.qty",
                "returjualdetail.harga",
                DB::raw('(returjualdetail.qty * returjualdetail.harga) AS totalharga'),
                "returjualdetail.satuanid",
                "satuan.nama as satuannama",
                'penjualandetail.qty as qtypesanan',
                'penjualandetail.qtyretur as qtysdhretur',
                'returjualdetail.penjualandetailid as penjualandetailid',
                'penjualandetail.pesananfinaldetailid'
            )
            ->leftJoin(DB::raw("returjualheader"), 'returjualdetail.returjualid', 'returjualheader.id')
            ->leftJoin(DB::raw("product"), 'returjualdetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'returjualdetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("penjualandetail"), 'returjualdetail.penjualandetailid', 'penjualandetail.id')
            ->where('returjualid', '=', $id);
        // dd($fetch->get());

        DB::table($tempo)->insertUsing([
            "id", "returjualid", "returjualnobukti", "productid", "productnama", "keterangandetail", "qty", "harga",
            "totalharga", "satuanid", "satuannama", "qtypesanan", "qtysdhretur", "penjualandetailid", "pesananfinaldetailid",
        ], $fetch);

        // dd(DB::table($tempo)->get());

        return $tempo;
    }

    public function createTempPenjualanDetail($penjualanid, $id, $penjualandetailid)
    {
        $tempGetPenjualan = 'tempgetpenjualan' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempGetPenjualan (
            id INT UNSIGNED,
            productid INT UNSIGNED,
            productnama VARCHAR(100),
            satuanid INT UNSIGNED,
            satuannama VARCHAR(100),
            qtysdhretur INT,
            qtypesanan INT, 
            harga FLOAT,
            penjualanid INT UNSIGNED,
            penjualandetailid INT UNSIGNED,
            pesananfinaldetailid INT UNSIGNED
        )");

        $fetch = DB::table('penjualandetail')->from(DB::raw("penjualandetail"))
            ->select(
                'penjualandetail.productid as id',
                'penjualandetail.productid as productid',
                'product.nama as productnama',
                'penjualandetail.satuanid',
                'satuan.nama as satuannama',
                'penjualandetail.qtyretur as qtysdhretur',
                'penjualandetail.qty as qtypesanan',
                'penjualandetail.harga',
                'penjualandetail.penjualanid',
                'penjualandetail.id as penjualandetailid',
                'penjualandetail.pesananfinaldetailid',
            )
            ->leftJoin('satuan', 'penjualandetail.satuanid', 'satuan.id')
            ->leftJoin('product', 'penjualandetail.productid', 'product.id')
            ->where('penjualanid', $penjualanid);

        // dd($fetch->first());

        DB::table($tempGetPenjualan)->insertUsing([
            "id", "productid", "productnama", "satuanid", "satuannama", "qtysdhretur", "qtypesanan", "harga",
            "penjualanid", "penjualandetailid", "pesananfinaldetailid",
        ], $fetch);

        // dd(DB::table($tempGetPenjualan)->get());

        $temp = 'tempDetails' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            productid INT UNSIGNED,
            productnama VARCHAR(100),
            satuanid INT UNSIGNED,
            satuannama VARCHAR(100),
            qtysdhretur INT, 
            qtypesanan INT, 
            harga FLOAT,
            penjualandetailid INT UNSIGNED, 
            pesananfinaldetailid INT UNSIGNED
        )");

        $tempRetur = $this->createTempReturDetail($id);
        // dd(DB::table($tempRetur)->get());

        $fetch = DB::table($tempGetPenjualan)->from(DB::raw("$tempGetPenjualan as a"))
            ->select(
                'a.id',
                'a.productid',
                'a.productnama',
                'a.satuanid',
                'a.satuannama',
                'a.qtysdhretur',
                'a.qtypesanan',
                'a.harga',
                'a.penjualandetailid',
                'a.pesananfinaldetailid'
            )
            ->leftJoin($tempRetur, 'a.penjualandetailid', "$tempRetur.penjualandetailid")
            ->whereNull("$tempRetur.penjualandetailid")
            ->whereNotIn('a.penjualandetailid', function ($query) use ($tempRetur) {
                $query->select('penjualandetailid')->from($tempRetur);
            });

        // dd($fetch->get());

        DB::table($temp)->insertUsing([
            "id", "productid", "productnama", "satuanid", "satuannama", "qtysdhretur", "qtypesanan", "harga",
            "penjualandetailid", "pesananfinaldetailid",
        ], $fetch);

        // dd(DB::table($temp)->get());

        return $temp;
    }

    public function processStore(array $data): ReturJualHeader
    {
        // dd($data);
        $returJualHeader = new ReturJualHeader();

        /*STORE HEADER*/
        $group = 'RETUR JUAL HEADER BUKTI';
        $subGroup = 'RETUR JUAL HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();
        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $returJualHeader->tglbukti = $tglbukti;
        $returJualHeader->penjualanid = $data['penjualanid'];
        $returJualHeader->customerid = $data['customerid'];
        $returJualHeader->total = $data['totaljual'];
        $returJualHeader->status = $data['status'] ?? 1;

        // dd(!isset($data['flag']) == 'generated');
        if (isset($data['flag']) == 'generated') {
            $returJualHeader->flag = 'generated';
        }
        $returJualHeader->modifiedby = auth('api')->user()->id;

        $returJualHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $returJualHeader->getTable(), date('Y-m-d', strtotime($data['tglbukti'])));

        if (!$returJualHeader->save()) {
            throw new \Exception("Error storing retur jual header.");
        }

        $returJualHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($returJualHeader->getTable()),
            'postingdari' => strtoupper('ENTRY RETUR JUAL HEADER'),
            'idtrans' => $returJualHeader->id,
            'nobuktitrans' => $returJualHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $returJualHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        $returJualDetails = [];

        for ($i = 0; $i < count($data['productid']); $i++) {
            $returJualDetail = (new ReturJualDetail())->processStore($returJualHeader, [
                'returjualid' => $returJualHeader->id,
                'penjualandetailid' => $data['penjualandetailid'][$i],
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
                'productid' => $data['productid'][$i],
                'keterangan' => $data['keterangan'][$i] ?? "",
                'qty' => $data['qtyreturjual'][$i],
                'satuanid' => $data['satuanid'][$i],
                'harga' => $data['hargajual'][$i],
                'modifiedby' => auth('api')->user()->id,
            ]);
            $returJualDetails[] = $returJualDetail->toArray();

            // dd($data);

            //CREATE KARTU STOK
            $kartuStok = (new KartuStok())->processStore([
                "tglbukti" => $tglbukti,
                "penerimaandetailid" => $returJualDetail->id,
                "pengeluarandetailid" => 0,
                'nobukti' => $returJualHeader->nobukti,
                'productid' => $data['productid'][$i],
                'qtypenerimaan' => $data['qtyreturjual'][$i],
                'totalpenerimaan' => $data['hargabeli'][$i] * $data['qtyreturjual'][$i],
                'qtypengeluaran' => 0,
                'totalpengeluaran' => 0,
                'flag' => 'RJ',
                'seqno' => 3
            ]);

            //UPDATE QTY TERPAKAI DI PEMBELIAN
            $hpps = DB::table('hpp')
                ->select(
                    'hpp.pengeluaranid',
                    'penjualanheader.nobukti as nobuktipenjualan',
                    'hpp.pengeluarandetailid',
                    'hpp.penerimaanid',
                    'pembelianheader.nobukti as nobuktipembelian',
                    'hpp.penerimaandetailid',
                    'hpp.pengeluaranqty',
                    'hpp.penerimaantotal',
                    'hpp.productid',
                )
                ->leftJoin('penjualanheader', 'hpp.pengeluaranid', 'penjualanheader.id')
                ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
                ->where('hpp.pengeluarandetailid', $data['penjualandetailid'][$i])
                ->orderBy('pembelianheader.nobukti', 'desc')
                ->first();

            // dd($hpps);

            $pembelian = DB::table('pembeliandetail')
                ->select(
                    'qty',
                    'qtyterpakai',
                    'productid'
                )
                ->where('id', $hpps->penerimaandetailid)
                ->first();

            if ($pembelian) {
                $qtyterpakai = $pembelian->qtyterpakai - $data['qtyreturjual'][$i];
                $pembelianDetail = PembelianDetail::where('id', $hpps->penerimaandetailid)->first();
                $pembelianDetail->qtyterpakai = $qtyterpakai;
                $pembelianDetail->save();
            }

            // dd($pembelianDetail);
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($returJualHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY RETUR JUAL DETAIL'),
            'idtrans' =>  $returJualHeaderLogTrail->id,
            'nobuktitrans' => $returJualHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $returJualDetails,
            'modifiedby' => auth('api')->user()->user,
        ]);
        // dd($data);

        //STORE HUTANG
        $tgljatuhtempo = date("Y-m-d", strtotime("+1 month", strtotime($tglbukti)));
        $dataHutang = [
            "tglbukti" => $tglbukti,
            "pembelianid" => $returJualHeader->id,
            "tglbuktipembelian" => $tglbukti,
            "tgljatuhtempo" => $tgljatuhtempo,
            "supplierid" => $data['customerid'],
            "keterangan" => '',
            "nominalhutang" => $data['totaljual'],
            "nominalsisa" => $data['totaljual'],
            "tglcetak" => '2023-11-11',
            "status" => $row['status'] ?? 1,
            "flag" => 'RJ'
        ];
        // dd($dataHutang);
        $hutang = (new Hutang())->processStore($dataHutang);

        // dd($returJualHeader);
        return $returJualHeader;
    }

    public function processUpdateOld(ReturJualHeader $returJualHeader, array $data): returJualHeader
    {
        $nobuktiOld = $returJualHeader->nobukti;

        $group = 'RETUR JUAL HEADER BUKTI';
        $subGroup = 'RETUR JUAL HEADER BUKTI';

        $returJualHeader->penjualanid = $data['penjualanid'];
        $returJualHeader->customerid = $data['customerid'];
        $returJualHeader->total = $data['total'];
        $returJualHeader->status = $data['status'] ?? 1;
        if (isset($data['flag']) == 'generated') {
            $returJualHeader->flag = 'generated';
        }
        $returJualHeader->modifiedby = auth('api')->user()->id;

        if (!$returJualHeader->save()) {
            throw new \Exception("Error updating Retur jual Header.");
        }

        $returJualHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($returJualHeader->getTable()),
            'postingdari' => strtoupper('ENTRY RETUR JUAL HEADER'),
            'idtrans' => $returJualHeader->id,
            'nobuktitrans' => $returJualHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $returJualHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        /*DELETE EXISTING PENJUALAN DETAIL*/
        $returJualDetail = ReturJualDetail::where('returjualid', $returJualHeader->id)->lockForUpdate()->delete();
        $returJualDetails = [];

        // dd($kartuStok);

        for ($i = 0; $i < count($data['productid']); $i++) {
            $returJualDetail = (new ReturJualDetail())->processStore($returJualHeader, [
                'returjualid' => $returJualHeader->id,
                'penjualandetailid' => $data['penjualandetailid'][$i],
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
                'productid' => $data['productid'][$i],
                'keterangan' => $data['keterangan'][$i] ?? "",
                'qty' => $data['qty'][$i],
                'satuanid' => $data['satuanid'][$i],
                'harga' => $data['harga'][$i],
                'modifiedby' => auth('api')->user()->id,
            ]);
            $returJualDetails[] = $returJualDetail->toArray();

            (new LogTrail())->processStore([
                'namatabel' => strtoupper($returJualHeaderLogTrail->getTable()),
                'postingdari' =>  strtoupper('ENTRY RETUR JUAL DETAIL'),
                'idtrans' =>  $returJualHeaderLogTrail->id,
                'nobuktitrans' => $returJualHeader->nobukti,
                'aksi' => 'ENTRY',
                'datajson' => $returJualDetails,
                'modifiedby' => auth('api')->user()->user,
            ]);

            $hpp = DB::table('hpp')
                ->select('penerimaanharga')
                ->where('pengeluarandetailid', $returJualDetail->penjualandetailid)
                ->first();

            //STORE KARTU STOK
            $kartuStok = (new KartuStok())->processStore([
                "tglbukti" => $returJualHeader->tglbukti,
                "penerimaandetailid" => $returJualDetail->id,
                "pengeluarandetailid" => 0,
                'nobukti' => $returJualHeader->nobukti,
                'productid' => $data['productid'][$i],
                'qtypenerimaan' => $data['qty'][$i],
                'totalpenerimaan' => $hpp->penerimaanharga * $data['qty'][$i],
                'qtypengeluaran' => 0,
                'totalpengeluaran' => 0,
                "flag" => 'RJ',
                "seqno" => 3
            ]);

            $tsc = DB::table('kartustok')
                ->select('*')
                ->where('productid', $data['productid'][$i])
                ->get();

            $qtySaldo = 0;
            $totalSaldo = 0;
            $usedQty = 0;

            foreach ($tsc as $transaction) {
                if ($transaction->flag == 'B' || $transaction->flag == 'RJ') {
                    $qtySaldo += $transaction->qtypenerimaan;
                    $totalSaldo += $transaction->totalpenerimaan;
                } else {
                    $qtySaldo -= $transaction->qtypengeluaran;
                    $totalSaldo -= $transaction->totalpengeluaran;
                }

                DB::table('kartustok')
                    ->where('id', $transaction->id)
                    ->update([
                        'qtysaldo' => $qtySaldo,
                        'totalsaldo' => $totalSaldo
                    ]);
            }

            // UPDATE QTY TERPAKAI DI PEMBELIAN
            $hpps = DB::table('hpp')
                ->select(
                    'hpp.pengeluaranid',
                    'penjualanheader.nobukti as nobuktipenjualan',
                    'hpp.pengeluarandetailid',
                    'hpp.penerimaanid',
                    'pembelianheader.nobukti as nobuktipembelian',
                    'hpp.penerimaandetailid',
                    'hpp.pengeluaranqty',
                    'hpp.penerimaantotal',
                    'hpp.productid',
                )
                ->leftJoin('penjualanheader', 'hpp.pengeluaranid', 'penjualanheader.id')
                ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
                ->where('hpp.pengeluarandetailid', $data['penjualandetailid'][$i])
                ->orderBy('pembelianheader.nobukti', 'desc')
                ->first();

            $pembelian = DB::table('pembeliandetail')
                ->select(
                    'qty',
                    'qtyterpakai',
                    'productid'
                )
                ->where('id', $hpps->penerimaandetailid)
                ->first();

            if ($pembelian) {
                $qtyterpakai = $pembelian->qtyterpakai + $data['qty'][$i];
                $pembelianDetail = PembelianDetail::where('id', $hpps->penerimaandetailid)->first();
                $pembelianDetail->qtyterpakai = $qtyterpakai;
                $pembelianDetail->save();
            }

            // $test = DB::table('kartustok')
            //     ->select('*')
            //     ->where('productid', $data['productid'][$i])
            //     // ->where('productid', 121)
            //     ->get();
            // $test2 = DB::table('pembeliandetail')
            //     ->select('*')
            //     ->where('productid', $data['productid'][$i])
            //     ->get();
        }

        return $returJualHeader;
    }

    public function processUpdate(ReturJualHeader $returJualHeader, array $data): returJualHeader
    {
        // dd($data);
        $tempDetail = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempDetail (
            id INT UNSIGNED NULL,
            returjualid INT NULL,
            productid INT NULL,
            satuanid INT NULL,
            penjualandetailid INT NULL,
            keterangan VARCHAR(500),
            qty FLOAT,
            harga FLOAT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        
        )");
        $nobuktiOld = $returJualHeader->nobukti;

        $group = 'RETUR JUAL HEADER BUKTI';
        $subGroup = 'RETUR JUAL HEADER BUKTI';

        // dd($data);

        $returJualHeader->penjualanid = $data['penjualanid'];
        $returJualHeader->customerid = $data['customerid'];
        $returJualHeader->total = $data['totaljual'];
        $returJualHeader->status = $data['status'] ?? 1;
        if (isset($data['flag']) == 'generated') {
            $returJualHeader->flag = 'generated';
        }
        $returJualHeader->modifiedby = auth('api')->user()->id;

        if (!$returJualHeader->save()) {
            throw new \Exception("Error updating Retur jual Header.");
        }

        $returJualHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($returJualHeader->getTable()),
            'postingdari' => strtoupper('ENTRY RETUR JUAL HEADER'),
            'idtrans' => $returJualHeader->id,
            'nobuktitrans' => $returJualHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $returJualHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        // dd($data);

        for ($i = 0; $i < count($data['productid']); $i++) {
            DB::table($tempDetail)->insert([
                'id' => $data['id'][$i],
                'returjualid' => $returJualHeader->id,
                'productid' => $data['productid'][$i],
                'satuanid' => $data['satuanid'][$i],
                'penjualandetailid' => $data['penjualandetailid'][$i],
                'keterangan' => $data['keterangan'][$i] ?? '',
                'qty' => $data['qtyreturjual'][$i] ?? 0,
                'harga' => $data['hargajual'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // dd(DB::table($tempDetail)->get());

            $hpp = DB::table('hpp')
                ->select('penerimaanharga')
                ->where('pengeluarandetailid', $data['penjualandetailid'][$i])
                ->first();

            //STORE KARTU STOK
            $kartuStok = (new KartuStok())->processStore([
                "tglbukti" => $returJualHeader->tglbukti,
                "penerimaandetailid" => $data['penjualandetailid'][$i],
                "pengeluarandetailid" => 0,
                'nobukti' => $returJualHeader->nobukti,
                'productid' => $data['productid'][$i],
                'qtypenerimaan' => $data['qtyreturjual'][$i],
                'totalpenerimaan' => $hpp->penerimaanharga * $data['qtyreturjual'][$i],
                'qtypengeluaran' => 0,
                'totalpengeluaran' => 0,
                "flag" => 'RJ',
                "seqno" => 3
            ]);

            // dd($kartuStok);
        }

        // querey update
        $queryUpdate = DB::table('returjualdetail as a')
            ->join("returjualheader as b", 'a.returjualid', '=', 'b.id')
            ->join("$tempDetail as c", 'a.id', '=', 'c.id')
            ->update([
                'a.id' => DB::raw('c.id'),
                'a.returjualid' => DB::raw('c.returjualid'),
                'a.productid' => DB::raw('c.productid'),
                'a.satuanid' => DB::raw('c.satuanid'),
                'a.penjualandetailid' => DB::raw('c.penjualandetailid'),
                'a.keterangan' => DB::raw('c.keterangan'),
                'a.qty' => DB::raw('c.qty'),
                'a.harga' => DB::raw('c.harga'),
                'a.modifiedby' => DB::raw('c.modifiedby'),
                'a.created_at' => DB::raw('c.created_at'),
                'a.updated_at' => DB::raw('c.updated_at')
            ]);

        // dd($queryUpdate);

        // delete retur jual detail
        $queryDelete = DB::table('returjualdetail as a')
            ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.id')
            ->whereNull('b.id')
            ->where('a.returjualid', "=", $returJualHeader->id)
            ->delete();

        // insert returjual detail add row
        $insertAddRowQuery =  DB::table("$tempDetail as a")
            ->where("a.id", '=', '0');

        // dd($insertAddRowQuery->get(), $queryDelete);

        $insert = DB::table('returjualdetail')->insertUsing(["id", "returjualid", "productid", "satuanid", "penjualandetailid", "keterangan", "qty", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

        //UPDATE HUTANG
        $hutang = DB::table('hutang')
            ->where('hutang.pembelianid', $returJualHeader->id)
            ->update([
                'nominalhutang' => $data['totaljual'],
                'nominalsisa' => $data['totaljual'],
                'updated_at' => $returJualHeader->updated_at
            ]);

        // dd(Hutang::where('pembelianid', $returJualHeader->id)->first());

        return $returJualHeader;
    }

    public function processDestroy($id, $postingDari = ''): ReturJualHeader
    {
        //DELETE KARTU STOK
        $returJualDetail = DB::table('returjualdetail')
            ->select(
                'returjualdetail.id',
                'returjualdetail.qty',
                'returjualdetail.harga',
                'returjualdetail.productid',
                'penjualandetail.id as penjualandetailid',
                'returjualheader.nobukti',
            )
            ->leftJoin('returjualheader', 'returjualdetail.returjualid', 'returjualheader.id')
            ->leftJoin('penjualandetail', 'returjualdetail.penjualandetailid', 'penjualandetail.id')
            ->where('returjualdetail.returjualid', $id)
            ->get();

        foreach ($returJualDetail as $detail) {
            $kartuStok = KartuStok::where('nobukti', $detail->nobukti)
                ->where('productid', $detail->productid)
                ->delete();

            $hpp = DB::table('hpp')
                ->select('*')
                ->where('pengeluarandetailid', $detail->penjualandetailid)
                ->first();

            $pembelian = DB::table('pembeliandetail')
                ->select(
                    'qty',
                    'qtyterpakai',
                    'productid'
                )
                ->where('id', $hpp->penerimaandetailid)
                ->first();

            if ($pembelian) {
                $qtyterpakai = $pembelian->qtyterpakai + $detail->qty ?? 0;
                $pembeliandetail = PembelianDetail::where('id', $hpp->penerimaandetailid)->first();
                $pembeliandetail->qtyterpakai = $qtyterpakai;
                $pembeliandetail->save();
            }

            // dd($pembeliandetail);
        }

        $returJualDetail = ReturJualDetail::where('returjualid', '=', $id)->get();
        $dataDetail = $returJualDetail->toArray();


        /*DELETE EXISTING FAKTUR PENJUALAN HEADER*/
        $returJualHeader = new returJualHeader();
        $returJualHeader = $returJualHeader->lockAndDestroy($id);
        $returJualHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $returJualHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $returJualHeader->id,
            'nobuktitrans' => $returJualHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $returJualHeader->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        //DELETE HUTANG  
        $getHutang = DB::table("hutang")
            ->select('id', 'nobukti')
            ->where('pembelianid', '=', $id)
            ->first();
        // dd($getHutang);
        $hutang = new Hutang();
        $hutang->processDestroy($getHutang->id);

        (new LogTrail())->processStore([
            'namatabel' => 'RETURJUALDETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $returJualHeaderLogTrail['id'],
            'nobuktitrans' => $returJualHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->name
        ]);

        // dd('test', $dataDetail);

        foreach ($dataDetail as $detail) {
            $tsc = DB::table('kartustok')
                ->select('*')
                ->where('productid', $detail['productid'])
                ->get();

            $qtySaldo = 0;
            $totalSaldo = 0;

            foreach ($tsc as $transaction) {
                if ($transaction->flag == 'B' || $transaction->flag == 'RJ') {
                    $qtySaldo += $transaction->qtypenerimaan;
                    $totalSaldo += $transaction->totalpenerimaan;
                } else {
                    $qtySaldo -= $transaction->qtypengeluaran;
                    $totalSaldo -= $transaction->totalpengeluaran;
                }

                DB::table('kartustok')
                    ->where('id', $transaction->id)
                    ->update([
                        'qtysaldo' => $qtySaldo,
                        'totalsaldo' => $totalSaldo
                    ]);
            }
        }

        return $returJualHeader;
    }

    public function cekValidasiAksi($penjualanid)
    {
        $penjualanHeader = DB::table('returjualheader')
            ->from(
                DB::raw("returjualheader as a")
            )
            ->select(
                'a.id',
                'a.nobukti'
            )
            ->where('a.flag', '=', 'GENERATED')
            ->where('a.id', '=', request()->id)
            ->first();

        if (isset($penjualanHeader)) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Penjualan ' . $penjualanHeader->nobukti,
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
}
