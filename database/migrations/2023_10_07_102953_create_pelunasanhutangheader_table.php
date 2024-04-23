<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePelunasanhutangheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pelunasanhutangheader');

        Schema::create('pelunasanhutangheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->integer('jenispelunasanhutang')->length(11)->nullable();
            $table->unsignedBigInteger('alatbayarid')->nullable();
            $table->unsignedBigInteger('supplierid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('alatbayarid', 'phheader_alatbayar_alatbayarid_foreign')->references('id')->on('alatbayar');
            $table->foreign('supplierid', 'phheader_supplier_supplierid_foreign')->references('id')->on('supplier');
        });

        DB::statement("ALTER TABLE pelunasanhutangheader DROP FOREIGN KEY phheader_alatbayar_alatbayarid_foreign");
        DB::statement("ALTER TABLE pelunasanhutangheader DROP FOREIGN KEY phheader_supplier_supplierid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pelunasanhutangheader');
    }
}
