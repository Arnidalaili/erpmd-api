<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Fakturpenjualandetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('fakturpenjualandetail');

        Schema::create('fakturpenjualandetail', function (Blueprint $table) {
            $table->id();
            $table->integer('fakturpenjualan_id')->Length(11)->nullable();
            $table->integer('item_id')->Length(11)->nullable();
            $table->string('description', 250)->nullable();
            $table->integer('qty')->Length(11)->nullable();
            $table->double('hargasatuan', 15,2)->nullable();
            $table->double('amount', 15,2)->nullable();
            $table->string('modifiedby', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fakturpenjualandetail');
    }
}
