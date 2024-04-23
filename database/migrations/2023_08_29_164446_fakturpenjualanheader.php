<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Fakturpenjualanheader extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('fakturpenjualanheader');

        Schema::create('fakturpenjualanheader', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->Length(11)->nullable();
            $table->string('nopo',250)->nullable();
            $table->string('noinvoice',100)->nullable()->unique();
            $table->date('invoicedate')->nullable();
            $table->string('shipto',250)->nullable();
            $table->string('rate',250)->nullable();
            $table->string('fob',250)->nullable();
            $table->string('terms',250)->nullable();
            $table->string('fiscalrate',250)->nullable();
            $table->date('shipdate')->nullable();
            $table->string('shipvia',250)->nullable();
            $table->string('receivableacoount',250)->nullable();
            $table->integer('sales_id')->Length(11)->nullable();
            $table->string('modifiedby',50)->nullable();
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
        Schema::dropIfExists('fakturpenjualanheader');
    }
}
