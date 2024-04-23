<?php

namespace App\Models;

use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HPP extends MyModel
{
    use HasFactory;

    protected $table = 'hpp';

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

        $aktif = request()->aktif ?? '';

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'hpp.id',
                'hpp.pengeluaranid',
                'hpp.tglbukti',
                'penjualanheader.nobukti as pengeluarannobukti',
                'hpp.pengeluarandetailid',
                'hpp.penerimaanid',
                'pembelianheader.nobukti as penerimaannobukti',
                'hpp.penerimaandetailid',
                'hpp.productid',
                'product.nama as productnama',
                'hpp.pengeluaranqty',
                'hpp.penerimaanharga',
                'hpp.pengeluaranharga',
                'hpp.penerimaantotal',
                'hpp.pengeluarantotal',
                'hpp.profit',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'hpp.modifiedby',
                'hpp.created_at',
                'hpp.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'hpp.status', '=', 'parameter.id')
            ->leftJoin('product', 'hpp.productid', 'product.id')
            ->leftJoin('penjualanheader', 'hpp.pengeluaranid', 'penjualanheader.id')
            ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id');

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'hpp.id',
                'hpp.pengeluaranid',
                'hpp.tglbukti',
                'hpp.pengeluarannobukti',
                'hpp.pengeluarandetailid',
                'hpp.penerimaanid',
                'pembelianheader.nobukti as penerimaannobukti',
                'hpp.penerimaandetailid',
                'hpp.productid',
                'product.nama as productnama',
                'hpp.pengeluaranqty',
                'hpp.penerimaanharga',
                'hpp.pengeluaranharga',
                'hpp.penerimaantotal',
                'hpp.pengeluarantotal',
                'hpp.profit',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'hpp.modifiedby',
                'hpp.created_at',
                'hpp.updated_at'
            )
            ->leftJoin('penjualanheader', function ($join) {
                $join->on('hpp.pengeluaranid', '=', 'penjualanheader.id')
                    ->where('hpp.flag', '=', 'PJ');
            })
            ->leftJoin('returbeliheader', function ($join) {
                $join->on('hpp.pengeluaranid', '=', 'returbeliheader.id')
                    ->where('hpp.flag', '=', 'RB');
            })
            ->leftJoin(DB::raw("parameter"), 'hpp.status', '=', 'parameter.id')
            ->leftJoin('product', 'hpp.productid', 'product.id')
            ->leftJoin('pembelianheader', 'hpp.penerimaanid', 'pembelianheader.id');

        if (request()->tgldari && request()->tglsampai) {
            $startDate = date('Y-m-d', strtotime(request()->tgldari));
            $endDate = date('Y-m-d', strtotime(request()->tglsampai));
            $query->whereRaw("DATE($this->table.tglbukti) BETWEEN '$startDate' AND '$endDate'");
        }

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();
            $query->where('hpp.status', '=', $status->id);
        }
        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        $this->sort($query);
        if (request()->limit > 0) {
            $this->paginate($query);
        }

        $data = $query->get();
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'tglbukti') {
            return $query->orderBy('tglbukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'pengeluarannobukti') {
            return $query->orderBy('pengeluarannobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'penerimaannobukti') {
            return $query->orderBy('penerimaannobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'productnama') {
            return $query->orderBy('product.nama', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'statusmemo') {
            return $query->orderBy('parameter.memo', $this->params['sortOrder']);
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
                        if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tgl') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.text', 'like', '%$filters[data]%');
                        } else if ($filters['field'] == 'pengeluarannobukti') {
                            // $query = $query->where('penjualanheader.nobukti', 'like', "%$filters[data]%");
                            $query->where(function ($query) use ($filters) {
                                $query->where('hpp.flag', '=', 'PJ')
                                    ->whereRaw('penjualanheader.nobukti LIKE ?', ['%' . $filters['data'] . '%']);
                            })
                                ->orWhere(function ($query) use ($filters) {
                                    $query->where('hpp.flag', '=', 'RB')
                                        ->whereRaw('returbeliheader.nobukti LIKE ?', ['%' . $filters['data'] . '%']);
                                });
                        } else if ($filters['field'] == 'penerimaannobukti') {
                            $query = $query->where('pembelianheader.nobukti', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->where('product.nama', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'tglbukti') {
                            $query->where('hpp.flag', '=', 'PJ')
                                ->whereRaw("DATE_FORMAT(penjualanheader.tglbukti, '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%'])
                                ->orWhere(function ($query) use ($filters) {
                                    $query->where('hpp.flag', '=', 'RB')
                                        ->whereRaw("DATE_FORMAT(returbeliheader.tglbukti, '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                                });
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    $query->where(function ($query) {
                        foreach ($this->params['filters']['rules'] as $index => $filters) {
                            if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                                $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                            } else if ($filters['field'] == 'pengeluarannobukti') {
                                // $query = $query->where('penjualanheader.nobukti', 'like', "%$filters[data]%");
                                $query->where(function ($query) use ($filters) {
                                    $query->where('hpp.flag', '=', 'PJ')
                                        ->whereRaw('penjualanheader.nobukti LIKE ?', ['%' . $filters['data'] . '%']);
                                })
                                    ->orWhere(function ($query) use ($filters) {
                                        $query->where('hpp.flag', '=', 'RB')
                                            ->whereRaw('returbeliheader.nobukti LIKE ?', ['%' . $filters['data'] . '%']);
                                    });
                            } else if ($filters['field'] == 'tglbukti') {
                                $query->where('hpp.flag', '=', 'PJ')
                                    ->whereRaw("DATE_FORMAT(penjualanheader.tglbukti, '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%'])
                                    ->orWhere(function ($query) use ($filters) {
                                        $query->where('hpp.flag', '=', 'RB')
                                            ->whereRaw("DATE_FORMAT(returbeliheader.tglbukti, '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                                    });
                            } else if ($filters['field'] == 'productnama') {
                                $query = $query->orWhere('product.nama', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'penerimaannobukti') {
                                $query = $query->orWhere('pembelianheader.nobukti', 'like', "%$filters[data]%");
                            } else if ($filters['field'] == 'statusmemo') {
                                $query = $query->OrwhereRaw('parameter.text', 'like', '%$filters[data]%');
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

    public function processStore(array $data)
    {
        // dump($data);
        $totalHarga = 0;

        $tempHpp = 'tempHpp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $tempHpp (
            id BIGINT,
            productid INT,
            penerimaanid VARCHAR(255),
            penerimaanqty FLOAT,
            penerimaanqtyterpakai FLOAT
        )");

        $temprekappengeluaranHpp = 'temprekappengeluaranHpp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $temprekappengeluaranHpp (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            urut INT,
            pengeluaranid INT,
            pengeluarandetailid INT,
            penerimaanid INT,
            penerimaandetailid INT,
            productid INT,
            pengeluaranqty FLOAT,
            penerimaanharga FLOAT, 
            pengeluaranharga FLOAT, 
            penerimaantotal FLOAT,
            pengeluarantotal FLOAT
        )");

        $pengeluaranid =  $data['pengeluaranid'];
        $productid = $data['productid'];

        $queryHpp = DB::table('hpp')->from(DB::raw('hpp'))
            ->select(
                'hpp.urut',
                'hpp.pengeluaranid',
                'hpp.pengeluarandetailid',
                'hpp.penerimaanid',
                'hpp.penerimaandetailid',
                'hpp.productid',
                'hpp.pengeluaranqty',
                'hpp.penerimaanharga',
                'hpp.pengeluaranharga',
                'hpp.penerimaantotal',
                'hpp.pengeluarantotal'
            )
            ->where('hpp.productid', $productid)
            ->where('hpp.pengeluaranid', $pengeluaranid)
            ->orderby('hpp.id', 'asc');

        // dd($queryHpp->get());
        // if ($data['pesananfinaldetailid'] == 101) {
        //     dd($data, $queryHpp->get());
        // }

        DB::table($temprekappengeluaranHpp)->insertUsing([
            "urut", "pengeluaranid", "pengeluarandetailid", "penerimaanid", "penerimaandetailid", "productid", "pengeluaranqty", "penerimaanharga", "pengeluaranharga", "penerimaantotal", "pengeluarantotal"
        ], $queryHpp);

        $a = 0;
        $totalTerpakai2 = 0;

        DB::table($tempHpp)->delete();

        $queryHpp = DB::table($temprekappengeluaranHpp)->from(DB::raw($temprekappengeluaranHpp . " a"))
            ->select(
                DB::raw("MAX(b.id) as id"),
                'a.productid',
                'a.penerimaanid',
                DB::raw("max(c.qty) as penerimaanqty"),
                DB::raw("ROUND(SUM(c.qtyterpakai),2) as penerimaanqtyterpakai")
            )
            ->join(DB::raw("pembelianheader b"), 'a.penerimaanid', 'b.id')
            ->join(DB::raw("pembeliandetail c"), function ($join) {
                $join->on('b.id', '=', 'c.pembelianid');
                $join->on('a.productid', '=', 'c.productid');
            })
            ->where('c.productid', '=', $data['productid'])
            ->groupBY('a.productid')
            ->groupBY('a.penerimaanid');

        DB::table($tempHpp)->insertUsing([
            "id", "productid", "penerimaanid",  "penerimaanqty", "penerimaanqtyterpakai"
        ], $queryHpp);

        $querySisa = DB::table('pembeliandetail')->from(DB::raw('pembeliandetail a'))
            ->select(
                DB::raw("((a.qty - a.qtyterpakai)-IFNULL(b.penerimaanqty,0)) as qtysisa"),
                'c.nobukti',
                'a.qty',
                'a.productid',
                'a.harga',
                'a.id as penerimaandetailid',
                'c.id as penerimaanid',
                'c.nobukti as penerimaannobukti',
                DB::raw("a.harga * a.qty as totalharga"),
                DB::raw("((a.harga * (a.qty- a.qtyterpakai))-IFNULL(b.penerimaanqtyterpakai,0)) as totalsisa"),
                DB::raw("IFNULL(a.qtyterpakai,0) as qtyterpakai"),
                DB::raw("IFNULL(a.qtyterpakai * a.harga,0) as totalterpakai")
            )
            ->leftjoin(db::raw($tempHpp . " b "), function ($join) {
                $join->on('a.pembelianid', '=', 'b.penerimaanid');
                $join->on('a.productid', '=', 'b.productid');
            })
            ->join(db::raw("pembelianheader c "), 'a.pembelianid', 'c.id')
            ->where('a.productid', '=',   $data['productid'])
            ->whereRaw("((a.qty - a.qtyterpakai) - IFNULL(b.penerimaanqty,0)) <> 0")
            ->orderBy('c.tglbukti', 'asc')
            ->orderBy('c.id', 'asc')
            ->orderBy('a.id', 'asc')
            ->get();

        // dump($querySisa);

        $a = $a + 1;
        $qtyjual = $data['qtypengeluaran'] ?? 0;
        $qtytemp = 0;
        $qtyterpakai = 0;
        // dd($qtyjual);

        foreach ($querySisa as $index => $value) {

            $qtysisa = $value->qtysisa;

            if ($qtyterpakai == 0) {
                if ($qtyjual <= $qtysisa) {
                    $qtyterpakai = $qtyjual;
                } else {
                    $qtyterpakai = $qtysisa;
                }
            } else {
                $qtyterpakai = $qtyjual - $qtyterpakai;
            }

            $totalSisa = round(($value->totalharga - $value->totalterpakai), 2);
            $hargatotalterpakai = ($totalSisa / $value->qtysisa);
            $totalTerpakai = round(($hargatotalterpakai * $value->qtyterpakai), 2);

            $totalTerpakai2 += $totalTerpakai;
            $hpp = new HPP();
            $hpp->pengeluaranid = $data['pengeluaranid'] ?? 0;
            $hpp->tglbukti = $data['tglbukti'] ?? '';
            $hpp->pengeluarannobukti = $data['pengeluarannobukti'] ?? 0;
            $hpp->pengeluarandetailid = $data['pengeluarandetailid'] ?? 0;
            $hpp->penerimaanid = $value->penerimaanid ?? 0;
            $hpp->penerimaandetailid = $value->penerimaandetailid ?? 0;
            $hpp->productid = $data['productid'] ?? 0;
            $hpp->urut = $a;
            $hpp->pengeluaranqty = $qtyterpakai ?? 0;
            $hpp->penerimaanharga = $value->harga ?? 0;
            $hpp->pengeluaranharga = $data['hargapengeluaranhpp'] ?? 0;
            $hpp->penerimaantotal = $value->harga * $qtyterpakai ?? 0;
            $hpp->pengeluarantotal = $data['hargapengeluaranhpp'] * $qtyterpakai ?? 0;
            $hpp->profit = ($data['hargapengeluaranhpp'] * $qtyterpakai) - ($value->harga * $qtyterpakai) ?? 0;
            $hpp->flag = $data['flag'] ?? "";
            $hpp->status = $data['status'] ?? 1;
            $hpp->modifiedby = $data['modifiedby'] ?? '';

            // dump($hpp);

            // DB::table($temprekappengeluaranHpp)->insert([
            //     'urut' => $a,
            //     'pengeluaranid' => $data['pengeluaranid'] ?? 0,
            //     'pengeluarandetailid' => $data['pengeluarandetailid'] ?? 0,
            //     'pengeluaranqty' => $qtysisa ?? 0,
            //     'penerimaanid' => $data['penerimaanid'] ?? 0,
            //     'penerimaandetailid' => $data['penerimaandetailid'] ?? 0,
            //     'productid' => $data['productid'] ?? 0,
            //     'penerimaanharga' => $value->harga ?? 0,
            //     'pengeluaranharga' => $data['hargapengeluaranpp'] ?? 0,
            //     'penerimaantotal' => $value->totalharga ?? 0,
            //     'pengeluarantotal' => $data['hargapengeluaranhpp'] * $qtyterpakai ?? 0,
            // ]);

            $penjualan = PenjualanHeader::where('id', $data['pengeluaranid'])->first();
            $returbeli = ReturBeliHeader::where('id', $data['pengeluaranid'])->first();

            // dd($returbeli);
            if ($hpp->flag == 'PJ') {
                $ksnobukti = $penjualan['nobukti'] ?? '';
            } else {
                $ksnobukti = $returbeli['nobukti'] ?? '';
            }

            $pengeluaranid = db::table("penjualanheader")->from(db::raw("penjualanheader as a"))
                ->select('a.id', 'a.tglbukti')->where('a.nobukti', $ksnobukti)->first();

            // dd($qtyterpakai);
            // dd($data);
            $kartuStok = (new KartuStok())->processStore([
                'tglbukti' => $data['tglbukti'],
                'penerimaandetailid' => 0,
                'pengeluarandetailid' => $data['pengeluarandetailid'] ?? 0,
                'nobukti' => $ksnobukti ?? '',
                'productid' => $data['productid'],
                'qtypenerimaan' => 0,
                'totalpenerimaan' => 0,
                'qtypengeluaran' => $qtyterpakai ?? 0,
                'totalpengeluaran' => $value->harga * $qtyterpakai ?? 0,
                'flag' => $data['flagkartustok'],
                'seqno' => $data['seqno'],
            ]);

            // dd($kartuStok);

            // dd($kartuStok);

            $aksqty = $value->qty ?? 0;
            $aksharga = round(($value->totalsisa / $value->qtysisa), 10) ?? 0;
            $totalHarga += round(($aksharga *  $aksqty), 2);

            if (!$hpp->save()) {
                throw new \Exception("Error storing hpp.");
            }

            $pembelianDetail = PembelianDetail::where('id', $value->penerimaandetailid)->first();
            // dd($pembelianDetail);

            if ($pembelianDetail) {
                $pembelianDetail->qtyterpakai = $pembelianDetail->qtyterpakai + $qtyterpakai;
                $pembelianDetail->save();
            }

            // dd($pembelianDetail);

            if ($qtytemp == 0) {
                if ($qtyjual <= $qtysisa) {
                    $qtytemp = $qtyjual;
                    break;
                } else {
                    $qtytemp = $qtysisa;
                    $a = $a + 1;
                }
            } else {
                $hitung = $qtyjual - $qtytemp;
                $qtytemp += $hitung;
                if ($qtytemp == $qtyjual) {
                    break;
                }
            }
            // dd($hpp);
        }
        // die;
        // dump($hpp);
        return $hpp;
    }
}
