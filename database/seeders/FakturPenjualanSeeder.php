<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class FakturPenjualanSeeder extends Seeder
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

            DB::table('fakturpenjualanheader')->insert([
                'customer_id' => 1,
                'noinvoice' => $invoiceNumber,
                'invoicedate' => '2023-09-04',
                'shipdate' => '2023-09-10',
                'shipvia' => 'CARGO',
                'modifiedby' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
