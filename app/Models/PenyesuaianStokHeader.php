<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\RunningNumberService;
use Illuminate\Support\Facades\DB;

class PenyesuaianStokHeader extends MyModel
{
    use HasFactory;

    protected $table = 'penyesuaianstokheader';

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
        $query = DB::table($this->table . ' as penyesuaianstokheader')
            ->select(
                "penyesuaianstokheader.id",
                "penyesuaianstokheader.nobukti",
                "penyesuaianstokheader.tglbukti",
                "penyesuaianstokheader.keterangan",
                "penyesuaianstokheader.total",
                "penyesuaianstokheader.tglcetak",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'penyesuaianstokheader.created_at',
                'penyesuaianstokheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'penyesuaianstokheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'penyesuaianstokheader.modifiedby', 'modifier.id');

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
        $query = DB::table('penyesuaianstokheader')
            ->select(
                "penyesuaianstokheader.id",
                "penyesuaianstokheader.nobukti",
                "penyesuaianstokheader.tglbukti",
                "penyesuaianstokheader.keterangan",
                "penyesuaianstokheader.total",
                "penyesuaianstokheader.tglcetak",
                "parameter.id as status",
                "parameter.memo as statusmemo",
                'modifier.id as modifiedby',
                'modifier.name as modifiedby_name',
                'penyesuaianstokheader.created_at',
                'penyesuaianstokheader.updated_at'
            )
            ->leftJoin(DB::raw("parameter"), 'penyesuaianstokheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'penyesuaianstokheader.modifiedby', 'modifier.id')
            ->where('penyesuaianstokheader.id', $id);
        $data = $query->first();

        // dd($data);
        return $data;
    }

