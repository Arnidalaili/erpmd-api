<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class CustomersSeeder extends Seeder
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
         for ($i = 1; $i <= 1000; $i++) {
            DB::table('customers')->insert([
                'name' => $faker->name,
            ]);
        }
    }
}
