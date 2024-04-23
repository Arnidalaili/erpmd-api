<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePelunasanhutangdetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pelunasanhutangdetail');

        Schema::create('pelunasanhutangdetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pelunasanhutangid')->nullable();
            $table->unsignedBigInteger('hutangid')->nullable();
            $table->date('tglbuktihutang')->nullable();
            $table->double('nominalhutang',15,2)->nullable();
            $table->double('nominalbayar',15,2)->nullable();
            $table->double('sisa',15,2)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('nominalpotongan',15,2)->nullable();
            $table->string('keteranganpotongan', 255)->nullable();
            $table->double('nominalnotadebet',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pelunasanhutangid', 'phdetail_phheader_pelunasanhutangid_foreign')->references('id')->on('pelunasanhutangheader')->onDelete('cascade');
            $table->foreign('hutangid', 'phdetail_hutang_hutangid_foreign')->references('id')->on('hutang');
        });

        DB::statement("ALTER TABLE pelunasanhutangdetail DROP FOREIGN KEY phdetail_hutang_hutangid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pelunasanhutangdetail');
    }
}
