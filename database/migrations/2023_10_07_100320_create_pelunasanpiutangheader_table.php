<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePelunasanpiutangheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pelunasanpiutangheader');

        Schema::create('pelunasanpiutangheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->integer('jenispelunasanpiutang')->length(11)->nullable();
            $table->unsignedBigInteger('alatbayarid')->nullable();
            $table->unsignedBigInteger('customerid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('alatbayarid', 'ppheader_alatbayar_alatbayarid_foreign')->references('id')->on('alatbayar');
            $table->foreign('customerid', 'ppheader_customer_customerid_foreign')->references('id')->on('customer');
        });

        DB::statement("ALTER TABLE pelunasanpiutangheader DROP FOREIGN KEY ppheader_alatbayar_alatbayarid_foreign");
        DB::statement("ALTER TABLE pelunasanpiutangheader DROP FOREIGN KEY ppheader_customer_customerid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pelunasanpiutangheader');
    }
}
