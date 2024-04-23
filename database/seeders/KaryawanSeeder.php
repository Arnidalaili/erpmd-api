<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class KaryawanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        $telepon = $faker->regexify('[0-9]{13}');
         for ($i = 1; $i <= 100; $i++) {

            $randomArmadaId = DB::table('armada')->inRandomOrder()->value('id');

            DB::table('karyawan')->insert([
                'nama' => $faker->name,
                'nama2' => $faker->name,
                'telepon' => $telepon,
                'alamat' => $faker->address,
                'keterangan' => $faker->text,
                'armadaid' => $randomArmadaId,
                'status' => 1
            ]);
        }
    }
}
