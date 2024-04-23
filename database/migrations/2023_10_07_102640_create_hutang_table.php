<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateHutangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('hutang');

        Schema::create('hutang', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->string('flag', 20)->nullable();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('pembelianid')->nullable();
            $table->date('tglbuktipembelian')->nullable();
            $table->date('tgljatuhtempo')->nullable();
            $table->unsignedBigInteger('supplierid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('nominalhutang',15,2)->nullable();
            $table->double('nominalbayar',15,2)->nullable();
            $table->double('nominalsisa',15,2)->nullable();
            $table->date('tglcetak')->nullable();
            $table->date('tgllunas')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pembelianid', 'hutang_pbheader_pembelianid_foreign')->references('id')->on('pembelianheader');
            $table->foreign('supplierid', 'hutang_supplier_supplierid_foreign')->references('id')->on('supplier');
        });

        DB::statement("ALTER TABLE hutang DROP FOREIGN KEY hutang_pbheader_pembelianid_foreign");
        DB::statement("ALTER TABLE hutang DROP FOREIGN KEY hutang_supplier_supplierid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hutang');
    }
}
