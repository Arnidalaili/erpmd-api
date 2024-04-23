<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePembeliandetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pembeliandetail');

        Schema::create('pembeliandetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembelianid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('qtystok',15,2)->nullable();
            $table->double('qty',15,2)->nullable();
            $table->double('qtyretur',15,2)->nullable();
            $table->double('qtypesanan',15,2)->nullable();
            $table->double('qtyterpakai',15,2)->nullable();
            $table->double('harga',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pembelianid', 'pbdetail_pbheader_pembelianid_foreign')->references('id')->on('pembelianheader')->onDelete('cascade');
            $table->foreign('productid', 'pbdetail_product_productid_foreign')->references('id')->on('product');
            $table->foreign('satuanid', 'pbdetail_satuan_satuanid_foreign')->references('id')->on('satuan');
            
        });

        DB::statement("ALTER TABLE pembeliandetail DROP FOREIGN KEY pbdetail_product_productid_foreign");
        DB::statement("ALTER TABLE pembeliandetail DROP FOREIGN KEY pbdetail_satuan_satuanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pembeliandetail');
    }
}
