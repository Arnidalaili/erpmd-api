<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Bank::create(['nama' => 'BCA', 'tipebank' => '8', 'keterangan' => 'Bank 1', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        Bank::create(['nama' => 'BCA 2', 'tipebank' => '8', 'keterangan' => 'Bank 2', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        Bank::create(['nama' => 'BCA 3', 'tipebank' => '8', 'keterangan' => 'Bank 3', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        Bank::create(['nama' => 'KAS', 'tipebank' => '9', 'keterangan' => 'Kas 1', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        Bank::create(['nama' => 'KAS 2', 'tipebank' => '9', 'keterangan' => 'Kas 2', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        Bank::create(['nama' => 'KAS 3', 'tipebank' => '9', 'keterangan' => 'Kas 3', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        Bank::create(['nama' => 'GIRO', 'tipebank' => '8', 'keterangan' => 'Giro 1', 'status' => '1', 'modifiedby' => 'ADMIN',]);
    }
}
