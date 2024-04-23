<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class KartuStok extends MyModel
{
    use HasFactory;

    protected $table = 'kartustok';

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
        // dd('ghghgh');
        $this->setRequestParameters();

        $aktif = request()->aktif ?? '';

        $query = DB::table($this->table)->from(DB::raw("$this->table"))
            ->select(
                'kartustok.id',
                'kartustok.tglbukti',
                'kartustok.nobukti',
                'kartustok.productid',
                'product.nama as productnama',
                'kartustok.qtypenerimaan',
                'kartustok.qtypengeluaran',
                'kartustok.totalpenerimaan',
                'kartustok.totalpengeluaran',
                'kartustok.qtysaldo',
                'kartustok.totalsaldo',
                'parameter.id as status',
                'parameter.memo as statusmemo',
                'user.name as modifiedby',
                'kartustok.created_at',
                'kartustok.updated_at',
                'kartustok.seqno',
                DB::raw("
                    CASE
                        WHEN kartustok.flag = 'J' THEN penjualanheader.customerid
                        WHEN kartustok.flag = 'B' THEN pembelianheader.supplierid
                        WHEN kartustok.flag = 'RJ' THEN returjualheader.customerid
                        WHEN kartustok.flag = 'RB' THEN returbeliheader.supplierid
                        ELSE NULL
                    END AS customer_supplierid
                "),
                DB::raw("
                    COALESCE(customer.nama, supplier.nama, supplierretur.nama, customerretur.nama) AS customer_suppliernama
                ")
            )
            ->leftJoin(DB::raw("parameter"), 'kartustok.status', '=', 'parameter.id')
            ->leftJoin('product', 'kartustok.productid', 'product.id')
            ->leftJoin('user', 'kartustok.modifiedby', 'user.id')
            ->leftJoin('pembelianheader', 'kartustok.nobukti', '=', 'pembelianheader.nobukti')
            ->leftJoin('penjualanheader', 'kartustok.nobukti', '=', 'penjualanheader.nobukti')
            ->leftJoin('returjualheader', 'kartustok.nobukti', '=', 'returjualheader.nobukti')
            ->leftJoin('returbeliheader', 'kartustok.nobukti', '=', 'returbeliheader.nobukti')
            ->leftJoin('supplier', 'pembelianheader.supplierid', '=', 'supplier.id')
            ->leftJoin('customer', 'penjualanheader.customerid', '=', 'customer.id')
            ->leftJoin('supplier as supplierretur', 'returbeliheader.supplierid', '=', 'supplierretur.id')
            ->leftJoin('customer as customerretur', 'returjualheader.customerid', '=', 'customerretur.id');

        if (request()->tgldari && request()->tglsampai) {
            $query->whereBetween($this->table . '.tglbukti', [date('Y-m-d', strtotime(request()->tgldari)), date('Y-m-d', strtotime(request()->tglsampai))]);
        }

        $this->filter($query);

        if ($aktif == 'AKTIF') {
            $status = Parameter::from(
                DB::raw("parameter")
            )
                ->where('grp', '=', 'STATUS')
                ->where('text', '=', 'AKTIF')
                ->first();

            $query->where('kartustok.status', '=', $status->id);
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
            return $query->orderBy('kartustok.tglbukti', $this->params['sortOrder'])
                ->orderBy('product.nama', 'ASC')->orderBy('kartustok.seqno', 'ASC');
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
                        } else if ($filters['field'] == 'productnama') {
                            $query = $query->where('product.nama', 'like', "%$filters[data]%");
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
                            } else if ($filters['field'] == 'statusmemo') {
                                $query = $query->OrwhereRaw('parameter.text', 'like', '%$filters[data]%');
                            } else if ($filters['field'] == 'productnama') {
                                $query = $query->OrwhereRaw('product.nama', 'like', "%$filters[data]%");
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

    public function processStore(array $data): KartuStok
    {
        // dd($data);
        $query = DB::table('kartustok')
            ->select(
                'nobukti',
                'tglbukti',
                'qtypenerimaan',
                'qtypengeluaran',
                'totalpenerimaan',
                'totalpengeluaran',
                'qtysaldo',
                'totalsaldo'
            )
            ->where('productid', $data['productid'])
            ->where('nobukti', $data['nobukti'])
            ->orderByDesc('id')
            ->first();
        // dd($query);


        if ($query != null) {
            // dd('ghgh');
            $qtypengeluaran = $query->qtypengeluaran +  $data['qtypengeluaran'];
            $totalpengeluaran = $query->totalpengeluaran + $data['totalpengeluaran'];
            $qtysaldo = $query->qtysaldo + $data['qtypenerimaan'] - $data['qtypengeluaran'];
            $totalsaldo = $query->totalsaldo + $data['totalpenerimaan'] - $data['totalpengeluaran'];

            $kartuStok = KartuStok::where('nobukti', $query->nobukti)->first();
            $kartuStok->qtypengeluaran = $qtypengeluaran;
            $kartuStok->totalpengeluaran = $totalpengeluaran;
            $kartuStok->qtysaldo = $qtysaldo;
            $kartuStok->totalsaldo = $totalsaldo;

            // dd($kartuStok);
            if (!$kartuStok->save()) {
                throw new \Exception("Error editing Kartu Stok.");
            }

            (new LogTrail())->processStore([
                'namatabel' => strtoupper($kartuStok->getTable()),
                'postingdari' =>  strtoupper('EDIT KARTU STOK'),
                'idtrans' =>  $kartuStok->id,
                'nobuktitrans' => $kartuStok->nobukti,
                'aksi' => 'ENTRY',
                'datajson' => $kartuStok,
                'modifiedby' => auth('api')->user()->user,
            ]);



            // dd($kartuStok);
        } else {
            // dd('vbvbvb');
            $fetch = DB::table('kartustok')
                ->select(
                    'nobukti',
                    'qtypenerimaan',
                    'qtypengeluaran',
                    'totalpenerimaan',
                    'totalpengeluaran',
                    'qtysaldo',
                    'totalsaldo'
                )
                ->where('productid', $data['productid'])
                ->orderByDesc('id')
                ->first();

            // dd($fetch);

            if ($fetch == null) {
                $qtysaldo = $data['qtypenerimaan'] - $data['qtypengeluaran'];
                $totalsaldo = $data['totalpenerimaan'] - $data['totalpengeluaran'];
            } else {
                $qtysaldo = $fetch->qtysaldo + $data['qtypenerimaan'] - $data['qtypengeluaran'];
                $totalsaldo = $fetch->totalsaldo + $data['totalpenerimaan'] - $data['totalpengeluaran'];
            }

            $kartuStok = new KartuStok();
            $kartuStok->tglbukti = $data['tglbukti'];
            $kartuStok->penerimaandetailid = $data['penerimaandetailid'];
            $kartuStok->pengeluarandetailid = $data['pengeluarandetailid'];
            $kartuStok->nobukti = $data['nobukti'];
            $kartuStok->productid = $data['productid'];
            $kartuStok->qtypenerimaan = $data['qtypenerimaan'];
            $kartuStok->totalpenerimaan = $data['totalpenerimaan'];
            $kartuStok->qtypengeluaran = $data['qtypengeluaran'];
            $kartuStok->totalpengeluaran = $data['totalpengeluaran'];
            $kartuStok->qtysaldo = $qtysaldo;
            $kartuStok->totalsaldo = $totalsaldo;
            $kartuStok->flag = $data['flag'];
            $kartuStok->seqno = $data['seqno'];
            $kartuStok->status = $data['status'] ?? 1;
            $kartuStok->modifiedby = auth('api')->user()->id;

            if (!$kartuStok->save()) {
                throw new \Exception("Error storing Kartu Stok.");
            }

            // dd($kartuStok);

            (new LogTrail())->processStore([
                'namatabel' => strtoupper($kartuStok->getTable()),
                'postingdari' =>  strtoupper('ENTRY KARTU STOK'),
                'idtrans' =>  $kartuStok->id,
                'nobuktitrans' => $kartuStok->nobukti,
                'aksi' => 'ENTRY',
                'datajson' => $kartuStok,
                'modifiedby' => auth('api')->user()->user,
            ]);
        }
        // dd($data);

        $tsc = DB::table('kartustok')
            ->select('*')
            ->where('productid', $data['productid'])
            ->get();
        // dd($tsc);

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

            // if($data['nobukti'] == 'RJ 0001/III/2024') {

            // dump($transaction->id, $transaction->flag, $qtySaldo, $totalSaldo);
            // }
        }
        // die;
        // $test = DB::table('kartustok')
        //     ->select('*')
        //     ->get();

        // dd($test);
        // dd($kartuStok);
        return $kartuStok;
    }
}
