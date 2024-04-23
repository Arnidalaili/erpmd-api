<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $faker = Faker::create();

        //  // Seed some customers with fake names
        //  for ($i = 1; $i <= 100; $i++) {
        //     DB::table('satuan')->insert([
        //         'nama' => $faker->name,
        //         'keterangan' => $faker->text,
        //         'status' => 1

        //     ]);
        // }

        Satuan::create(['nama' => 'Botol', 'keterangan' => 'Botol', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Can', 'keterangan' => 'Can', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Renteng', 'keterangan' => 'Renteng', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Sample', 'keterangan' => 'Sample', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Ktn', 'keterangan' => 'Ktn', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Ganti semalam', 'keterangan' => 'Ganti semalam', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Btg', 'keterangan' => 'Btg', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Btr', 'keterangan' => 'Btr', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Pcs', 'keterangan' => 'Pcs', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Bks', 'keterangan' => 'Bks', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Pack', 'keterangan' => 'Pack', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Ikat', 'keterangan' => 'Ikat', 'status' => '1', 'modifiedby' => 'ADMIN']);
        Satuan::create(['nama' => 'Kg', 'keterangan' => 'Kg', 'status' => '1', 'modifiedby' => 'ADMIN']);
    }
}
