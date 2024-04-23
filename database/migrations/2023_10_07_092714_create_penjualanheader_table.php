<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePenjualanheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('penjualanheader');

        Schema::create('penjualanheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('pesananfinalid')->nullable();
            $table->unsignedBigInteger('customerid')->nullable();
            $table->unsignedBigInteger('top')->nullable();
            $table->string('alamatpengiriman', 255)->nullable();
            $table->date('tglpengiriman')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->date('tglterima')->nullable();
            $table->double('servicetax',15,2)->nullable();
            $table->double('tax',15,2)->nullable();
            $table->double('taxamount',15,2)->nullable();
            $table->double('discount',15,2)->nullable();
            $table->double('subtotal',15,2)->nullable();
            $table->double('total',15,2)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->string('editall', 100)->nullable();
            $table->timestamps();

            $table->foreign('customerid', 'pjheader_customer_customerid_foreign')->references('id')->on('customer');
            $table->foreign('pesananfinalid', 'pjheader_pfheader_pesananfinalid_foreign')->references('id')->on('pesananfinalheader');
        });

        DB::statement("ALTER TABLE penjualanheader DROP FOREIGN KEY pjheader_customer_customerid_foreign");
        DB::statement("ALTER TABLE penjualanheader DROP FOREIGN KEY pjheader_pfheader_pesananfinalid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('penjualanheader');
    }
}
