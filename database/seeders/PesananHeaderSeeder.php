<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class PesananHeaderSeeder extends Seeder
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
            $invoiceNumber = 'inv' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $customerId = DB::table('customer')->inRandomOrder()->value('id');

            DB::table('pesananheader')->insert([
                'nobukti' => $invoiceNumber,
                'tglbukti' => Carbon::now(),
                'customerid' => 1,
                'alamatpengiriman' => 'BELAWAN',
                'tglpengiriman' => '2023-09-10',
                'keterangan' =>  $faker->text,
                'status' =>  1,
                'modifiedby' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
