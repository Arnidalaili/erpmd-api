<?php

namespace App\Services;

use App\Helpers\App;
use Illuminate\Support\Facades\DB;

class RunningNumberService
{
    public function get(string $group, string $subGroup, string $table, string $tgl): string
    {
        $parameter = DB::table('parameter')
            ->select(
                DB::raw(
                    "parameter.id,
                    parameter.text,
                    IFNULL(type.text,'') as type"
                )

            )
            ->leftJoin('parameter as type', 'parameter.type', 'type.id')
            ->where('parameter.grp', $group)
            ->where('parameter.subgrp', $subGroup)
            ->first();

        if (!isset($parameter->text)) {
            return response([
                'status' => false,
                'message' => 'Parameter tidak ditemukan'
            ]);
        }
        $bulan = date('n', strtotime($tgl));
        $tahun = date('Y', strtotime($tgl));

        $statusformat = $parameter->id;
        $text = $parameter->text;
        $type = $parameter->type;

        if ($type == 'RESET BULAN') {
            $lastRow = DB::table($table)
                ->where(DB::raw('month(tglbukti)'), '=', $bulan)
                ->where(DB::raw('year(tglbukti)'), '=', $tahun)
                ->lockForUpdate()->count();
        }
        if ($type == 'RESET TAHUN') {
            $lastRow = DB::table($table)
                ->where(DB::raw('year(tglbukti)'), '=', $tahun)
                ->where(DB::raw('statusformat'), '=', $statusformat)
                ->lockForUpdate()->count();
        }
        if ($type == '') {

            $lastRow = DB::table($table)
                ->lockForUpdate()->count();
        }

        $runningNumber = (new App)->runningNumber($text, $lastRow, $bulan);

        $nilai = 0;
        $nomor = $lastRow;
        while ($nilai < 1) {
            $cekbukti = DB::table($table)
                ->where(DB::raw('nobukti'), '=', $runningNumber)
                ->first();
            if (!isset($cekbukti)) {
                $nilai++;
                break;
            }
            $nomor++;
            $runningNumber = (new App)->runningNumber($text, $nomor, $bulan);
        }


        return $runningNumber;
    }
}
