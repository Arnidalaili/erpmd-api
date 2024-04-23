<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSupplierTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('supplier');

        Schema::create('supplier', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->nullable();
            $table->string('telepon', 255)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->unsignedBigInteger('karyawanid')->nullable();
            $table->double('potongan',15,2)->nullable();
            $table->integer('top')->length(11)->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('karyawanid', 'supplier_karyawan_karyawanid_foreign')->references('id')->on('karyawan');
        });
        
        DB::statement("ALTER TABLE supplier DROP FOREIGN KEY supplier_karyawan_karyawanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier');
    }
}
