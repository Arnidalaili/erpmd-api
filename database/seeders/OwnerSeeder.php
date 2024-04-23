<?php

namespace Database\Seeders;

use App\Models\Owner;
use Illuminate\Database\Seeder;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        owner::create(['nama' => 'Owner 1', 'nama2' => 'PT ABC', 'telepon' => '081209678594', 'alamat' => 'Jl. ABC No.12', 'keterangan' => 'Owner 1', 'status' => '1', 'modifiedby' => 'ADMIN',]);
    }
}
