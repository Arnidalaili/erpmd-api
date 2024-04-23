<?php

namespace Database\Seeders;

use App\Models\CustomerModel;
use Customer;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CustomerModel::create(['nama' => 'Admin', 'nama2' => '','telepon' => '','alamat' => 'pusat','keterangan' => '','ownerid' => '1','productid' => '1','groupid' => '1', 'status' => '1', 'modifiedby' => 'ADMIN']);
        // CustomerModel::create(['nama' => 'Apni', 'nama2' => '','telepon' => '','alamat' => 'pusat','keterangan' => '','ownerid' => '1','productid' => '2','groupid' => '2', 'status' => '1', 'modifiedby' => 'ADMIN']);
    }
}
