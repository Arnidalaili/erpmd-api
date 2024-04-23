<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dashboard extends MyModel
{
    use HasFactory;

    public function get()
    {
        $aktif = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'STATUS')->where("text", 'AKTIF')->first();
        $nonAktif = DB::table("parameter")->from(DB::raw("parameter"))->where('grp', 'STATUS')->where("text", 'NON AKTIF')->first();

        $product = DB::table("product")->from(DB::raw("product"))->where('status', $aktif->id)->count();
        // $tradoNonAktif = DB::table("trado")->from(DB::raw("trado"))->where('statusaktif', $nonAktif->id)->count();
        // $supirAktif = DB::table("supir")->from(DB::raw("supir"))->where('statusaktif', $aktif->id)->count();
        // $supirNonAktif = DB::table("supir")->from(DB::raw("supir"))->where('statusaktif', $nonAktif->id)->count();

        $data = [
            'product' => $product,
            // 'tradononaktif' => $tradoNonAktif,
            // 'supiraktif' => $supirAktif,
            // 'supirnonaktif' => $supirNonAktif,
        ];
        return $data;
    }
}
