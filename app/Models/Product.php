<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends MyModel
{
    use HasFactory;

    protected $table = 'product';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function get()
    {
        $this->setRequestParameters();

        $aktif = request()->aktif ?? '';
        $customerid = request()->customerid ?? '';
        $pesananFinal = request()->pesananFinal ?? '';
        // $supplierid = request()->supplierid ?? '';
        $tglpengiriman = request()->tglpengiriman ?? '';

        $getCust = DB::table("customer")->where('id', $customerid)->first();
        if ($getCust) {
            $getParam = DB::table("parameter")->where('id', $getCust->hargaproduct)->first();
            $ketHarga = strtolower(str_replace(' ', '', $getParam->text));
        } else {
            $ketHarga = '';
        }

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'product.id',
                'product.nama',
                'groupproduct.id as groupid',
                'groupproduct.nama as groupnama',
                'supplier.id as supplierid',
                'supplier.nama as suppliernama',
                'satuan.id as satuanid',
                'satuan.nama as satuannama',
                'product.keterangan',
                'product.hargabeli',
                'product.hargakontrak1',
                'product.hargakontrak2',
                'product.hargakontrak3',
                'product.hargakontrak4',
                'product.hargakontrak5',
                'product.hargakontrak6',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'product.modifiedby',
                'product.created_at',
                'product.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'product.status', 'parameter.id')
            ->leftJoin(DB::raw("groupproduct"), 'product.groupid', 'groupproduct.id')
            ->leftJoin(DB::raw("supplier"), 'product.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("satuan"), 'product.satuanid', 'satuan.id');

        $ids = $query->get()->pluck('id')->toArray();

        $tempStokSubquery = $this->createTempKartuStok($ids);
        $tempStokArray = $tempStokSubquery->pluck('qtysaldo', 'productid')->toArray();

        if ($pesananFinal != '') {
            $tglpengiriman = date("Y-m-d", strtotime($tglpengiriman));
            $query->join(DB::raw("pesananfinaldetail"), 'pesananfinaldetail.productid', 'product.id');
            $query->leftJoin("pesananfinalheader", 'pesananfinalheader.id', 'pesananfinaldetail.pesananfinalid');
            $query->where('pesananfinalheader.tglpengiriman', $tglpengiriman);
            $query->distinct();
        }

        if ($customerid != '') {
            $query->addSelect("product.$ketHarga as hargajual");
        } else {
            $query->addSelect("product.hargajual");
        }

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();
            $query->where('product.status', '=', $status->id);
        }

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);

        if (request()->limit > 0) {
            $this->paginate($query);
        }

        $query = $query->get()->map(function ($item) use ($tempStokArray) {
            if (isset($tempStokArray[$item->id])) {
                $item->qtystok = $tempStokArray[$item->id];
            } else {
                $item->qtystok = 0.0;
            }
            return $item;
        });

        // dd($query);

        return $query;
    }

    public function createTempKartuStok($ids)
    {
        $tempGetStok = 'tempgetstok' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));

        DB::statement("CREATE TEMPORARY TABLE $tempGetStok (
            productid INT UNSIGNED
        )");

        foreach ($ids as $id) {
            DB::table($tempGetStok)->insert(['productid' => $id]);
        }

        $kartuStok = DB::table($tempGetStok)
            ->leftJoin('kartustok', function ($join) use ($tempGetStok) {
                $join->on("$tempGetStok.productid", '=', 'kartustok.productid')
                    ->whereRaw('kartustok.id = (SELECT MAX(id) FROM kartustok WHERE productid = ' . $tempGetStok . '.productid)');
            })
            ->select("$tempGetStok.productid", DB::raw('COALESCE(SUM(kartustok.qtysaldo), 0) as qtysaldo'))
            ->groupBy("$tempGetStok.productid")
            ->get();

        return $kartuStok;
    }

    public function findAll($id)
    {
        $query = DB::table('product')
            ->select(
                'product.id',
                'product.nama',
                'groupproduct.id as groupid',
                'groupproduct.nama as groupnama',
                'supplier.id as supplierid',
                'supplier.nama as suppliernama',
                'satuan.id as satuanid',
                'satuan.nama as satuannama',
                'product.keterangan',
                'product.hargajual',
                'product.hargabeli',
                'product.hargakontrak1',
                'product.hargakontrak2',
                'product.hargakontrak3',
                'product.hargakontrak4',
                'product.hargakontrak5',
                'product.hargakontrak6',
                'parameter.id as status',
                'parameter.text as statusnama',
                'product.modifiedby',
                'product.created_at',
                'product.updated_at',
            )
            ->leftJoin(DB::raw("parameter"), 'product.status', '=', 'parameter.id')
            ->leftJoin(DB::raw("groupproduct"), 'product.groupid', '=', 'groupproduct.id')
            ->leftJoin(DB::raw("supplier"), 'product.supplierid', '=', 'supplier.id')
            ->leftJoin(DB::raw("satuan"), 'product.satuanid', '=', 'satuan.id')
            ->where('product.id', $id);

        $data = $query->first();
        return $data;
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

    public function selectColumns($query)
    {
        return $query->from(
            DB::raw($this->table)
        )
            ->select(
                DB::raw("
                $this->table.id,
                $this->table.nama,
                groupproduct.id as groupid,
                groupproduct.nama as groupnama,
                supplier.id as supplierid,
                supplier.nama as suppliernama,
                satuan.id as satuanid,
                satuan.nama as satuannama,
                $this->table.keterangan,
                $this->table.hargabeli,
                $this->table.hargajual,
                $this->table.hargakontrak1,
                $this->table.hargakontrak2,
                $this->table.hargakontrak3,
                $this->table.hargakontrak4,
                $this->table.hargakontrak5,
                $this->table.hargakontrak6,
                parameter.id as status,
                parameter.memo as statusmemo,
                $this->table.modifiedby,
                $this->table.created_at,
                $this->table.updated_at
            ")
            )
            ->leftJoin(DB::raw("parameter"), 'product.status', 'parameter.id')
            ->leftJoin(DB::raw("groupproduct"), 'product.groupid', 'groupproduct.id')
            ->leftJoin(DB::raw("supplier"), 'product.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("satuan"), 'product.satuanid', 'satuan.id');
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
                id BIGINT NULL,
                nama VARCHAR(100) NULL,
                groupid VARCHAR(100) NULL,
                groupnama VARCHAR(100) NULL,
                supplierid VARCHAR(100) NULL,
                suppliernama VARCHAR(100) NULL,
                satuanid VARCHAR(100) NULL,
                satuannama VARCHAR(100) NULL,
                keterangan VARCHAR(255) NULL,
                hargabeli FLOAT NULL,
                hargajual FLOAT NULL,
                hargakontrak1 FLOAT NULL,
                hargakontrak2 FLOAT NULL,
                hargakontrak3 FLOAT NULL,
                hargakontrak4 FLOAT NULL,
                hargakontrak5 FLOAT NULL,
                hargakontrak6 FLOAT NULL,
                status VARCHAR(100) NULL,
                statusmemo VARCHAR(100) NULL,
                modifiedby VARCHAR(100) NULL DEFAULT '',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                position INT AUTO_INCREMENT PRIMARY KEY
            )
        ");

        DB::table($temp)->insertUsing(["id", "nama", "groupid", "groupnama", "supplierid", "suppliernama", "satuanid", "satuannama", "keterangan", "hargabeli", "hargajual", "hargakontrak1", "hargakontrak2", "hargakontrak3", "hargakontrak4", "hargakontrak5", "hargakontrak6", "status", "statusmemo", "modifiedby", "created_at", "updated_at"], $query);

        return $temp;
    }

    public function sort($query)
    {
        return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', '=', "$filters[data]");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'groupnama') {
                            $query = $query->where('groupproduct.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'suppliernama') {
                            $query = $query->where('supplier.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->where('satuan.nama', 'like', "%$filters[data]%");
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }
                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'statusmemo') {
                                $query = $query->orWhere('parameter.text', '=', "$filters[data]");
                            } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else if ($filters['field'] == 'groupnama') {
                                $query = $query->orWhere('groupproduct.nama', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'suppliernama') {
                                $query = $query->orWhere('supplier.nama', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'satuannama') {
                                $query = $query->orWhere('satuan.nama', 'like', "%$filters[data]%");
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

    public function processStore(array $data): Product
    {
        $product = new Product();
        $product->nama = $data['nama'] ?? '';
        // $product->groupid = $data['groupid'] ?? 0;
        $product->supplierid = $data['supplierid'] ?? 0;
        $product->satuanid = $data['satuanid'] ?? 0;
        $product->keterangan = $data['keterangan'] ?? '';
        $product->hargajual = $data['hargajual'] ?? '';
        $product->hargabeli = $data['hargabeli'] ?? '';
        $product->hargakontrak1 = $data['hargakontrak1'] ?? 0;
        $product->hargakontrak2 = $data['hargakontrak2'] ?? 0;
        $product->hargakontrak3 = $data['hargakontrak3'] ?? 0;
        $product->hargakontrak4 = $data['hargakontrak4'] ?? 0;
        $product->hargakontrak5 = $data['hargakontrak5'] ?? 0;
        $product->hargakontrak6 = $data['hargakontrak6'] ?? 0;
        $product->status = $data['status'];
        $product->modifiedby = auth('api')->user()->name;

        // dd($product);
        if (!$product->save()) {
            throw new \Exception('Error storing Product.');
        }
        (new LogTrail())->processStore([
            'namatabel' => strtoupper($product->getTable()),
            'postingdari' => 'ENTRY PRODUCT',
            'idtrans' => $product->id,
            'nobuktitrans' => $product->id,
            'aksi' => 'ENTRY',
            'datajson' => $product->toArray(),
            'modifiedby' => $product->modifiedby
        ]);
        return $product;
    }

    public function processUpdate(Product $product, array $data): Product
    {
        $product->nama = $data['nama'] ?? '';
        // $product->groupid = $data['groupid'] ?? '';
        $product->supplierid = $data['supplierid'] ?? '';
        $product->satuanid = $data['satuanid'] ?? '';
        $product->keterangan = $data['keterangan'] ?? '';
        $product->hargajual = $data['hargajual'] ?? '';
        $product->hargabeli = $data['hargabeli'] ?? '';
        $product->hargakontrak1 = $data['hargakontrak1'] ?? '';
        $product->hargakontrak2 = $data['hargakontrak2'] ?? '';
        $product->hargakontrak3 = $data['hargakontrak3'] ?? '';
        $product->hargakontrak4 = $data['hargakontrak4'] ?? '';
        $product->hargakontrak5 = $data['hargakontrak5'] ?? '';
        $product->hargakontrak6 = $data['hargakontrak6'] ?? '';
        $product->status = $data['status'];
        $product->modifiedby = auth('api')->user()->name;

        if (!$product->save()) {
            throw new \Exception('Error updating Product');
        }

        $pesananFinalDetails = PesananFinalDetail::where('productid', $product->id)->get();

        $idHeaders = [];
        foreach ($pesananFinalDetails as $pesananFinalDetail) {
            $today = date('Y-m-d');
            $pesananFinal = PesananFinalHeader::find($pesananFinalDetail->pesananfinalid);

            if ($pesananFinal->nobuktipenjualan == '' && $pesananFinal->tglpengiriman == $today) {
                if (!in_array($pesananFinalDetail->pesananfinalid, $idHeaders)) {
                    $idHeaders[] = $pesananFinalDetail->pesananfinalid;
                }

                $pesananFinalDetail->hargajual = $product->hargajual;
                $pesananFinalDetail->hargabeli = $product->hargabeli;

                if (!$pesananFinalDetail->save()) {
                    throw new \Exception('Error updating to pesanan final');
                }
            }
        }
        // dd($pesananFinal);
        // dd($idHeaders);

        $this->updateHeaderPesananFinal($idHeaders);

        (new LogTrail())->processStore([
            'namatabel' => $product->getTable(),
            'postingdari' => 'EDIT PRODUCT',
            'idtrans' => $product->id,
            'nobuktitrans' => $product->id,
            'aksi' => 'EDIT',
            'datajson' => $product->toArray(),
            'modifiedby' => $product->modifiedby
        ]);
        return $product;
    }

    public function updateHeaderPesananFinal($idHeader)
    {
        for ($i = 0; $i < count($idHeader); $i++) {
            $pesananFinal = PesananFinalHeader::find($idHeader[$i]);


            $pesananFinalDetails = PesananFinalDetail::where('pesananfinalid', $pesananFinal->id)->get();
            $tax = DB::table("parameter")
                ->select('id', 'text')
                ->where('grp', '=', 'tax')
                ->where('subgrp', '=', 'tax')
                ->first();

            $taxText = $tax->text;

            $subTotal = 0;
            foreach ($pesananFinalDetails as $pesananFinalDetail) {
                $totalHarga = strval($pesananFinalDetail->qtyjual) * strval($pesananFinalDetail->hargajual);
                $subTotal += $totalHarga;
            }

            $taxAmount = ($taxText / 100) * $subTotal;
            $total = $taxAmount + $subTotal;

            $pesananFinal->subtotal = $subTotal;
            $pesananFinal->taxAmount = $taxAmount;
            $pesananFinal->tax = $taxText;
            $pesananFinal->total = $total;

            if (!$pesananFinal->save()) {
                throw new \Exception('Error updating pesanan final');
            }
        }
    }

    public function processDestroy($id): Product
    {
        $product = new Product();
        $product = $product->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($product->getTable()),
            'postingdari' => 'DELETE PRODUCT',
            'idtrans' => $product->id,
            'nobuktitrans' => $product->id,
            'aksi' => 'DELETE',
            'datajson' => $product->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        return $product;
    }

    public function editingAt($id, $btn)
    {
        $product = Product::find($id);
        if ($btn == 'EDIT') {
            $product->editingby = auth('api')->user()->name;
            $product->editingat = date('Y-m-d H:i:s');
        } else {

            if ($product->editingby == auth('api')->user()->name) {
                $product->editingby = '';
                $product->editingat = null;
            }
        }
        if (!$product->save()) {
            throw new \Exception("Error Update product.");
        }

        return $product;
    }

    public function getAllProduct()
    {
        $this->setRequestParameters();

        $aktif = request()->aktif ?? '';

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'product.id',
                'product.nama',
                'groupproduct.id as groupid',
                'groupproduct.nama as groupnama',
                'supplier.id as supplierid',
                'supplier.nama as suppliernama',
                'satuan.id as satuanid',
                'satuan.nama as satuannama',
                'product.keterangan',
                'product.hargabeli',
                'product.hargajual',
                'product.hargakontrak1',
                'product.hargakontrak2',
                'product.hargakontrak3',
                'product.hargakontrak4',
                'product.hargakontrak5',
                'product.hargakontrak6',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'product.modifiedby',
                'product.created_at',
                'product.updated_at',
                'pesananfinalheader.tglpengiriman',
                DB::raw('CASE WHEN pesananfinaldetail.id IS NOT NULL THEN "ada" ELSE "tidakada"
                        END AS pesananfinaldetailid'),
                DB::raw('CASE WHEN penjualandetail.id IS NOT NULL THEN "ada" ELSE "tidakada"
                        END AS penjualandetailid'),
                DB::raw('CASE WHEN pembeliandetail.id IS NOT NULL THEN "ada" ELSE "tidakada"
                        END AS pembeliandetailid'),

            )
            ->leftJoin(DB::raw("parameter"), 'product.status', 'parameter.id')
            ->leftJoin(DB::raw("groupproduct"), 'product.groupid', 'groupproduct.id')
            ->leftJoin(DB::raw("supplier"), 'product.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("satuan"), 'product.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("pesananfinaldetail"), 'pesananfinaldetail.productid', 'product.id')
            ->leftJoin(DB::raw("pesananfinalheader"), 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("penjualandetail"), 'penjualandetail.productid', 'product.id')
            ->leftJoin(DB::raw("pembeliandetail"), 'pembeliandetail.productid', 'product.id')
            ->distinct();



        if (request()->tglpengiriman) {
            $query->where('pesananfinalheader.tglpengiriman', '=', date('Y-m-d', strtotime(request()->tglpengiriman)));
        }
        // dd($query->get()->count());
        // dd($query->count());


        $queryCount = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'product.id',
                'product.nama',
                'groupproduct.id as groupid',
                'groupproduct.nama as groupnama',
                'supplier.id as supplierid',
                'supplier.nama as suppliernama',
                'satuan.id as satuanid',
                'satuan.nama as satuannama',
                'product.keterangan',
                'product.hargabeli',
                'product.hargajual',
                'product.hargakontrak1',
                'product.hargakontrak2',
                'product.hargakontrak3',
                'product.hargakontrak4',
                'product.hargakontrak5',
                'product.hargakontrak6',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'product.modifiedby',
                'product.created_at',
                'product.updated_at',
                'pesananfinalheader.tglpengiriman',

            )
            ->leftJoin(DB::raw("parameter"), 'product.status', 'parameter.id')
            ->leftJoin(DB::raw("groupproduct"), 'product.groupid', 'groupproduct.id')
            ->leftJoin(DB::raw("supplier"), 'product.supplierid', 'supplier.id')
            ->leftJoin(DB::raw("satuan"), 'product.satuanid', 'satuan.id')
            ->leftJoin(DB::raw("pesananfinaldetail"), 'pesananfinaldetail.productid', 'product.id')
            ->leftJoin(DB::raw("pesananfinalheader"), 'pesananfinaldetail.pesananfinalid', 'pesananfinalheader.id');



        $this->filter($query);



        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();
            $query->where('product.status', '=', $status->id);
        }


        if (request()->tglpengiriman) {
            $queryCount->where('pesananfinalheader.tglpengiriman', '=', date('Y-m-d', strtotime(request()->tglpengiriman)));
        }



        $this->totalRows = $this->filter($query)->get()->count();
        // dd( $this->totalRows );
        // $this->totalRows = $this->filter($queryCount)->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);


        if (request()->limit > 0) {
            $this->paginate($query);
        }

        $data = $query->get();

        return $data;
    }

    public function processEditAll($data)
    {
        for ($i = 0; $i < count($data['nama']); $i++) {
            $product = new Product();

            $product = Product::find($data['id'][$i]);


            $dataAll =  [
                "nama" => $data['nama'][$i],
                "supplierid" => $data['supplierid'][$i],
                "suppliernama" => $data['suppliernama'][$i],
                "satuanid" => $data['satuanid'][$i],
                "satuannama" => $data['satuannama'][$i],
                "hargabeli" => $data['hargabeli'][$i],
                "hargajual" => $data['hargajual'][$i],
                "fullfilled" => $data['fullfilled'][$i],

            ];


            if ($dataAll) {
                $product->nama = $dataAll['nama'] ?? '';
                // $product->groupid = $dataAll['groupid'] ?? '';
                $product->supplierid = $dataAll['supplierid'] ?? '';
                $product->satuanid = $dataAll['satuanid'] ?? '';
                $product->keterangan = $dataAll['keterangan'] ?? '';
                $product->hargajual = $dataAll['hargajual'] ?? '';
                $product->hargabeli = $dataAll['hargabeli'] ?? '';
                $product->modifiedby = auth('api')->user()->name;

                if (!$product->save()) {
                    throw new \Exception('Error updating Product');
                }

                $pesananFinalDetails = PesananFinalDetail::where('productid', $product->id)->get();



                $idHeaders = [];
                foreach ($pesananFinalDetails as $pesananFinalDetail) {
                    $today = date('Y-m-d');
                    $besok = date('Y-m-d', strtotime('+1 day'));
                    $pesananFinal = PesananFinalHeader::find($pesananFinalDetail->pesananfinalid);


                    if ($pesananFinal->nobuktipenjualan == '' && $pesananFinal->tglpengiriman == $today || $pesananFinal->tglpengiriman == $besok) {

                        if (!in_array($pesananFinalDetail->pesananfinalid, $idHeaders)) {
                            $tglpengiriman = date('d-m-Y', strtotime($pesananFinal->tglpengiriman));
                            $idHeaders[] = $pesananFinalDetail->pesananfinalid;
                        }

                        $updatedat = strtotime($pesananFinalDetail->updated_at->toDateTimeString());
                        $now = time();

                        if ($dataAll['fullfilled'] == 'hargajual') {
                            $pesananFinalDetail->hargajual = $product->hargajual;
                        } else if ($dataAll['fullfilled'] == 'hargabeli') {
                            $pesananFinalDetail->hargabeli = $product->hargabeli;
                        } else {
                            $pesananFinalDetail->hargajual = $product->hargajual;
                            $pesananFinalDetail->hargabeli = $product->hargabeli;
                        };


                        if (!$pesananFinalDetail->save()) {
                            throw new \Exception('Error updating to pesanan final');
                        }
                    }
                }


                $this->updateHeaderPesananFinal($idHeaders);

                (new LogTrail())->processStore([
                    'namatabel' => $product->getTable(),
                    'postingdari' => 'EDIT PRODUCT',
                    'idtrans' => $product->id,
                    'nobuktitrans' => $product->id,
                    'aksi' => 'EDIT',
                    'datajson' => $product->toArray(),
                    'modifiedby' => $product->modifiedby
                ]);
            }
        }


        return $tglpengiriman ?? '';
    }
}
