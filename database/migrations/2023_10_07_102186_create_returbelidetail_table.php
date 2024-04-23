<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateReturbelidetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('returbelidetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('returbeliid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->unsignedBigInteger('pembeliandetailid')->nullable();
            $table->unsignedBigInteger('returjualdetailid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('qty',15,2)->nullable();
            $table->double('harga',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('returbeliid', 'rbdetail_rjheader_returbeliid_foreign')->references('id')->on('returbeliheader')->onDelete('cascade');
            $table->foreign('productid', 'rbdetail_product_productid_foreign')->references('id')->on('product');
            $table->foreign('satuanid', 'rbdetail_satuan_satuanid_foreign')->references('id')->on('satuan');
            $table->foreign('pembeliandetailid', 'rbdetail_pbdetail_pembeliandetailid_foreign')->references('id')->on('pembeliandetail');
        });

        DB::statement("ALTER TABLE returbelidetail DROP FOREIGN KEY rbdetail_product_productid_foreign");
        DB::statement("ALTER TABLE returbelidetail DROP FOREIGN KEY rbdetail_satuan_satuanid_foreign");
        DB::statement("ALTER TABLE returbelidetail DROP FOREIGN KEY rbdetail_pbdetail_pembeliandetailid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('returbelidetail');
    }
}
