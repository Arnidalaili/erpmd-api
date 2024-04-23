<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePenjualandetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('penjualandetail');

        Schema::create('penjualandetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penjualanid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->unsignedBigInteger('pesananfinaldetailid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('qty',15,2)->nullable();
            $table->double('qtyreturjual',15,2)->nullable();
            $table->double('qtyreturbeli',15,2)->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->double('harga',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('penjualanid', 'pjdetail_pjheader_penjualanid_foreign')->references('id')->on('penjualanheader')->onDelete('cascade');
            $table->foreign('productid', 'pjdetail_product_productid_foreign')->references('id')->on('product');
            $table->foreign('pesananfinaldetailid', 'pjdetail_pesananfinaldetail_pesananfinaldetailid_foreign')->references('id')->on('pesananfinaldetail');
        });

        DB::statement("ALTER TABLE penjualandetail DROP FOREIGN KEY pjdetail_product_productid_foreign");
        DB::statement("ALTER TABLE penjualandetail DROP FOREIGN KEY pjdetail_pesananfinaldetail_pesananfinaldetailid_foreign");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('penjualandetail');
    }
}
