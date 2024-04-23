<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class PesananFinalHeaderSeeder extends Seeder
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

            DB::table('pesananfinalheader')->insert([
                'nobukti' => $invoiceNumber,
                'tglbukti' => Carbon::now(),
                'nobuktipenjualan' => '',
                'nobuktipenjualan' => '',
                'pesananid' => 111,
                'tglbuktipesanan' => '2023-11-02',
                'customerid' => $customerId,
                'alamatpengiriman' => 'BELAWAN',
                'tglpengiriman' => '2023-11-03',
                'keterangan' =>  $faker->text,
                'servicetax' => 0,
                'tax' => 0,
                'subtotal' => 0,
                'discount' => 0,
                'tglcetak' => '2023-11-04',
                'status' =>  1,
                'modifiedby' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
