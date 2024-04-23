<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePenyesuaianstokdetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('penyesuaianstokdetail');

        Schema::create('penyesuaianstokdetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penyesuaianstokid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->integer('qty')->length(11)->nullable();
            $table->double('harga',15,2)->nullable();
            $table->double('total',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('penyesuaianstokid', 'psheader_psheader_penyesuaianstokid_foreign')->references('id')->on('penyesuaianstokheader')->onDelete('cascade');
            $table->foreign('productid', 'psheader_product_productid_foreign')->references('id')->on('product');
        });

        DB::statement("ALTER TABLE penyesuaianstokdetail DROP FOREIGN KEY psheader_product_productid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('penyesuaianstokdetail');
    }
}
