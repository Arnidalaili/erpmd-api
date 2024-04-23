<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCekpesananTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cekpesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesananfinalid')->nullable();
            $table->unsignedBigInteger('pesananfinaldetailid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->unsignedBigInteger('customerid')->nullable();
            $table->double('qty', 15, 2)->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->integer('cekpesanandetail')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pesananfinalid', 'cp_pfheader_pesananfinalid_foreign')->references('id')->on('pesananfinalheader');
            $table->foreign('productid', 'cp_product_productid_foreign')->references('id')->on('product');
            $table->foreign('satuanid', 'cp_satuan_satuanid_foreign')->references('id')->on('satuan');
            $table->foreign('customerid', 'cp_customer_customerid_foreign')->references('id')->on('customer');
        });

        DB::statement("ALTER TABLE cekpesanan DROP FOREIGN KEY cp_pfheader_pesananfinalid_foreign");
        DB::statement("ALTER TABLE cekpesanan DROP FOREIGN KEY cp_product_productid_foreign");
        DB::statement("ALTER TABLE cekpesanan DROP FOREIGN KEY cp_satuan_satuanid_foreign");
        DB::statement("ALTER TABLE cekpesanan DROP FOREIGN KEY cp_customer_customerid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cekpesanan');
    }
}
