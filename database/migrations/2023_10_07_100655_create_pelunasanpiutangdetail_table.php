<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePelunasanpiutangdetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pelunasanpiutangdetail');

        Schema::create('pelunasanpiutangdetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pelunasanpiutangid')->nullable();
            $table->unsignedBigInteger('piutangid')->nullable();
            $table->date('tglbuktipiutang')->nullable();
            $table->double('nominalpiutang',15,2)->nullable();
            $table->double('nominalbayar',15,2)->nullable();
            $table->double('sisa',15,2)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('nominalpotongan',15,2)->nullable();
            $table->string('keteranganpotongan', 255)->nullable();
            $table->double('nominalnotadebet',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pelunasanpiutangid', 'ppdetail_ppheader_pelunasanpiutangid_foreign')->references('id')->on('pelunasanpiutangheader')->onDelete('cascade');
            $table->foreign('piutangid', 'ppdetail_piutang_piutangid_foreign')->references('id')->on('piutang');
        });

        DB::statement("ALTER TABLE pelunasanpiutangdetail DROP FOREIGN KEY ppdetail_piutang_piutangid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pelunasanpiutangdetail');
    }
}
