<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        user::create(['user' => 'ADMIN', 'name' => 'ADMIN', 'password' => '$2y$10$/Hmu7CfU2uKpe/1mm8eJ8uXm45NVcAMB9NBM34/vKnknRSSxIBPEa', 'dashboard' => '', 'status' => '1', 'statusakses' => '3', 'email' => 'PT.TRANSPORINDO@GMAIL.COM', 'modifiedby' => '',]);
        user::create(['user' => 'EKA', 'name' => 'EKA', 'password' => '$2y$10$/Hmu7CfU2uKpe/1mm8eJ8uXm45NVcAMB9NBM34/vKnknRSSxIBPEa', 'dashboard' => '', 'status' => '1', 'statusakses' => '3', 'email' => 'EKA@GMAIL.COM', 'modifiedby' => '',]);
        user::create(['user' => 'ROY', 'name' => 'ROY', 'password' => '$2y$10$/Hmu7CfU2uKpe/1mm8eJ8uXm45NVcAMB9NBM34/vKnknRSSxIBPEa', 'dashboard' => '', 'status' => '1', 'statusakses' => '3', 'email' => 'ROY@GMAIL.COM', 'modifiedby' => '',]);
    }
}
