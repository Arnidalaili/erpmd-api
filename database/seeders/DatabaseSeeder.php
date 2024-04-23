<?php

namespace Database\Seeders;

use App\Models\GroupCustomer;
use Customer;
use Fakturpenjualanheader;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            ParameterSeeder::class,
            OwnerSeeder::class,
            GroupCustomerSeeder::class,
            UserSeeder::class,
            AcosSeeder::class,            
            MenuSeeder::class,
            RoleSeeder::class,
            AclSeeder::class,
            UserAclSeeder::class,
            UserRoleSeeder::class,
            ErrorSeeder::class,
            HariLiburSeeder::class,
            BankSeeder::class,
            AlatBayarSeeder::class,
            // PesananHeaderSeeder::class,
            // PesananDetailSeeder::class,
        ]);
    }
}
