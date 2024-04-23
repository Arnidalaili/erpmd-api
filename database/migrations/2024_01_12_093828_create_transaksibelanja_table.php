<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTransaksibelanjaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('transaksibelanja');

        Schema::create('transaksibelanja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('perkiraanid')->nullable();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('karyawanid')->nullable();
            $table->unsignedBigInteger('pembelianid')->nullable();
            $table->double('nominal',15,2)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('perkiraanid', 'tb_perkiraan_perkiraanid_foreign')->references('id')->on('perkiraan');
            $table->foreign('pembelianid', 'tb_pbheader_pembelianid_foreign')->references('id')->on('pembelianheader');
            $table->foreign('karyawanid', 'tb_karyawan_karyawanid_foreign')->references('id')->on('karyawan');
        });

        DB::statement("ALTER TABLE transaksibelanja DROP FOREIGN KEY tb_perkiraan_perkiraanid_foreign");
        DB::statement("ALTER TABLE transaksibelanja DROP FOREIGN KEY tb_pbheader_pembelianid_foreign");
        DB::statement("ALTER TABLE transaksibelanja DROP FOREIGN KEY tb_karyawan_karyawanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaksibelanja');
    }
}
