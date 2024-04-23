<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePiutangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('piutang');

        Schema::create('piutang', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->string('flag', 20)->nullable();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('penjualanid')->nullable();
            $table->date('tglbuktipenjualan')->nullable();
            $table->date('tgljatuhtempo')->nullable();
            $table->unsignedBigInteger('customerid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('nominalpiutang',15,2)->nullable();
            $table->double('nominalbayar',15,2)->nullable();
            $table->double('nominalsisa',15,2)->nullable();
            $table->date('tglcetak')->nullable();
            $table->date('tgllunas')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('penjualanid', 'piutang_pjheader_penjualanid_foreign')->references('id')->on('penjualanheader');
            $table->foreign('customerid', 'piutang_customer_customerid_foreign')->references('id')->on('customer');
        });

        DB::statement("ALTER TABLE piutang DROP FOREIGN KEY piutang_pjheader_penjualanid_foreign");
        DB::statement("ALTER TABLE piutang DROP FOREIGN KEY piutang_customer_customerid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('piutang');
    }
}
