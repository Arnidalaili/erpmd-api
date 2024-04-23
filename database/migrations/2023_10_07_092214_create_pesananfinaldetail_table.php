<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePesananfinaldetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pesananfinaldetail');

        Schema::create('pesananfinaldetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesananfinalid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->string('nobuktipembelian', 20)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('qtyjual', 15, 2)->nullable();
            $table->double('qtybeli', 15, 2)->nullable();
            $table->double('qtyreturjual', 15,2)->nullable();
            $table->double('qtyreturbeli',15,2)->nullable();
            $table->double('hargajual',15,2)->nullable();
            $table->double('hargabeli',15,2)->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->integer('cekpesanandetail')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pesananfinalid', 'pfdetail_pfheader_pesananfinalid_foreign')->references('id')->on('pesananfinalheader')->onDelete('cascade');
            $table->foreign('productid', 'pfdetail_product_productid_foreign')->references('id')->on('product');
            $table->foreign('satuanid', 'pfdetail_satuan_satuanid_foreign')->references('id')->on('satuan');
        });

        DB::statement("ALTER TABLE pesananfinaldetail DROP FOREIGN KEY pfdetail_product_productid_foreign");
        DB::statement("ALTER TABLE pesananfinaldetail DROP FOREIGN KEY pfdetail_satuan_satuanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesananfinaldetail');
    }
}
