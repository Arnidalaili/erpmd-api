<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArmadaRequest;
use App\Http\Requests\UpdateArmadaRequest;
use App\Http\Requests\DestroyArmadaRequest;
use App\Http\Requests\EditingAtArmadaRequest;
use App\Http\Requests\RangeExportReportRequest;
use App\Http\Requests\StoreAclRequest;
use App\Models\Armada;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ArmadaController extends Controller
{
    /**
     * @ClassName 
     */
    public function index()
    {
        $armada = new Armada();
        return response([
            'data' => $armada->get(),
            'attributes' => [
                'totalRows' => $armada->totalRows,
                'totalPages' => $armada->totalPages
            ]
        ]);
    }

    public function default()
    {
        $armada = new Armada();
        return response([
            'status' => true,
            'data' => $armada->default(),
        ]);
    }

    public function show($id)
    {
        $owner = Armada::findAll($id);
        return response([
            'status' => true,
            'data' => $owner
        ]);
    }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('armada')->getColumns();
        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }
        return response([
            'data' => $data
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(StoreArmadaRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'jenisarmada' => $request->jenisarmada,
                'nopolisi' => $request->nopolisi,
                'namapemilik' => $request->namapemilik,
                'nostnk' => $request->nostnk,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];
            
            $armada = (new Armada())->processStore($data);
            $armada->position = $this->getPosition($armada, $armada->getTable())->position;
            if ($request->limit==0) {
                $armada->page = ceil($armada->position / (10));
            } else {
                $armada->page = ceil($armada->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $armada
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function update(UpdateArmadaRequest $request, Armada $armada)
    {
        DB::beginTransaction();

        try {
            $data = [
                'id' => $request->id,
                'nama' => $request->nama,
                'jenisarmada' => $request->jenisarmada,
                'nopolisi' => $request->nopolisi,
                'namapemilik' => $request->namapemilik,
                'nostnk' => $request->nostnk,
                'keterangan' => $request->keterangan,
                'status' => $request->status,
            ];

            $armada = (new armada())->processUpdate($armada, $data);
            $armada->position = $this->getPosition($armada, $armada->getTable())->position;
            if ($request->limit==0) {
                $armada->page = ceil($armada->position / (10));
            } else {
                $armada->page = ceil($armada->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah.',
                'data' => $armada
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyArmadaRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $armada = (new Armada())->processDestroy($id);
            $selected = $this->getPosition($armada, $armada->getTable(), true);
            $armada->position = $selected->position;
            $armada->id = $selected->id;
            if ($request->limit==0) {
                $armada->page = ceil($armada->position / (10));
            } else {
                $armada->page = ceil($armada->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $armada
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function import(Request $request)
    {
        $request->validate(
            [
                'fileImport' => 'required|file|mimes:xls,xlsx'
            ],
            [
                'fileImport.mimes' => 'file import ' . app(ErrorController::class)->geterror('FXLS')->keterangan,
            ]
        );

        $the_file = $request->file('fileImport');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->getActiveSheet();
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range(2, ($row_limit));
            $column_range = range('A', $column_limit);
            // $startcount = 4;
            $data = array();

            $a = 0;
            foreach ($row_range as $row) {

                $nama = $sheet->getCell($this->kolomexcel(1) . $row)->getValue();
                $jenisarmada = $sheet->getCell($this->kolomexcel(2) . $row)->getValue();
                $nopolisi = $sheet->getCell($this->kolomexcel(3) . $row)->getValue();
                $namapemilik = $sheet->getCell($this->kolomexcel(4) . $row)->getValue();
                $nostnk = $sheet->getCell($this->kolomexcel(5) . $row)->getValue();
                $keterangan = $sheet->getCell($this->kolomexcel(6) . $row)->getValue();
                $status = $sheet->getCell($this->kolomexcel(7) . $row)->getValue();

                $statusId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $status)
                    ->first()->id ?? 0;

                $jenisarmadaId =  DB::table('parameter')
                    ->select('id')
                    ->where('text', $jenisarmada)
                    ->first()->id ?? 0;

                $data = [
                    'nama' => $nama,
                    'jenisarmada' => $jenisarmadaId,
                    'nopolisi' => $nopolisi,
                    'namapemilik' => $namapemilik,
                    'nostnk' => $nostnk,
                    'keterangan' => $keterangan,
                    'status' => $statusId,
                    'modifiedby' => auth('api')->user()->name
                ];
                $customer = (new Armada())->processStore($data);
            }
            return response([
                'status' => true,
                'keterangan' => 'data di update',
                'data' => $customer,

            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    private function kolomexcel($kolom)
    {
        if ($kolom >= 27 and $kolom <= 52) {
            $hasil = 'A' . chr(38 + $kolom);
        } else {
            $hasil = chr(64 + $kolom);
        }
        return $hasil;
    }

    public function editingat(EditingAtArmadaRequest $request)
    {
        $armada = new Armada();
        return response([
            'data' => $armada->editingAt($request->id, $request->btn),
        ]);
    }
}
