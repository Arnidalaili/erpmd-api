<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTransaksiarmadaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('transaksiarmada');

        Schema::create('transaksiarmada', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('perkiraanid')->nullable();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('karyawanid')->nullable();
            $table->unsignedBigInteger('armadaid')->nullable();
            $table->double('nominal',15,2)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('perkiraanid', 'ta_perkiraan_perkiraanid_foreign')->references('id')->on('perkiraan');
            $table->foreign('armadaid', 'ta_armada_armadaid_foreign')->references('id')->on('armada');
            $table->foreign('karyawanid', 'ta_karyawan_karyawanid_foreign')->references('id')->on('karyawan');
        });

        DB::statement("ALTER TABLE transaksiarmada DROP FOREIGN KEY ta_perkiraan_perkiraanid_foreign");
        DB::statement("ALTER TABLE transaksiarmada DROP FOREIGN KEY ta_armada_armadaid_foreign");
        DB::statement("ALTER TABLE transaksiarmada DROP FOREIGN KEY ta_karyawan_karyawanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaksiarmada');
    }
}
