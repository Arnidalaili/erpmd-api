<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('user');

        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('user',255)->nullable();
            $table->string('name',255)->nullable();
            $table->string('password',255)->nullable();
            $table->unsignedBigInteger('customerid')->length(11)->nullable();
            $table->unsignedBigInteger('karyawanid')->length(11)->nullable();
            $table->string('dashboard',255)->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->integer('statusakses')->length(11)->nullable();
            $table->string('email',255)->nullable();
            $table->string('tablecustomer',20)->nullable();
            $table->string('tablekaryawan',20)->nullable();
            $table->string('modifiedby',255)->nullable();
            $table->timestamps();

            
            $table->foreign('karyawanid', 'user_karyawan_karyawanid_foreign')->references('id')->on('karyawan');
            $table->foreign('customerid', 'user_customer_customerid_foreign')->references('id')->on('customer');
        });
        DB::statement("ALTER TABLE user DROP FOREIGN KEY user_karyawan_karyawanid_foreign");
        DB::statement("ALTER TABLE user DROP FOREIGN KEY user_customer_customerid_foreign");
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
}
