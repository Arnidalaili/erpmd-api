<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePesananfinalheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pesananfinalheader');

        Schema::create('pesananfinalheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->string('nobuktipenjualan', 20)->nullable();
            $table->unsignedBigInteger('pesananid')->nullable();
            $table->date('tglbuktipesanan')->nullable();
            $table->unsignedBigInteger('customerid')->nullable();
            $table->string('alamatpengiriman', 255)->nullable();
            $table->date('tglpengiriman')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('servicetax',15,2)->nullable();
            $table->integer('tax')->length(11)->nullable();
            $table->double('taxamount',15,2)->nullable();
            $table->double('discount',15,2)->nullable();
            $table->double('subtotal',15,2)->nullable();
            $table->double('total',15,2)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->integer('cekpesanan')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('customerid', 'pfheader_customer_customerid_foreign')->references('id')->on('customer');
            $table->foreign('pesananid', 'pfheader_pheader_pesananid_foreign')->references('id')->on('pesananheader');
        });

        DB::statement("ALTER TABLE pesananfinalheader DROP FOREIGN KEY pfheader_customer_customerid_foreign");
        DB::statement("ALTER TABLE pesananfinalheader DROP FOREIGN KEY pfheader_pheader_pesananid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesananfinalheader');
    }
}
