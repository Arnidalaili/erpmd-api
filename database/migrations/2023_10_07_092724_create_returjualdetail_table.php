<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateReturjualdetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('returjualdetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('returjualid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->unsignedBigInteger('satuanid')->nullable();
            $table->unsignedBigInteger('penjualandetailid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('qty',15,2)->nullable();
            $table->double('harga',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('returjualid', 'rjdetail_rjheader_returjualid_foreign')->references('id')->on('returjualheader')->onDelete('cascade');
            $table->foreign('productid', 'rjdetail_product_productid_foreign')->references('id')->on('product');
            $table->foreign('satuanid', 'rjdetail_satuan_satuanid_foreign')->references('id')->on('satuan');
            $table->foreign('penjualandetailid', 'rjdetail_pjdetail_penjualandetailid_foreign')->references('id')->on('penjualandetail');
        });

        DB::statement("ALTER TABLE returjualdetail DROP FOREIGN KEY rjdetail_product_productid_foreign");
        DB::statement("ALTER TABLE returjualdetail DROP FOREIGN KEY rjdetail_satuan_satuanid_foreign");
        DB::statement("ALTER TABLE returjualdetail DROP FOREIGN KEY rjdetail_pjdetail_penjualandetailid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('returjualdetail');
    }
}
