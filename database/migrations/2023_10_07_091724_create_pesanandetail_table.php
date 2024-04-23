<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePesanandetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pesanandetail');

        Schema::create('pesanandetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesananid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->double('qty', 15,2)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pesananid', 'pdetail_pheader_pesananid_foreign')->references('id')->on('pesananheader')->onDelete('cascade');
            $table->foreign('productid', 'pdetail_product_productid_foreign')->references('id')->on('product');
            $table->foreign('satuanid', 'pdetail_satuan_satuanid_foreign')->references('id')->on('satuan');
        });

        DB::statement("ALTER TABLE pesanandetail DROP FOREIGN KEY pdetail_product_productid_foreign");
        DB::statement("ALTER TABLE pesanandetail DROP FOREIGN KEY pdetail_satuan_satuanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesanandetail');
    }
}
