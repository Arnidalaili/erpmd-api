<?php

namespace Database\Seeders;

use App\Models\GroupCustomer;
use Illuminate\Database\Seeder;

class GroupCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GroupCustomer::create(['nama' => 'Group Customer 1', 'keterangan' => 'Group Customer 1', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        GroupCustomer::create(['nama' => 'Group Customer 2', 'keterangan' => 'Group Customer 2', 'status' => '1', 'modifiedby' => 'ADMIN',]);
        GroupCustomer::create(['nama' => 'Group Customer 3', 'keterangan' => 'Group Customer 3', 'status' => '1', 'modifiedby' => 'ADMIN',]);
    }
}
