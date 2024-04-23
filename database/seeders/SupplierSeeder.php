<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

         // Seed some customers with fake names
         for ($i = 1; $i <= 100; $i++) {
            $randomKaryawanId = DB::table('karyawan')->inRandomOrder()->value('id');

            DB::table('supplier')->insert([
                'nama' => $faker->name,
                'telepon' => $faker->phoneNumber,
                'alamat' => $faker->address,
                'keterangan' => $faker->text,
                'karyawanid' => $randomKaryawanId,
                'potongan' => '0',
                'top' => 11,
                'status' => 1
            ]);
        }
    }
}
