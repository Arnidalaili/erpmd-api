<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class PesananFinalDetailSeeder extends Seeder
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
            $randomPesananFinalHeaderId = DB::table('pesananfinalheader')->inRandomOrder()->value('id');

            $usedProductIds = [];
            for ($j = 1; $j <= 1000; $j++) {
                do {
                    $randomItemId = DB::table('product')->inRandomOrder()->value('id');
                } while (in_array($randomItemId, $usedProductIds));

                $usedProductIds[] = $randomItemId;

                DB::table('pesananfinaldetail')->insert([
                    'pesananfinalid' => $randomPesananFinalHeaderId,
                    'productid' => $randomItemId,
                    'qty' => $faker->numberBetween(1, 5),
                    'qtyreturjual' => 0,
                    'qtyreturbeli' => 0,
                    'satuanid' => 1,
                    'keterangan' => '',
                    'harga' => 0,
                    'modifiedby' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }


            // for ($j = 1; $j <= 500; $j++) {
            //     $randomItemId = DB::table('product')->inRandomOrder()->value('id');

            //     DB::table('pesananfinaldetail')->insert([
            //         'pesananfinalid' => $randomPesananFinalHeaderId,
            //         'productid' => $randomItemId,
            //         'qty' => $faker->numberBetween(1, 5),
            //         'qtyreturjual' => 0,
            //         'qtyreturbeli' => 0,
            //         'satuanid' => 1,
            //         'keterangan' => '',
            //         'harga' => 0,
            //         'modifiedby' => 1,
            //         'created_at' => Carbon::now(),
            //         'updated_at' => Carbon::now()
            //     ]);
            // }
        }
    }
}
