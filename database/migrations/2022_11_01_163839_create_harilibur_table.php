<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHariliburTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('harilibur', function (Blueprint $table) {
            $table->id();
            $table->date('tgl')->nullable();
            $table->longText('keterangan')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby',50)->nullable();            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('harilibur');
    }
}
