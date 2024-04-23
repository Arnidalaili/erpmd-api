<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('customer');

        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->nullable();
            $table->string('nama2', 100)->nullable();
            $table->string('username', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('telepon', 255)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->unsignedBigInteger('ownerid')->nullable();
            $table->integer('hargaproduct')->nullable();
            $table->unsignedBigInteger('groupid')->nullable();
            $table->integer('status')->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('ownerid', 'customer_owner_ownerid_foreign')->references('id')->on('owner');
            $table->foreign('groupid', 'customer_groupcustomer_groupid_foreign')->references('id')->on('groupcustomer');
        });

        DB::statement("ALTER TABLE customer DROP FOREIGN KEY customer_owner_ownerid_foreign");
        DB::statement("ALTER TABLE customer DROP FOREIGN KEY customer_groupcustomer_groupid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer');
    }
}
