<?php

namespace App\Models;

use App\Services\RunningNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReturBeliHeader extends MyModel
{
    use HasFactory;

    protected $table = 'returbeliheader';

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
        $query = DB::table($this->table . ' as returbeliheader')
            ->select(
                "returbeliheader.id",
                "returbeliheader.tglbukti",
                "returbeliheader.nobukti",
                "returbeliheader.pembelianid",
                "pembelianheader.nobukti as pembeliannobukti",
                "returbeliheader.supplierid",
                "supplier.nama as suppliernama",
                "returbeliheader.keterangan",
                "returbeliheader.total",
                "returbeliheader.tglcetak",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'returbeliheader.created_at',
                'returbeliheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'returbeliheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'returbeliheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("pembelianheader"), 'returbeliheader.pembelianid', 'pembelianheader.id')
            ->leftJoin(DB::raw("supplier"), 'returbeliheader.supplierid', 'supplier.id');

        if (request()->tgldari && request()->tglsampai) {
            $query->whereBetween($this->table . '.tglbukti', [date('Y-m-d', strtotime(request()->tgldari)), date('Y-m-d', strtotime(request()->tglsampai))]);
        }

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;

        $this->sort($query);
        $this->filter($query);

        $data = $query->get();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('returbeliheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(returbeliheader.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(returbeliheader.nobukti, '/', 1)"), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'pembeliannobukti') {
            return $query->orderBy('pembelianheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'suppliernama') {
            return $query->orderBy('supplier.nama', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'suppliernama') {
                            $query = $query->where('supplier.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'pembeliannobukti') {
                            $query = $query->where('pembelianheader.nobukti', 'LIKE', "%$filters[data]%");
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
                            if ($filters['field'] != '') {
                                if ($filters['field'] == 'suppliernama') {
                                    $query = $query->orWhere('supplier.nama', 'like', "%$filters[data]%");
                                } else if ($filters['field'] == 'statusmemo') {
                                    $query = $query->orWhere('parameter.memo', 'LIKE', "%$filters[data]%");
                                } else if ($filters['field'] == 'pembeliannobukti') {
                                    $query = $query->orWhere('pembelianheader.nobukti', 'LIKE', "%$filters[data]%");
                                } else if ($filters['field'] == 'modifiedby_name') {
                                    $query = $query->orWhere('modifier.name', 'LIKE', "%$filters[data]%");
                                } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                                    $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                                } else {
                                    $query = $query->OrwhereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                                }
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

    public function findAll($id)
    {
        $query = DB::table('returbeliheader')
            ->select(
                "returbeliheader.id",
                "returbeliheader.tglbukti",
                "returbeliheader.nobukti",
                "returbeliheader.pembelianid",
                "pembelianheader.nobukti as pembeliannobukti",
                "returbeliheader.supplierid",
                "supplier.nama as suppliernama",
                "returbeliheader.keterangan",
                "returbeliheader.total",
                "returbeliheader.tglcetak",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                "parameter.text as statusnama",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'returbeliheader.created_at',
                'returbeliheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'returbeliheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'returbeliheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("supplier"), 'returbeliheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("pembelianheader"), 'returbeliheader.pembelianid', 'pembelianheader.id')
            ->where('returbeliheader.id', $id);

        $data = $query->first();
        // dd($data);
        return $data;
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
                $this->table.pembelianid,
                pembelianheader.nobukti as pembeliannobukti,
                $this->table.supplierid,
                supplier.nama as suppliernama,
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
            ->leftJoin(DB::raw("parameter"), 'returbeliheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'returbeliheader.modifiedby', 'modifier.id')
            ->leftJoin(DB::raw("supplier"), 'returbeliheader.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("pembelianheader"), 'returbeliheader.pembelianid', 'pembelianheader.id');
    }

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->selectColumns($query);
        // dd($query);
        $query = $this->filter($query);
        $query = $this->sort($query);

        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            tglbukti DATETIME,
            nobukti VARCHAR(100),
            pembelianid INT,
            pembeliannobukti VARCHAR(100),
            supplierid INT,
            suppliernama VARCHAR(100),
            keterangan VARCHAR(100),
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
            )
        ");
        DB::table($temp)->insertUsing([
            "id", "tglbukti", "nobukti", "pembelianid", "pembeliannobukti", "supplierid", "suppliernama", "keterangan", "total",
            "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
            "created_at", "updated_at"
        ], $query);

        // dd(DB::table($temp)->get());
        return $temp;
    }

    public function editingAt($id, $btn)
    {
        $returBeliHeader = ReturBeliHeader::find($id);
        if ($btn == 'EDIT') {
            $returBeliHeader->editingby = auth('api')->user()->name;
            $returBeliHeader->editingat = date('Y-m-d H:i:s');
        } else {

            if ($returBeliHeader->editingby == auth('api')->user()->name) {
                $returBeliHeader->editingby = '';
                $returBeliHeader->editingat = null;
            }
        }
        if (!$returBeliHeader->save()) {
            throw new \Exception("Error Update retur beli header.");
        }

        return $returBeliHeader;
    }

    public function processData($data)
    {
        $ids = [];
        $satuanIds = [];
        $qtys = [];
        $keteranganDetails = [];
        $hargas = [];
        $pembelianDetails = [];
        $pesananFinalDetails = [];

        foreach ($data as $detail) {
            $productIds = request()->productid;
            $ids[] = $detail['id'];
            $satuanIds[] = $detail['satuanid'];
            $qtys[] = $detail['qty'];
            $keteranganDetails[] = $detail['keterangandetail'];
            $hargas[] = $detail['harga'];
            $pembelianDetails[] = $detail['pembeliandetailid'] ?? 0;
            $pesananFinalDetails[] = $detail['pesananfinaldetailid'] ?? 0;
        }

        $data = [
            "tglbukti" => request()->tglbukti,
            "pembelianid" => request()->pembelianid,
            "supplierid" => request()->supplierid,
            "keterangan" => request()->keterangan,
            "total" => request()->total,
            "status" => request()->status ?? 1,
            "id" =>  $ids,
            "productid" =>  $productIds,
            "satuanid" => $satuanIds,
            "qty" => $qtys,
            "keterangandetail" => $keteranganDetails,
            "harga" => $hargas,
            "pembeliandetailid" => $pembelianDetails,
            "pesananfinaldetailid" => $pesananFinalDetails
        ];
        // dd($data);
        return $data;
    }

    public function getPembelianDetail($pembelianid)
    {
        $query = DB::table('pembeliandetail')->from(DB::raw("pembeliandetail"))
            ->select(
                'pembeliandetail.id as pembeliandetailid',
                // 'pembeliandetail.pesananfinaldetailid',
                'pembeliandetail.productid as id',
                'product.nama as productnama',
                'pembeliandetail.satuanid',
                'satuan.nama as satuannama',
                'pembeliandetail.qtyretur as qtysdhretur',
                'pembeliandetail.qty as qtypesanan',
                'pembeliandetail.harga',
                'pembeliandetail.id as pembeliandetailid',
            )
            ->leftJoin('satuan', 'pembeliandetail.satuanid', 'satuan.id')
            ->leftJoin('product', 'pembeliandetail.productid', 'product.id')
            ->where('pembelianid', $pembelianid);

        $data = $query->get();

        // dd($data);

        return $data;
    }

    public function getEditPembelianDetail($pembelianid, $id)
    {
        // dd('test');
        $tempDetail = $this->createTempPembelianDetail($id, $pembelianid);
        $tempRetur = $this->createTempReturDetail($id);


        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $temp (
            id INT UNSIGNED,
            returbeliid INT UNSIGNED,
            returbelinobukti VARCHAR(100),
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
            pembeliandetailid INT UNSIGNED
           
            -- pesananfinaldetailid INT UNSIGNED
        )");


        $retur = DB::table($tempRetur)
            ->select(
                DB::raw("id, returbeliid,returbelinobukti, productid, productnama, keterangandetail, qty, qtypesanan, qtysdhretur, harga, totalharga, satuanid, satuannama, pembeliandetailid")
            );

        // dd($retur->get());

        DB::table($temp)->insertUsing([
            "id", "returbeliid", "returbelinobukti", "productid", "productnama", "keterangandetaiL", "qty", "qtypesanan", "qtysdhretur", "harga", "totalharga", "satuanid", "satuannama", "pembeliandetailid",
        ], $retur);

        $pembelians = DB::table("$tempDetail as a")
            ->select(
                DB::raw("a.id,0 as returbeliid, null as returbelinobukti, a.productid, a.productnama, null as keterangandetail, 0 as qty, a.qtypesanan, a.qtysdhretur, a.harga, null as totalharga, a.satuanid, a.satuannama, a.pembeliandetailid")
            )
            ->leftJoin(DB::raw("$tempRetur as b"), "a.id", "b.productid");

        DB::table($temp)->insertUsing([
            "id", "returbeliid", "returbelinobukti", "productid", "productnama", "keterangandetail", "qty", "qtypesanan", "qtysdhretur", "harga", "totalharga", "satuanid", "satuannama", "pembeliandetailid",
        ], $pembelians);

        $data = DB::table($temp)
            ->select(DB::raw("$temp.id as id,$temp.returbeliid as returbeliid, $temp.id as productid,returbelinobukti, productid, productnama, keterangandetail, qty, qtypesanan, qtysdhretur, harga, totalharga, satuanid, satuannama, pembeliandetailid"))
            ->get();

        // dd($data);

        return $data;
    }

    public function createTempReturDetail($id)
    {
        $tempo = 'tempRetur' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempo (
            id BIGINT,
            returbeliid INT UNSIGNED,
            returbelinobukti VARCHAR(100),
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
            pembeliandetailid INT UNSIGNED
            -- pesananfinaldetailid INT UNSIGNED

        )");

        $fetch = DB::table('returbelidetail')
            ->select(
                "returbelidetail.productid as id",
                "returbelidetail.id as returbeliid",
                "returbeliheader.nobukti as returbelinobukti",
                "returbelidetail.productid",
                "product.nama as productnama",
                "returbelidetail.keterangan as keterangandetail",
                "returbelidetail.qty",
                "returbelidetail.harga",
                DB::raw('(returbelidetail.qty * returbelidetail.harga) AS totalharga'),
                "returbelidetail.satuanid",
                "satuan.nama as satuannama",
                'pembeliandetail.qty as qtypesanan',
                'pembeliandetail.qtyretur as qtysdhretur',
                'pembeliandetail.id as pembeliandetailid',
                // 'pembeliandetail.pesananfinaldetailid'
            )
            ->leftJoin(DB::raw("returbeliheader"), 'returbelidetail.returbeliid', 'returbeliheader.id')
            ->leftJoin(DB::raw("product"), 'returbelidetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'returbelidetail.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("pembeliandetail"), 'returbelidetail.pembeliandetailid', 'pembeliandetail.id')
            ->where('returbeliid', '=', $id);
        // dd($fetch->get());

        DB::table($tempo)->insertUsing([
            "id", "returbeliid", "returbelinobukti", "productid", "productnama", "keterangandetail", "qty", "harga", "totalharga",
            "satuanid", "satuannama", "qtypesanan", "qtysdhretur", "pembeliandetailid",
        ], $fetch);
        // dd(DB::table($tempo)->get());
        return $tempo;
    }

    public function createTempPembelianDetail($id, $pembelianid)
    {
        $tempGetPembelian = 'tempgetpembelian' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempGetPembelian (
            id INT UNSIGNED,
            productid INT UNSIGNED,
            productnama VARCHAR(100),
            satuanid INT UNSIGNED,
            satuannama VARCHAR(100),
            qtysdhretur INT,
            qtypesanan INT,
            harga FLOAT,
            pembelianid INT UNSIGNED,
            pembeliandetailid INT UNSIGNED
            -- pesananfinaldetailid INT UNSIGNED
        )");

        $fetch = DB::table('pembeliandetail')->from(DB::raw("pembeliandetail"))
            ->select(
                'pembeliandetail.productid as id',
                'pembeliandetail.productid as productid',
                'product.nama as productnama',
                'pembeliandetail.satuanid',
                'satuan.nama as satuannama',
                'pembeliandetail.qtyretur as qtysdhretur',
                'pembeliandetail.qty as qtypesanan',
                'pembeliandetail.harga',
                'pembeliandetail.pembelianid',
                'pembeliandetail.id as pembeliandetailid',
                // 'pembeliandetail.pesananfinaldetailid'
            )
            ->leftJoin('satuan', 'pembeliandetail.satuanid', 'satuan.id')
            ->leftJoin('product', 'pembeliandetail.productid', 'product.id')
            ->where('pembelianid', $pembelianid);

        DB::table($tempGetPembelian)->insertUsing([
            "id", "productid", "productnama", "satuanid", "satuannama", "qtysdhretur", "qtypesanan", "harga",
            "pembelianid", "pembeliandetailid"
        ], $fetch);

        // dd(DB::table($tempGetPembelian)->get());

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
            pembeliandetailid INT UNSIGNED
            -- pesananfinaldetailid INT UNSIGNED
        )");

        $tempRetur = $this->createTempReturDetail($id);
        // dd(DB::table($tempRetur)->get());

        $fetch = DB::table($tempGetPembelian)->from(DB::raw("$tempGetPembelian as a"))
            ->select(
                'a.id',
                'a.productid',
                'a.productnama',
                'a.satuanid',
                'a.satuannama',
                'a.qtysdhretur',
                'a.qtypesanan',
                'a.harga',
                'a.pembeliandetailid'
                // 'a.pesananfinaldetailid'
            )
            ->leftJoin($tempRetur, 'a.pembeliandetailid', "$tempRetur.pembeliandetailid")
            ->whereNull("$tempRetur.pembeliandetailid")
            ->whereNotIn('a.pembeliandetailid', function ($query) use ($tempRetur) {
                $query->select('pembeliandetailid')->from($tempRetur);
            });

        // dd($fetch->first());

        DB::table($temp)->insertUsing([
            "id", "productid", "productnama", "satuanid", "satuannama", "qtysdhretur", "qtypesanan", "harga",
            "pembeliandetailid",
        ], $fetch);

        // dd(DB::table($temp)->get());

        return $temp;
    }

    public function processStore(array $data): ReturBeliHeader
    {
        // dd($data);
        $returBeliHeader = new ReturBeliHeader();

        /*STORE HEADER*/
        $group = 'RETUR BELI HEADER BUKTI';
        $subGroup = 'RETUR BELI HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();
        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $returBeliHeader->tglbukti = $tglbukti;
        $returBeliHeader->pembelianid = $data['pembelianid'];
        $returBeliHeader->supplierid = $data['supplierid'];
        $returBeliHeader->total = $data['total'];
        $returBeliHeader->status = $data['status'] ?? 1;

        if (isset($data['flag']) == 'generated') {
            $returBeliHeader->flag = 'generated';
        }

        $returBeliHeader->modifiedby = auth('api')->user()->id;

        $returBeliHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $returBeliHeader->getTable(), date('Y-m-d', strtotime($data['tglbukti'])));

        if (!$returBeliHeader->save()) {
            throw new \Exception("Error storing retur beli header.");
        }

        $returBeliHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($returBeliHeader->getTable()),
            'postingdari' => strtoupper('ENTRY RETUR BELI HEADER'),
            'idtrans' => $returBeliHeader->id,
            'nobuktitrans' => $returBeliHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $returBeliHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        // dd($returBeliHeader);
        $returBeliDetails = [];

        // dd($data);

        for ($i = 0; $i < count($data['productid']); $i++) {
            $returBeliDetail = (new ReturBeliDetail())->processStore($returBeliHeader, [
                'returbeliid' => $returBeliHeader->id,
                'pembeliandetailid' => $data['pembeliandetailid'][$i],
                'returjualdetailid' => $data['returjualdetailid'][$i] ?? 0,
                'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
                'productid' => $data['productid'][$i],
                'keterangan' => $data['keterangan'][$i] ?? "",
                'qty' => $data['qty'][$i],
                'satuanid' => $data['satuanid'][$i],
                'harga' => $data['harga'][$i],
                'modifiedby' => auth('api')->user()->id,
            ]);
            $returBeliDetails[] = $returBeliDetail->toArray(); 


            $pembelianDetail = PembelianDetail::where('id', $data['pembeliandetailid'][$i])->first();
            if ($pembelianDetail) {
                $pembelianDetail->update([
                    'qtyretur' => $data['qty'][$i],
                ]);
            }

            $query = DB::table('returbelidetail')
                ->select(
                    'returbeliheader.id as pengeluaranid',
                    'returbeliheader.tglbukti',
                    "returbeliheader.nobukti as pengeluarannobukti",
                    "returbelidetail.id as pengeluarandetailid",
                    "returbelidetail.productid",
                    "returbelidetail.qty as pengeluaranqty",
                    "returbelidetail.harga as pengeluaranhargahpp",
                    "product.hargabeli as pengeluaranharga",
                    DB::raw('returbelidetail.qty * returbelidetail.harga as pengeluarantotalhpp'),
                    DB::raw('returbelidetail.qty * product.hargabeli as pengeluarantotal'),
                )
                ->leftJoin("returbeliheader", "returbelidetail.returbeliid", "returbeliheader.id")
                ->leftJoin("product", "returbelidetail.productid", "product.id")
                ->where("returbeliheader.id", $returBeliHeader->id)
                ->where("returbelidetail.productid", $data['productid'][$i])
                ->first();

            // dd($query);
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
                "flag" => 'RB',
                "flagkartustok" => 'RB',
                "seqno" => 4,
            ];
            $hpp = (new HPP())->processStore($dataHpp);

            // $pembelian = DB::table('pembeliandetail')
            //     ->select(
            //         'qty','qtyterpakai','productid'
            //     )
            //     // ->where('id', )
            //     ->get();
            // dd($pembelian);
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($returBeliHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('ENTRY RETUR JUAL DETAIL'),
            'idtrans' =>  $returBeliHeaderLogTrail->id,
            'nobuktitrans' => $returBeliHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $returBeliDetails,
            'modifiedby' => auth('api')->user()->user,
        ]);

        $tgljatuhtempo = date("Y-m-d", strtotime("+1 month", strtotime($tglbukti)));
        $dataPiutang = [
            "tglbukti" => $tglbukti,
            "penjualanid" => $returBeliHeader->id,
            "tglbuktipenjualan" => $tglbukti,
            "tgljatuhtempo" => $tgljatuhtempo,
            "customerid" => $data['supplierid'],
            "keterangan" => '',
            "nominalpiutang" => $data['total'],
            "nominalsisa" => $data['total'],
            "tglcetak" => '2023-11-11',
            "status" => $row['status'] ?? 1,
            "flag" => 'RB',
        ];
        $piutang = (new Piutang())->processStore($dataPiutang);



        return $returBeliHeader;
    }

    // public function processUpdateOld(ReturBeliHeader $returBeliHeader, array $data): ReturBeliHeader
    // {
    //     $nobuktiOld = $returBeliHeader->nobukti;

    //     $group = 'RETUR BELI HEADER BUKTI';
    //     $subGroup = 'RETUR BELI HEADER BUKTI';

    //     $returBeliHeader->pembelianid = $data['pembelianid'];
    //     $returBeliHeader->supplierid = $data['supplierid'];
    //     $returBeliHeader->total = $data['total'];
    //     $returBeliHeader->status = $data['status'] ?? 1;
    //     $returBeliHeader->modifiedby = auth('api')->user()->id;

    //     if (!$returBeliHeader->save()) {
    //         throw new \Exception("Error updating Retur Beli Header.");
    //     }

    //     $returBeliHeaderLogTrail = (new LogTrail())->processStore([
    //         'namatabel' => strtoupper($returBeliHeader->getTable()),
    //         'postingdari' => strtoupper('ENTRY RETUR BELI HEADER'),
    //         'idtrans' => $returBeliHeader->id,
    //         'nobuktitrans' => $returBeliHeader->nobukti,
    //         'aksi' => 'ENTRY',
    //         'datajson' => $returBeliHeader->toArray(),
    //         'modifiedby' => auth('api')->user()->user
    //     ]);

    //     // dd($data);

    //     /*DELETE EXISTING RETUR BELI DETAIL*/
    //     $returBeliDetail = ReturBeliDetail::where('returbeliid', $returBeliHeader->id)->lockForUpdate()->delete();

    //     $returBeliDetails = [];
    //     for ($i = 0; $i < count($data['productid']); $i++) {
    //         $returBeliDetail = (new ReturBeliDetail())->processStore($returBeliHeader, [
    //             'returbeliid' => $returBeliHeader->id,
    //             'pembeliandetailid' => $data['pembeliandetailid'][$i],
    //             'pesananfinaldetailid' => $data['pesananfinaldetailid'][$i],
    //             'productid' => $data['productid'][$i],
    //             'keterangan' => $data['keterangan'][$i] ?? "",
    //             'qty' => $data['qty'][$i],
    //             'satuanid' => $data['satuanid'][$i],
    //             'harga' => $data['harga'][$i],
    //             'modifiedby' => auth('api')->user()->id,
    //         ]);
    //         $returBeliDetails[] = $returBeliDetail->toArray();

    //         // dd($returBeliDetails);

    //         (new LogTrail())->processStore([
    //             'namatabel' => strtoupper($returBeliHeaderLogTrail->getTable()),
    //             'postingdari' =>  strtoupper('ENTRY RETUR JUAL DETAIL'),
    //             'idtrans' =>  $returBeliHeaderLogTrail->id,
    //             'nobuktitrans' => $returBeliHeader->nobukti,
    //             'aksi' => 'ENTRY',
    //             'datajson' => $returBeliDetails,
    //             'modifiedby' => auth('api')->user()->user,
    //         ]);

    //         // if ($returBeliHeader->flag == 'GENERATED') {
    //         $hpp = DB::table('hpp')
    //             ->select('*')
    //             ->where('hpp.pengeluaranid', '=', $returBeliHeader->id)
    //             ->first();

    //         // dd($returBeliDetail, $returBeliHeader, $hpp);
    //         // dd($hpp);

    //         $fetchDataHpp = DB::table('hpp')
    //             ->select(
    //                 'hpp.id',
    //                 'hpp.pengeluaranid',
    //                 DB::raw("
    //                         CASE
    //                             WHEN hpp.flag = 'PJ' THEN penjualanheader.nobukti
    //                             WHEN hpp.flag = 'RB' THEN returbeliheader.nobukti
    //                             ELSE NULL
    //                         END AS pengeluarannobukti
    //                     "),
    //                 DB::raw("
    //                         CASE
    //                             WHEN hpp.flag = 'PJ' THEN penjualandetail.id
    //                             WHEN hpp.flag = 'RB' THEN returbelidetail.id
    //                             ELSE NULL
    //                         END AS pengeluarandetailid
    //                     "),
    //                 'penerimaanid',
    //                 'pembelianheader.nobukti as penerimaannobukti',
    //                 'penerimaandetailid',
    //                 DB::raw("
    //                         CASE
    //                             WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
    //                             WHEN hpp.flag = 'RB' THEN returbelidetail.qty
    //                             ELSE NULL
    //                         END AS pengeluaranqty
    //                     "),
    //                 'penerimaanharga',
    //                 'penerimaantotal',
    //                 DB::raw("
    //                         CASE
    //                             WHEN hpp.flag = 'PJ' THEN penjualandetail.harga
    //                             WHEN hpp.flag = 'RB' THEN returbelidetail.harga
    //                             ELSE NULL
    //                         END AS pengeluaranhargahpp
    //                     "),
    //                 DB::raw("
    //                         CASE
    //                             WHEN hpp.flag = 'PJ' THEN penjualandetail.harga * (pengeluaranqty - penjualandetail.qtyreturjual)
    //                             WHEN hpp.flag = 'RB' THEN returbelidetail.harga * returbelidetail.qty
    //                             ELSE NULL
    //                         END AS pengeluarantotalhpp
    //                     "),
    //                 'hpp.productid',
    //             )
    //             ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
    //             ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
    //             ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
    //             ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
    //             ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
    //             ->where('hpp.id', '>=', $hpp->id)
    //             // ->orderBy('pengeluaranid', 'asc')
    //             ->get();

    //         dd($fetchDataHpp);

    //         $fetchHpp = DB::table('hpp')
    //             ->select(
    //                 'hpp.pengeluaranid',
    //                 DB::raw("
    //                         CASE
    //                             WHEN hpp.flag = 'PJ' THEN penjualanheader.nobukti
    //                             WHEN hpp.flag = 'RB' THEN returbeliheader.nobukti
    //                             ELSE NULL
    //                         END AS pengeluarannobukti
    //                     "),
    //                 'pengeluarandetailid',
    //                 'penerimaanid',
    //                 'pembelianheader.nobukti as penerimaannobukti',
    //                 'penerimaandetailid',
    //                 DB::raw("
    //                         CASE
    //                             WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
    //                             WHEN hpp.flag = 'RB' THEN returbelidetail.qty
    //                             ELSE NULL
    //                         END AS pengeluaranqty
    //                     "),
    //                 'penerimaantotal',
    //                 'hpp.productid',
    //             )
    //             ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
    //             ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
    //             ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
    //             ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
    //             ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
    //             ->where('hpp.id', '>=', $hpp->id)
    //             // ->orderBy('pengeluaranid', 'asc')
    //             ->get();

    //         // dd($fetchHpp);

    //         foreach ($fetchHpp as $fetch) {
    //             // dd($fetch);
    //             $pembelian = DB::table('pembeliandetail')
    //                 ->select(
    //                     'qty',
    //                     'qtyterpakai',
    //                     'productid'
    //                 )
    //                 ->where('id', $fetch->penerimaandetailid)
    //                 ->first();

    //             // dd($pembelian);

    //             if ($pembelian) {
    //                 $qtyterpakai = $pembelian->qtyterpakai + $fetch->pengeluaranqty;
    //                 $pembeliandetail = PembelianDetail::where('id', $fetch->penerimaandetailid)->first();
    //                 $pembeliandetail->qtyterpakai = $qtyterpakai;
    //                 $pembeliandetail->save();
    //             }
    //             // dd($pembeliandetail);

    //             $hpp = HPP::where('pengeluaranid', $fetch->pengeluaranid)->first();
    //             if ($hpp) {
    //                 $hpp->delete();
    //             }

    //             $kartuStok = KartuStok::where('nobukti', $fetch->pengeluarannobukti)->first();
    //             if ($kartuStok) {
    //                 $kartuStok->delete();
    //             }
    //             // dump($pembeliandetail, $kartuStok, $hpp);
    //         }
    //         // die;

    //         dd($fetchDataHpp);

    //         foreach ($fetchDataHpp as $row) {
    //             $flag = null;
    //             $flagkartustok = null;
    //             $seqno = 0;

    //             if ($row->pengeluarannobukti !== null) {
    //                 if (strpos($row->pengeluarannobukti, 'J') === 0) {
    //                     $flag = 'PJ';
    //                     $flagkartustok = 'J';
    //                     $seqno = 2;
    //                 } elseif (strpos($row->pengeluarannobukti, 'RB') === 0) {
    //                     $flag = 'RB';
    //                     $flagkartustok = 'RB';
    //                     $seqno = 4;
    //                 }
    //             }

    //             $dataHpp = [
    //                 "pengeluaranid" => $row->pengeluaranid,
    //                 "pengeluarandetailid" => $row->pengeluarandetailid,
    //                 "pengeluarannobukti" => $row->pengeluarannobukti,
    //                 "productid" => $row->productid,
    //                 "qtypengeluaran" => $row->pengeluaranqty,
    //                 "hargapengeluaranhpp" => $row->pengeluaranhargahpp,
    //                 "hargapengeluaran" => $row->penerimaanharga,
    //                 "totalpengeluaranhpp" => $row->pengeluarantotalhpp,
    //                 "totalpengeluaran" => $row->penerimaantotal,
    //                 "flag" => $flag,
    //                 "flagkartustok" => $flagkartustok,
    //                 "seqno" => $seqno,
    //             ];
    //             $hpp = (new HPP())->processStore($dataHpp);
    //         }
    //     }

    //     return $returBeliHeader;
    // }

    public function processUpdate(ReturBeliHeader $returBeliHeader, array $data): ReturBeliHeader
    {
        // dd($data);
        $tempDetail = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempDetail (
            id INT UNSIGNED NULL,
            returbeliid INT NULL,
            productid INT NULL,
            satuanid INT NULL,
            pembeliandetailid INT NULL,
            keterangan VARCHAR(500),
            qty FLOAT,
            harga FLOAT,
            modifiedby VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME
        
        )");

        $nobuktiOld = $returBeliHeader->nobukti;

        $group = 'RETUR BELI HEADER BUKTI';
        $subGroup = 'RETUR BELI HEADER BUKTI';

        $returBeliHeader->pembelianid = $data['pembelianid'];
        $returBeliHeader->supplierid = $data['supplierid'];
        $returBeliHeader->total = $data['total'];
        $returBeliHeader->status = $data['status'] ?? 1;
        if (isset($data['flag']) == 'generated') {
            $returBeliHeader->flag = 'generated';
        }
        $returBeliHeader->modifiedby = auth('api')->user()->id;

        if (!$returBeliHeader->save()) {
            throw new \Exception("Error updating Retur Beli Header.");
        }

        // dd($returBeliHeader);

        $returBeliHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($returBeliHeader->getTable()),
            'postingdari' => strtoupper('ENTRY RETUR BELI HEADER'),
            'idtrans' => $returBeliHeader->id,
            'nobuktitrans' => $returBeliHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $returBeliHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);
        // dd($data);

        for ($i = 0; $i < count($data['productid']); $i++) {
            DB::table($tempDetail)->insert([
                'id' => $data['id'][$i],
                'returbeliid' => $returBeliHeader->id,
                'productid' => $data['productid'][$i],
                'satuanid' => $data['satuanid'][$i],
                'pembeliandetailid' => $data['pembeliandetailid'][$i],
                'keterangan' => $data['keterangan'][$i] ?? '',
                'qty' => $data['qty'][$i] ?? 0,
                'harga' => $data['harga'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        if ($data['flag'] !== 'generated') {

            // UPDATE RETUR BELI DETAIL
            $queryUpdate = DB::table('returbelidetail as a')
                ->join("returbeliheader as b", 'a.returbeliid', '=', 'b.id')
                ->join("$tempDetail as c", 'a.id', '=', 'c.id')
                ->update([
                    'a.id' => DB::raw('c.id'),
                    'a.returbeliid' => DB::raw('c.returbeliid'),
                    'a.productid' => DB::raw('c.productid'),
                    'a.satuanid' => DB::raw('c.satuanid'),
                    'a.pembeliandetailid' => DB::raw('c.pembeliandetailid'),
                    'a.keterangan' => DB::raw('c.keterangan'),
                    'a.qty' => DB::raw('c.qty'),
                    'a.harga' => DB::raw('c.harga'),
                    'a.modifiedby' => DB::raw('c.modifiedby'),
                    'a.created_at' => DB::raw('c.created_at'),
                    'a.updated_at' => DB::raw('c.updated_at')
                ]);


            // UPDATE PIUTANG
            $queryUpdatePiutang =  DB::table('piutang as a')
                ->where("a.penjualanid", $returBeliHeader->id)
                ->join("returbeliheader as b", 'a.penjualanid', '=', 'b.id')
                ->update([
                    'a.nominalpiutang' => DB::raw('b.total'),
                    'a.nominalsisa' => DB::raw('b.total'),
                    'a.updated_at' => DB::raw('b.updated_at')
                ]);

            // DELETE RETUR BELI DETAIL
            $queryDelete = DB::table('returbelidetail as a')
                ->leftJoin("$tempDetail as b", 'a.id', '=', 'b.id')
                ->whereNull('b.id')
                ->where('a.returbeliid', "=", $returBeliHeader->id)
                ->delete();

            // INSERT RETUR BELI DETAIL
            $insertAddRowQuery =  DB::table("$tempDetail as a")
                ->where("a.id", '=', '0');
            DB::table('returbelidetail')->insertUsing(["id", "returbeliid", "productid", "satuanid", "pembeliandetailid", "keterangan", "qty", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);
        } else {

            // UPDATE RETUR BELI DETAIL
            $queryUpdate = DB::table('returbelidetail as a')
                ->join("returbeliheader as b", 'a.returbeliid', '=', 'b.id')
                ->join("$tempDetail as c", 'a.id', '=', 'c.id')
                ->update([
                    'a.id' => DB::raw('c.id'),
                    'a.returbeliid' => DB::raw('c.returbeliid'),
                    'a.productid' => DB::raw('c.productid'),
                    'a.satuanid' => DB::raw('c.satuanid'),
                    'a.pembeliandetailid' => DB::raw('c.pembeliandetailid'),
                    'a.keterangan' => DB::raw('c.keterangan'),
                    'a.qty' => DB::raw('c.qty'),
                    'a.harga' => DB::raw('c.harga'),
                    'a.modifiedby' => DB::raw('c.modifiedby'),
                    'a.created_at' => DB::raw('c.created_at'),
                    'a.updated_at' => DB::raw('c.updated_at')
                ]);

            // INSERT RETUR BELI DETAIL
            $insertAddRowQuery =  DB::table("$tempDetail as a")
                ->where("a.id", '=', '0');

            DB::table('returbelidetail')->insertUsing(["id", "returbeliid", "productid", "satuanid", "pembeliandetailid", "keterangan", "qty", "harga", "modifiedby", "created_at", "updated_at"], $insertAddRowQuery);

            //UPDATE TOTAL RETUR BELI HEADER
            $total = 0;
            $detail = DB::table('returbelidetail')
                ->select('*')
                ->where('returbeliid', $returBeliHeader->id)
                ->get();
            foreach ($detail as $item) {
                $total += $item->qty * $item->harga;
            }
            $returBeliHeader->total = $total;
            if (!$returBeliHeader->save()) {
                throw new \Exception("Error updating Retur Beli Header.");
            }

            // UPDATE PIUTANG
            $queryUpdatePiutang =  DB::table('piutang as a')
                ->where("a.penjualanid", $returBeliHeader->id)
                ->join("returbeliheader as b", 'a.penjualanid', '=', 'b.id')
                ->update([
                    'a.nominalpiutang' => DB::raw('b.total'),
                    'a.nominalsisa' => DB::raw('b.total'),
                    'a.updated_at' => DB::raw('b.updated_at')
                ]);

            // dd(Piutang::where('penjualanid', $returBeliHeader->id)->first());
        }

        foreach (DB::table($tempDetail)->get() as $value) {
            // UPDATE QTY RETUR PEMBELIAN DETAIL
            $queryUpdatePembelian = DB::table('pembeliandetail as a')
                ->where("a.id", $value->pembeliandetailid)
                ->update([
                    'a.qtyretur' => $value->qty
                ]);
        }

        $hppRow = DB::table('hpp')
            ->select('*')
            ->where('hpp.pengeluaranid', '=', $returBeliHeader->id)
            ->where('flag', 'RB')
            ->first();

        $query = DB::table('returbelidetail')
            ->select('*', 'returbelidetail.id as pengeluarandetailid', 'hpp.id as hppid', 'returbelidetail.productid')
            ->leftJoin('hpp', 'hpp.pengeluarandetailid', 'returbelidetail.id')
            ->where("returbeliid", $returBeliHeader->id)
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

        if ($hasNullHppId) {
            $fetchDataHpp = collect();
            // dd($combinedArray);
            foreach ($combinedArray as $detail => $value) {
                // dd($value, $detail);
                $hpp = DB::table('hpp')
                    ->select('*')
                    ->where('hpp.pengeluarandetailid', '=', $value->pengeluarandetailid)
                    ->where('flag', 'RB')
                    ->first();

                // dump($hpp);

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
                    // dd($value);
                    $fetchDataHppElse = DB::table('returbelidetail')
                        ->select(
                            'returbeliheader.id as pengeluaranid',
                            'returbeliheader.tglbukti',
                            "returbeliheader.nobukti as pengeluarannobukti",
                            "returbelidetail.id as pengeluarandetailid",
                            "returbelidetail.qty as pengeluaranqty",
                            "product.hargabeli as penerimaanharga",
                            DB::raw('returbelidetail.qty * product.hargabeli as penerimaantotal'),
                            "returbelidetail.harga as pengeluaranhargahpp",
                            DB::raw('returbelidetail.qty * returbelidetail.harga as pengeluarantotalhpp'),
                            "returbelidetail.productid",
                        )
                        ->leftJoin("returbeliheader", "returbelidetail.returbeliid", "returbeliheader.id")
                        ->leftJoin("product", "returbelidetail.productid", "product.id")
                        ->where("returbeliid", $returBeliHeader->id)
                        ->where("returbelidetail.productid", $value->productid)
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
            }
            // die;
        } else {
            $filteredData = DB::table('hpp')
                ->select(
                    'hpp.id',
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
                    // 'hpp.pengeluarannobukti',
                    DB::raw("
                            CASE
                                WHEN hpp.flag = 'PJ' THEN penjualandetail.id
                                WHEN hpp.flag = 'RB' THEN returbelidetail.id
                                ELSE NULL
                            END AS pengeluarandetailid
                        "),
                    'penerimaanid',
                    'pembelianheader.nobukti as penerimaannobukti',
                    'penerimaandetailid',
                    DB::raw("
                            CASE
                                WHEN hpp.flag = 'PJ' THEN penjualandetail.qty
                                WHEN hpp.flag = 'RB' THEN returbelidetail.qty
                                ELSE NULL
                            END AS pengeluaranqty
                        "),
                    'penerimaanharga',
                    'penerimaantotal',
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
                    'hpp.productid',
                )
                ->leftJoin('penjualanheader', 'penjualanheader.id', '=', 'hpp.pengeluaranid')
                ->leftJoin('returbeliheader', 'returbeliheader.id', '=', 'hpp.pengeluaranid')
                ->leftJoin('penjualandetail', 'penjualandetail.id', '=', 'hpp.pengeluarandetailid')
                ->leftJoin('returbelidetail', 'returbelidetail.id', '=', 'hpp.pengeluarandetailid')
                ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id')
                ->where('hpp.id', '>=', $hppRow->id)
                ->orderBy('id', 'asc')
                ->get();

            // dd($filteredData, $hppRow);

            $hasNull = $filteredData->contains(function ($item, $key) {
                return $item->pengeluarandetailid === null;
            });

            // dd($hasNull);
            if ($hasNull) {
                $fetchDataHpp = $filteredData->filter(function ($item, $key) {
                    return $item->pengeluarandetailid !== null;
                });
            } else {
                $fetchDataHpp = $filteredData;
            }
        }

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

        foreach ($fetchHpp as $fetch) {

            $hpp = HPP::where('pengeluarandetailid', $fetch->pengeluarandetailid)->where('pengeluarannobukti', $fetch->pengeluarannobukti)->first();
            if ($hpp) {
                $hpp->delete();
            }
            // dd($fetch);

            $kartuStok = KartuStok::where('pengeluarandetailid', $fetch->pengeluarandetailid)->where('nobukti', $fetch->pengeluarannobukti)->first();
            // dump($kartuStok);
            if ($kartuStok) {
                $kartuStok->delete();
            }
            // dump($kartuStok);

            $pembelian = DB::table('pembeliandetail')
                ->select(
                    'qty',
                    'qtyterpakai',
                    'productid'
                )
                ->where('id', $fetch->penerimaandetailid)
                ->first();

            // dump($pembelian);

            if ($pembelian) {
                $qtyterpakai = $pembelian->qtyterpakai - $fetch->pengeluaranqty;

                // dump($qtyterpakai, $pembelian->qtyterpakai, $fetch->pengeluaranqty);
                $pembelianDetail = PembelianDetail::where('id', $fetch->penerimaandetailid)->first();
                $pembelianDetail->qtyterpakai = $qtyterpakai;
                $pembelianDetail->save();
            }

            // dd($pembelianDetail);

            $tsc = DB::table('kartustok')
                ->select('*')
                ->where('productid', $fetch->productid)
                ->get();

            // dd($tsc);

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
        }

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
                "pengeluarandetailid" => $row->pengeluarandetailid,
                "pengeluarannobukti" => $row->pengeluarannobukti,
                "productid" => $row->productid,
                "qtypengeluaran" => $row->pengeluaranqty,
                "hargapengeluaranhpp" => $row->pengeluaranhargahpp,
                "hargapengeluaran" => $row->penerimaanharga,
                "totalpengeluaranhpp" => $row->pengeluarantotalhpp,
                "totalpengeluaran" => $row->penerimaantotal,
                "flag" => $flag,
                "flagkartustok" => $flagkartustok,
                "seqno" => $seqno,
            ];
            // dump($dataHpp);
            $hpp = (new HPP())->processStore($dataHpp);
            // dump($hpp);
        }

        // die;
        return $returBeliHeader;
    }

    public function processDestroy($id, $postingDari = ''): ReturBeliHeader
    {
        // dd($id);
        $hpp = DB::table('hpp')
            ->select('*')
            ->where('flag', 'RB')
            ->where('pengeluaranid', $id)
            ->get();

        // dd($hpp);

        $hppIds = $hpp->pluck('id')->toArray();

        // dd($hppIds, $hpp);

        $hppDelete = DB::table('hpp')
            ->select('*')
            ->where('hpp.pengeluaranid', '=', $id)
            ->where('flag', 'RB')
            ->first();

        // dd($hppDelete, $id);

        $fetchDataHpp = DB::table('hpp')
            ->select(
                'hpp.id',
                'hpp.pengeluaranid',
                DB::raw("CASE
                        WHEN hpp.flag = 'PJ' THEN penjualanheader.tglbukti
                        WHEN hpp.flag = 'RB' THEN returbeliheader.tglbukti
                        ELSE NULL
                    END AS tglbukti
                "),
                'hpp.flag',
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
            ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id');

        foreach ($hppIds as $ids) {
            $fetchDataHpp->where('hpp.id', '>', $ids);
        }

        $fetchDataHpp = $fetchDataHpp->get();

        // dd($fetchDataHpp);

        // dd($hppDelete);

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
            ->orderBy('pengeluaranid', 'asc')
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

            // dd($pembelian);

            if ($pembelian) {
                $qtyterpakai = $pembelian->qtyterpakai - $fetch->pengeluaranqty;
                $pembelianDetail = PembelianDetail::where('id', $fetch->penerimaandetailid)->first();
                $pembelianDetail->qtyterpakai = $qtyterpakai;
                $pembelianDetail->save();
            }
            // dd($pembelianDetail);

            $hpp = HPP::where('pengeluarandetailid', $fetch->pengeluarandetailid)->where('pengeluarannobukti', $fetch->pengeluarannobukti)->first();
            // dd($hpp);
            if ($hpp) {
                $hpp->delete();
            }

            $kartuStok = KartuStok::where('pengeluarandetailid', $fetch->pengeluarandetailid)->where('nobukti', $fetch->pengeluarannobukti)->first();
            // dd($kartuStok);
            if ($kartuStok) {
                $kartuStok->delete();
            }

            // dump($kartuStok, $hpp, $pembelian);
        }
        // die;

        $returBeliDetail = ReturBeliDetail::where('returbeliid', '=', $id)->get();
        // dd($returBeliDetail);
        $dataDetail = $returBeliDetail->toArray();

        foreach ($dataDetail as $detail) {
            $pembelianDetail  = PembelianDetail::where('id', $detail['pembeliandetailid'])->first();
            if ($pembelianDetail) {
                $pembelianDetail->qtyretur = 0;
                $pembelianDetail->save();

                // dd($pembelianDetail);
            }
        }
        // dd($dataDetail);
        // 

        /*DELETE EXISTING RETUR BELI HEADER*/
        $returBeliHeader = new ReturBeliHeader();
        // dd($returBeliHeader);
        $returBeliHeader = $returBeliHeader->lockAndDestroy($id);
        $returBeliHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $returBeliHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $returBeliHeader->id,
            'nobuktitrans' => $returBeliHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $returBeliHeader->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        // DELETE PIUTANG
        $getPiutang = DB::table("piutang")
            ->select('id', 'nobukti')
            ->where('penjualanid', '=', $id)
            ->first();
        $piutang = new Piutang();
        $piutang->processDestroy($getPiutang->id);

        (new LogTrail())->processStore([
            'namatabel' => 'RETURBELIDETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $returBeliHeaderLogTrail['id'],
            'nobuktitrans' => $returBeliHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->name
        ]);

        // dd('test');

        // CREATE ULANG HPP
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

            // dd('test');

            $dataHpp = [
                "pengeluaranid" => $row->pengeluaranid,
                "tglbukti" => $row->tglbukti,
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

        return $returBeliHeader;
    }

    public function cekValidasiAksi($pembelianid)
    {
        $pembelianHeader = DB::table('returbeliheader')
            ->from(
                DB::raw("returbeliheader as a")
            )
            ->select(
                'a.id',
                'a.nobukti'
            )
            ->where('a.flag', '=', 'GENERATED')
            ->where('a.id', '=', request()->id)
            ->first();

        if (isset($pembelianHeader)) {
            $data = [
                'kondisi' => true,
                'keterangan' => 'Pembelian ' . $pembelianHeader->nobukti,
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
