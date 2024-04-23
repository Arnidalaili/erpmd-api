<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class PesananDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 100; $i++) {
            $randomPesananHeaderId = DB::table('pesananheader')->inRandomOrder()->value('id');
            
            for ($j = 1; $j <= 25; $j++) {
                $randomItemId = DB::table('product')->inRandomOrder()->value('id');
        
                DB::table('pesanandetail')->insert([
                    'pesananid' => $randomPesananHeaderId,
                    'productid' => $randomItemId,
                    'qty' => $faker->numberBetween(1, 10),
                    // 'satuanId' => 1,
                    'keterangan' => 'desc',
                    'modifiedby' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }
        }
    }
}
