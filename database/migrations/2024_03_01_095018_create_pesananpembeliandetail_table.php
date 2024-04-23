<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePesananpembeliandetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pesananpembeliandetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembeliandetailid')->nullable();
            $table->unsignedBigInteger('pesananfinalid')->nullable();
            $table->unsignedBigInteger('pesananfinaldetailid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('qty',15,2)->nullable();
            $table->double('harga',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pembeliandetailid', 'ppbdetail_pbdetail_pembeliandetailid_foreign')->references('id')->on('pembeliandetail')->onDelete('cascade');
            $table->foreign('pesananfinalid', 'ppbdetail_pfheader_pesananfinalid_foreign')->references('id')->on('pesananfinalheader');
            $table->foreign('pesananfinaldetailid', 'ppbdetail_pfdetail_pesananfinaldetailid_foreign')->references('id')->on('pesananfinaldetail');
            $table->foreign('productid', 'ppbdetail_product_productid_foreign')->references('id')->on('product');
            $table->foreign('satuanid', 'ppbdetail_satuan_satuanid_foreign')->references('id')->on('satuan');
        });

        DB::statement("ALTER TABLE pesananpembeliandetail DROP FOREIGN KEY ppbdetail_pfheader_pesananfinalid_foreign");
        DB::statement("ALTER TABLE pesananpembeliandetail DROP FOREIGN KEY ppbdetail_pfdetail_pesananfinaldetailid_foreign");
        DB::statement("ALTER TABLE pesananpembeliandetail DROP FOREIGN KEY ppbdetail_product_productid_foreign");
        DB::statement("ALTER TABLE pesananpembeliandetail DROP FOREIGN KEY ppbdetail_satuan_satuanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesananpembeliandetail');
    }
}
