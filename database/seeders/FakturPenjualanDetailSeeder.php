<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class FakturPenjualanDetailSeeder extends Seeder
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
            $randomFakturPenjualanId = DB::table('fakturpenjualanheader')->inRandomOrder()->value('id');
            $randomItemId = DB::table('customers')->inRandomOrder()->value('id');

            DB::table('fakturpenjualandetail')->insert([
                'fakturpenjualan_id' => $randomFakturPenjualanId,
                'item_id' => $randomItemId,
                'description' => 'desc',
                'qty' => $faker->randomNumber(2),
                'hargasatuan' => $faker->randomNumber(7),
                'amount' => $faker->randomNumber(7),
                'modifiedby' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()

            ]);
        }
    }
}