    public function sort($query)
    {
        if ($this->params['sortIndex'] == 'id') {
            return $query->orderBy('penyesuaianstokheader.nobukti', $this->params['sortOrder']);
        } else if ($this->params['sortIndex'] == 'nobukti') {
            return $query->orderByRaw("
            CASE 
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'II' THEN 1
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'III' THEN 2
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'IV' THEN 3
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'V' THEN 4
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'VI' THEN 5
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'VII' THEN 6
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'VIII' THEN 7
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'IX' THEN 8
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'X' THEN 9
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'XI' THEN 10
            WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', -2), '/', 1) = 'XII' THEN 11
            ELSE 0
        END DESC")
                ->orderBy(DB::raw("SUBSTRING_INDEX(penyesuaianstokheader.nobukti, '/', 1)"), $this->params['sortOrder']);
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
                        if ($filters['field'] == 'statusmemo') {
                            $query = $query->where('parameter.memo', 'like', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at' || $filters['field'] == 'tglterima' || $filters['field'] == 'tglbukti') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            $query = $query->whereRaw($this->table . "." .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'statusmemo') {
                            $query = $query->orWhere('parameter.memo', 'LIKE', "%$filters[data]%");
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
            ->leftJoin(DB::raw("parameter"), 'penyesuaianstokheader.status', 'parameter.id')
            ->leftJoin(DB::raw("user as modifier"), 'penyesuaianstokheader.modifiedby', 'modifier.id');
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
            keterangan VARCHAR(500),
            total FLOAT,
            status INT,
            statusnama VARCHAR(500),
            statusmemo VARCHAR(500),
            tglcetak DATETIME,
            modifiedby VARCHAR(255),
            modifiedby_name VARCHAR(255),
            created_at DATETIME,
            updated_at DATETIME,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");

        DB::table($temp)->insertUsing([
            "id", "tglbukti", "nobukti", "keterangan", "total", "status", "statusnama", "statusmemo",  "tglcetak", "modifiedby", "modifiedby_name",
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
            throw new \Exception("Error Update penyesuaian header.");
        }

        return $returJualHeader;
    }

    public function processData($data)
    {
        $productIds = [];
        $qtys = [];
        $hargas = [];
        $totalhargas = [];
        foreach ($data as $detail) {
            $productIds[] = $detail['productid'];
            $qtys[] = $detail['qty'];
            $totalhargas[] = $detail['totalharga'];
            $hargas[] = $detail['harga'];
        }


        $data = [
            "tglbukti" => request()->tglbukti,
            "keterangan" => request()->keterangan,
            "total" => request()->total,
            "productid" =>  $productIds,
            "harga" => $hargas,
            "totalharga" => $totalhargas,
            "qty" => $qtys,
        ];

        return $data;
    }

    public function processStore(array $data): PenyesuaianStokHeader
    {
        $PenyesuaianStokHeader = new PenyesuaianStokHeader();


        /*STORE HEADER*/
        $group = 'PENYESUAIAN STOK HEADER BUKTI';
        $subGroup = 'PENYESUAIAN STOK HEADER BUKTI';

        $format = DB::table('parameter')->where('grp', $group)->where('subgrp', $subGroup)->first();

        $tglbukti = date('Y-m-d', strtotime($data['tglbukti']));

        $PenyesuaianStokHeader->tglbukti = $tglbukti;
        $PenyesuaianStokHeader->keterangan = $data['keterangan'];
        $PenyesuaianStokHeader->total = $data['total'];
        $PenyesuaianStokHeader->status = $data['status'] ?? 1;

        $PenyesuaianStokHeader->modifiedby = auth('api')->user()->id;
        $PenyesuaianStokHeader->nobukti = (new RunningNumberService)->get($group, $subGroup, $PenyesuaianStokHeader->getTable(), date('Y-m-d', strtotime($tglbukti)));

        if (!$PenyesuaianStokHeader->save()) {
            throw new \Exception("Error storing faktur penjualan header.");
        }

        $PenyesuaianStokHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($PenyesuaianStokHeader->getTable()),
            'postingdari' => strtoupper('ENTRY PENYESUAIAN STOK HEADER'),
            'idtrans' => $PenyesuaianStokHeader->id,
            'nobuktitrans' => $PenyesuaianStokHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $PenyesuaianStokHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);

        for ($i = 0; $i < count($data['productid']); $i++) {
            $penyesuaianStokDetail = (new PenyesuaianStokDetail())->processStore($PenyesuaianStokHeader, [
                'penyesuaianstokid' => $PenyesuaianStokHeader->id,
                'productid' => $data['productid'][$i] ?? 0,
                'qty' => $data['qty'][$i] ?? 0,
                'harga' => $data['harga'][$i] ?? 0,
                'total' => $data['totalharga'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->user,
            ]);
        }

        (new LogTrail())->processStore([
            'namatabel' => 'penyesuaianstokdetail',
            'postingdari' =>  strtoupper('ENTRY penyesuaian stok detail'),
            'idtrans' =>  $PenyesuaianStokHeaderLogTrail->id,
            'nobuktitrans' => $PenyesuaianStokHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $penyesuaianStokDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);


        return $PenyesuaianStokHeader;
    }

    public function processUpdate(PenyesuaianStokHeader $PenyesuaianStokHeader, array $data): PenyesuaianStokHeader
    {

        $nobuktiOld = $PenyesuaianStokHeader->nobukti;

        $group = 'PENYESUAIAN STOK HEADER BUKTI';
        $subGroup = 'PENYESUAIAN STOK HEADER BUKTI';

        $PenyesuaianStokHeader->keterangan = $data['keterangan'];
        $PenyesuaianStokHeader->total = $data['total'] ?? 0;
        $PenyesuaianStokHeader->status = $data['status'] ?? 1;
        $PenyesuaianStokHeader->modifiedby = auth('api')->user()->id;

        if (!$PenyesuaianStokHeader->save()) {
            throw new \Exception("Error updating penyesuaian stok Header.");
        }

        $PenyesuaianStokHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => strtoupper($PenyesuaianStokHeader->getTable()),
            'postingdari' => strtoupper('EDIT PESANAN FINAL HEADER'),
            'idtrans' => $PenyesuaianStokHeader->id,
            'nobuktitrans' => $PenyesuaianStokHeader->nobukti,
            'aksi' => 'EDIT',
            'datajson' => $PenyesuaianStokHeader->toArray(),
            'modifiedby' => auth('api')->user()->user
        ]);


        /*DELETE EXISTING HUTANG*/
        $pesananfinalDetail = PenyesuaianStokDetail::where('penyesuaianstokid', $PenyesuaianStokHeader->id)->lockForUpdate()->delete();

        /* Store detail */
        $pesananDetails = [];

        for ($i = 0; $i < count($data['productid']); $i++) {
            $penyesuaianStokDetail = (new PenyesuaianStokDetail())->processStore($PenyesuaianStokHeader, [
                'penyesuaianstokid' => $PenyesuaianStokHeader->id,
                'productid' => $data['productid'][$i] ?? 0,
                'qty' => $data['qty'][$i] ?? 0,
                'harga' => $data['harga'][$i] ?? 0,
                'total' => $data['totalharga'][$i] ?? 0,
                'modifiedby' => auth('api')->user()->user,
            ]);
            $pesananDetails[] = $penyesuaianStokDetail->toArray();
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($PenyesuaianStokHeaderLogTrail->getTable()),
            'postingdari' =>  strtoupper('EDIT PESANAN FINAL HEADER'),
            'idtrans' =>  $PenyesuaianStokHeaderLogTrail->id,
            'nobuktitrans' => $PenyesuaianStokHeader->nobukti,
            'aksi' => 'ENTRY',
            'datajson' => $penyesuaianStokDetail,
            'modifiedby' => auth('api')->user()->user,
        ]);
        return $PenyesuaianStokHeader;
    }

    public function processDestroy($id, $postingDari = ''): PenyesuaianStokHeader
    {
        $penyesuaianStokHeader = PenyesuaianStokDetail::where('penyesuaianstokid', '=', $id)->get();
        $dataDetail = $penyesuaianStokHeader->toArray();

        /*DELETE EXISTING FAKTUR PENJUALAN HEADER*/
        $penyesuaianStokHeader = new PenyesuaianStokHeader();

        $penyesuaianStokHeader = $penyesuaianStokHeader->lockAndDestroy($id);



        $penyesuaianStokHeaderLogTrail = (new LogTrail())->processStore([
            'namatabel' => $penyesuaianStokHeader->getTable(),
            'postingdari' => $postingDari,
            'idtrans' => $penyesuaianStokHeader->id,
            'nobuktitrans' => $penyesuaianStokHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $penyesuaianStokHeader->toArray(),
            'modifiedby' => auth('api')->user()->name
        ]);

        (new LogTrail())->processStore([
            'namatabel' => 'PENJUALANDETAIL',
            'postingdari' => $postingDari,
            'idtrans' => $penyesuaianStokHeaderLogTrail['id'],
            'nobuktitrans' => $penyesuaianStokHeader->nobukti,
            'aksi' => 'DELETE',
            'datajson' => $dataDetail,
            'modifiedby' => auth('api')->user()->name
        ]);
        return $penyesuaianStokHeader;
    }
}
