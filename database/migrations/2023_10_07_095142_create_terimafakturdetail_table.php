<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTerimafakturdetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('terimafakturdetail');

        Schema::create('terimafakturdetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('terimafakturid')->nullable();
            $table->unsignedBigInteger('penjualanid')->nullable();
            $table->date('tgljatuhtempo')->nullable();
            $table->double('nominal',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('terimafakturid', 'tfdetail_tfheader_terimafakturid_foreign')->references('id')->on('terimafakturheader')->onDelete('cascade');
            $table->foreign('penjualanid', 'tfdetail_pjheader_penjualanid_foreign')->references('id')->on('penjualanheader');
        });

        DB::statement("ALTER TABLE terimafakturdetail DROP FOREIGN KEY tfdetail_pjheader_penjualanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('terimafakturdetail');
    }
}
