<?php

namespace Database\Seeders;

use App\Models\AlatBayar;
use Illuminate\Database\Seeder;

class AlatBayarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AlatBayar::create(['nama' => 'TUNAI', 'keterangan' => 'Alat Bayar 1', 'bankid' => '4', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        AlatBayar::create(['nama' => 'BANK', 'keterangan' => 'Alat Bayar 2', 'bankid' => '1', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        AlatBayar::create(['nama' => 'GIRO', 'keterangan' => 'Alat Bayar 3', 'bankid' => '7', 'status' => '1', 'modifiedby' => 'ADMIN',]);
    }
}
