<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PembelianDetail extends MyModel
{
    use HasFactory;

    protected $table = 'pembeliandetail';

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
        $query = DB::table($this->table . ' as pembeliandetail');

        $query->select(
            "pembeliandetail.id",
            "pembeliandetail.pembelianid as pembelianid",
            "pembelianheader.nobukti as pembelianheadernobukti",
            "pembeliandetail.productid as productid",
            "product.nama as productnama",
            "pembeliandetail.satuanid as satuanid",
            "satuan.nama as satuannama",
            // "pembeliandetail.pesananfinaldetailid",
            // "pembeliandetail.pesananfinalid as pesananfinalid",
            // "pesananfinalheader.nobukti as pesananfinalheadernobukti",
            "pembeliandetail.keterangan as keterangandetail",
            "pembeliandetail.qty",
            "pembeliandetail.qtyretur",
            "pembeliandetail.qtystok",
            "pembeliandetail.qtypesanan",
            "pembeliandetail.qtyterpakai",
            "pembeliandetail.harga as harga",
            DB::raw('(pembeliandetail.qty * pembeliandetail.harga) AS totalharga'),
            "modifier.id as modified_by_id",
            "modifier.name as modified_by",
            "pembeliandetail.created_at",
            "pembeliandetail.updated_at",
        )
            ->leftJoin(DB::raw("pembelianheader"), 'pembeliandetail.pembelianid', 'pembelianheader.id')
            ->leftJoin(DB::raw("product"), 'pembeliandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'pembeliandetail.satuanid', 'satuan.id')
            // ->leftJoin(DB::raw("pesananpembeliandetail"), 'pembeliandetail.id', 'pesananpembeliandetail.pembeliandetailid')
            ->leftJoin(DB::raw("user as modifier"), 'pembeliandetail.modifiedby', 'modifier.id');


        $query->where("pembeliandetail.pembelianid", "=", request()->pembelianid);
        $this->totalRows = $query->count();


        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->totalNominal = $query->sum(DB::raw('(pembeliandetail.qty * pembeliandetail.harga)'));

        // $query->groupBy('pembeliandetail.id');

        $this->sort($query);

        $this->filter($query);

        if (!request()->forReport) {
            $this->paginate($query);
        }

        $data = $query->get();

        // dd($data);


        return $data;
    }

    public function getAll($id)
    {
        $query = DB::table('pembeliandetail')
            ->select(
                DB::raw('MAX(pembeliandetail.id) as id'),
                DB::raw('MAX(pembeliandetail.pembelianid) as pembelianid'),
                DB::raw('MAX(pembelianheader.nobukti) as pembelianheadernobukti'),
                DB::raw('MAX(pembeliandetail.productid) as productid'),
                DB::raw('MAX(product.nama) as productnama'),
                DB::raw('MAX(pembeliandetail.satuanid) as satuanid'),
                DB::raw('MAX(satuan.nama) as satuannama'),
                DB::raw('MAX(IFNULL(pesananpembeliandetail.pesananfinaldetailid, 0)) as pesananfinaldetailid'),
                DB::raw('MAX(IFNULL(pesananpembeliandetail.pembeliandetailid, 0)) as pesananpembelianid'),
                DB::raw('MAX(IFNULL(pesananpembeliandetail.pesananfinalid, 0)) as pesananfinalid'),
                DB::raw('MAX(pembeliandetail.keterangan) as keterangandetail'),
                DB::raw('MAX(pesananfinaldetail.keterangan) as keterangancekpesanan'),
                DB::raw('MAX(pembeliandetail.qty) as qty'),
                DB::raw('MAX(pembeliandetail.qtyretur) as qtyretur'),
                DB::raw('MAX(pembeliandetail.qtystok) as qtystok'),
                DB::raw('MAX(pembeliandetail.qtypesanan) as qtypesanan'),
                DB::raw('MAX(pembeliandetail.qtyterpakai) as qtyterpakai'),
                DB::raw('MAX(pembeliandetail.harga) as harga'),
                DB::raw('MAX(pembeliandetail.qty * pembeliandetail.harga) as totalharga'),
                DB::raw('MAX(modifier.id) as modified_by_id'),
                DB::raw('MAX(modifier.name) as modified_by'),
                DB::raw('MAX(pembeliandetail.created_at) as created_at'),
                DB::raw('MAX(pembeliandetail.updated_at) as updated_at'),
                DB::raw('CASE WHEN MAX(pesananpembeliandetail.pesananfinaldetailid) IS NOT NULL THEN "0" ELSE "1" END AS ismanual')
            )
            ->leftJoin(DB::raw("pembelianheader"), 'pembeliandetail.pembelianid', 'pembelianheader.id')
            ->leftJoin(DB::raw("product"), 'pembeliandetail.productid', 'product.id')
            ->leftJoin(DB::raw("satuan"), 'pembeliandetail.satuanid', 'satuan.id')
            // ->leftJoin(DB::raw("pesananfinalheader"), 'pembeliandetail.pesananfinalid', 'pesananfinalheader.id')
            ->leftJoin(DB::raw("pesananpembeliandetail"), 'pembeliandetail.id', 'pesananpembeliandetail.pembeliandetailid')
            ->leftJoin(DB::raw("pesananfinaldetail"), 'pesananfinaldetail.id', 'pesananpembeliandetail.pesananfinaldetailid')
            ->leftJoin(DB::raw("user as modifier"), 'pembeliandetail.modifiedby', 'modifier.id')
            ->where('pembelianid', '=', $id)
            ->groupBy('pembeliandetail.id');

        $detailPembelian = $query->get();

        $queryGetKeterangan = DB::table('pembeliandetail')
            ->select(
                "pembeliandetail.id as pembeliandetailid",
                "pembeliandetail.pembelianid as pembelianid",
                "pesananpembeliandetail.pesananfinaldetailid",
                DB::raw('IFNULL(pesananfinaldetail.keterangan, "") as keterangancekpesanan'),
            )
            ->leftJoin(DB::raw("pesananpembeliandetail"), 'pembeliandetail.id', 'pesananpembeliandetail.pembeliandetailid')
            ->leftJoin(DB::raw("pesananfinaldetail"), 'pesananfinaldetail.id', 'pesananpembeliandetail.pesananfinaldetailid')
            ->leftJoin(DB::raw("user as modifier"), 'pembeliandetail.modifiedby', 'modifier.id')
            ->where('pembelianid', '=', $id)->get();

        $groupedDetails = $queryGetKeterangan->groupBy('pembeliandetailid');


        // Combine header and detail data
        $result = $detailPembelian->map(function ($header) use ($groupedDetails) {
            return [
                'id' => $header->id,
                'pembelianid' => $header->pembelianid,
                'pembelianheadernobukti' => $header->pembelianheadernobukti,
                'productid' => $header->productid,
                'productnama' => $header->productnama,
                'satuanid' => $header->satuanid,
                'satuannama' => $header->satuannama,
                'pesananfinaldetailid' => $header->pesananfinaldetailid,
                'pesananpembelianid' => $header->pesananpembelianid,
                'pesananfinalid' => $header->pesananfinalid,
                'qty' => $header->qty,
                'qtyretur' => $header->qtyretur,
                'qtystok' => $header->qtystok,
                'qtypesanan' => $header->qtypesanan,
                'qtyterpakai' => $header->qtyterpakai,
                'harga' => $header->harga,
                'totalharga' => $header->totalharga,
                'keterangan' => $header->keterangandetail,
                'modified_by_id' => $header->modified_by_id,
                'modified_by' => $header->modified_by,
                'created_at' => $header->created_at,
                'updated_at' => $header->updated_at,
                'ismanual' => $header->ismanual,
                'details' => $groupedDetails->get($header->id, []),
            ];
        });
        // Convert the result to an array
        $data = $result;

        // dd($data);
        return $data;
        // return $data;
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'type') {
                            $query = $query->where('B.grp', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("format(" . $this->table . "." . $filters['field'] . ", 'dd-MM-yyyy HH:mm:ss') LIKE '%$filters[data]%'");
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->where('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'totalharga') {
                            $query->whereRaw('(pembeliandetail.qty * pembeliandetail.harga) LIKE ?', ["%$filters[data]%"]);
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->where('satuan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'pesananfinalheadernobukti') {
                            $query = $query->where('pesananfinalheader.nobukti', 'like', "%$filters[data]%");
                        } else {
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
                            $query = $query->orWhereRaw('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'satuannama') {
                            $query = $query->orWhereRaw('satuan.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'totalharga') {
                            $query->orWhereRaw('(pembeliandetail.qty * pembeliandetail.harga) LIKE ?', ["%$filters[data]%"]);
                        } else if ($filters['field'] == 'pesananfinalheadernobukti') {
                            $query = $query->orWhereRaw('pesananfinalheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'keterangandetail') {
                            $query = $query->orWhereRaw('pembeliandetail.keterangan', 'like', "%$filters[data]%");
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

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('pembelianheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'productnama') {
            return $query->orderBy('product.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'keterangandetail') {
            return $query->orderBy('pembeliandetail.keterangan', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'satuannama') {
            return $query->orderBy('satuan.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'totalharga') {
            return $query->orderBy(DB::raw('(pembeliandetail.qty * pembeliandetail.harga)'), $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'pesananfinalheadernobukti') {
            return $query->orderBy(DB::raw('pesananfinalheader.nobukti'), $this->params['sortOrder']);
        } else {
            return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
        }
    }

    public function paginate($query)
    {
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }

    public function processStore(PembelianHeader $pembelianHeader, array $data): PembelianDetail
    {
        $ismanual = $data['ismanual'] ?? 0;
        if ($ismanual == 0) {
            $product = array_keys($data['productid']);
            $productInfo = reset($product);
        } else {
            $productInfo = $data['productid'];
        }

        $pembelianDetail = new PembelianDetail();
        $pembelianDetail->pembelianid = $data['pembelianid'];
        $pembelianDetail->productid = $productInfo;
        $pembelianDetail->satuanid = $data['satuanid'];
        $pembelianDetail->keterangan = $data['keterangan'];
        $pembelianDetail->qty = $data['qty'];
        $pembelianDetail->qtyretur = $data['qtyretur'];
        $pembelianDetail->qtystok = $data['qtystok'];
        $pembelianDetail->qtypesanan = $data['qtypesanan'];
        $pembelianDetail->qtyterpakai = $data['qtyterpakai'];
        $pembelianDetail->harga = $data['harga'];
        $pembelianDetail->modifiedby = $data['modifiedby'];
        $pembelianDetail->save();

        if (!$pembelianDetail->save()) {
            throw new \Exception("Error storing Pembelian Detail.");
        }

        if ($ismanual == 0) {
            $pesananDetails = [];
            foreach ($data['productid'] as $productId => $productInfo) {
                $pesananfinalids = $productInfo['pesananfinalid'];
                $pesananfinaldetailids = $productInfo['pesananfinaldetailid'];
                for ($i = 0; $i < count($pesananfinalids); $i++) {
                    $pesananDetail = (new PesananPembelianDetail())->processStore($pembelianDetail, [
                        'pembeliandetailid' => $pembelianDetail->id,
                        'pesananfinalid' => $pesananfinalids[$i] ?? 0,
                        'pesananfinaldetailid' => $pesananfinaldetailids[$i] ?? 0,
                        'productid' => $pembelianDetail->productid,
                        'satuanid' => $pembelianDetail->satuanid,
                        'keterangan' => $pembelianDetail->keterangan ?? '',
                        'qty' => $pembelianDetail->qty ?? 0,
                        'harga' => $pembelianDetail->harga ?? 0,
                        'modifiedby' => auth('api')->user()->id,
                    ]);
                    $pesananDetails[] = $pesananDetail->toArray();

                    $pesananfinaldetail = PesananFinalDetail::where('id', $pesananDetail->pesananfinaldetailid)->first();
                    if ($pesananfinaldetail) {
                        $pesananfinaldetail->nobuktipembelian = $pembelianHeader->nobukti;
                        $pesananfinaldetail->save();
                    }
                }
            }
        }
        return $pembelianDetail;
    }

    public function processStoreNew(PembelianHeader $pembelianHeader, array $data): PembelianDetail
    {
        // dd($data);
        $ismanual = $data['ismanual'] ?? 0;
        if ($ismanual == 0) {
            $product = array_keys($data['productid']);
            $productInfo = reset($product);
        } else {
            $productInfo = $data['productid'];
        }

        $pembelianDetail = new PembelianDetail();


        if ($ismanual == 0) {
            $pesananDetails = [];
            foreach ([$data['productid']] as $productId => $productInfo) {

                $pesananfinalids = $productInfo['pesananfinalid'];
                $pesananfinaldetailids = $productInfo['pesananfinaldetailid'];
                for ($i = 0; $i < count($pesananfinalids); $i++) {
                    $pesananDetail = (new PesananPembelianDetail())->processStore($pembelianDetail, [
                        'pembeliandetailid' => $pembelianDetail->id,
                        'pesananfinalid' => $pesananfinalids[$i] ?? 0,
                        'pesananfinaldetailid' => $pesananfinaldetailids[$i] ?? 0,
                        'productid' => $pembelianDetail->productid,
                        'satuanid' => $pembelianDetail->satuanid,
                        'keterangan' => $pembelianDetail->keterangan ?? '',
                        'qty' => $pembelianDetail->qty ?? 0,
                        'harga' => $pembelianDetail->harga ?? 0,
                        'modifiedby' => auth('api')->user()->id,
                    ]);
                    $pesananDetails[] = $pesananDetail->toArray();

                    $pesananfinaldetail = PesananFinalDetail::where('id', $pesananDetail->pesananfinaldetailid)->first();
                    if ($pesananfinaldetail) {
                        $pesananfinaldetail->nobuktipembelian = $pembelianHeader->nobukti;
                        $pesananfinaldetail->save();
                    }
                }
            }
        }

        // dd($pembelianDetail);
        return $pembelianDetail;
    }
}
