<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNotakreditdetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('notakreditdetail');

        Schema::create('notakreditdetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notakreditid')->nullable();
            $table->unsignedBigInteger('piutangid')->nullable();
            $table->date('tglbuktipiutang')->nullable();
            $table->double('nominalpiutang',15,2)->nullable();
            $table->unsignedBigInteger('hutangid')->nullable();
            $table->date('tglbuktihutang')->nullable();
            $table->double('nominalhutang',15,2)->nullable();
            $table->string('keteranganpotongan', 255)->nullable();
            $table->double('nominal',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('notakreditid', 'nkdetail_nkheader_notakreditid_foreign')->references('id')->on('notakreditheader')->onDelete('cascade');
            $table->foreign('piutangid', 'nkdetail_piutang_piutangid_foreign')->references('id')->on('piutang');
            $table->foreign('hutangid', 'nkdetail_hutang_hutangid_foreign')->references('id')->on('hutang');
        });

        DB::statement("ALTER TABLE notakreditdetail DROP FOREIGN KEY nkdetail_piutang_piutangid_foreign");
        DB::statement("ALTER TABLE notakreditdetail DROP FOREIGN KEY nkdetail_hutang_hutangid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notakreditdetail');
    }
}
