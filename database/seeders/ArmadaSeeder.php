<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class ArmadaSeeder extends Seeder
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
            DB::table('armada')->insert([
                'nama' => 'motor',
                'keterangan' => $faker->text,
                'status' => 1
            ]);
        }
    }
}
