<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('product');

        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->nullable();
            $table->unsignedBigInteger('groupid')->nullable();
            $table->unsignedBigInteger('supplierid')->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('hargajual',15,2)->nullable();
            $table->double('hargabeli',15,2)->nullable();
            $table->double('hargakontrak1',15,2)->nullable();
            $table->double('hargakontrak2',15,2)->nullable();
            $table->double('hargakontrak3',15,2)->nullable();
            $table->double('hargakontrak4',15,2)->nullable();
            $table->double('hargakontrak5',15,2)->nullable();
            $table->double('hargakontrak6',15,2)->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('groupid', 'customer_group_groupid_foreign')->references('id')->on('groupproduct');
            $table->foreign('supplierid', 'customer_supplier_supplierid_foreign')->references('id')->on('supplier');
            $table->foreign('satuanid', 'customer_satuan_satuanid_foreign')->references('id')->on('satuan');
        });

        DB::statement("ALTER TABLE product DROP FOREIGN KEY customer_group_groupid_foreign");
        DB::statement("ALTER TABLE product DROP FOREIGN KEY customer_supplier_supplierid_foreign");
        DB::statement("ALTER TABLE product DROP FOREIGN KEY customer_satuan_satuanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product');
    }
}
