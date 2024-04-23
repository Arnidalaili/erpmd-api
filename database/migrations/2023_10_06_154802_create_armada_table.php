<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArmadaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('armada');

        Schema::create('armada', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->nullable();
            $table->integer('jenisarmada')->length(11)->nullable();
            $table->string('nopolisi', 25)->nullable();
            $table->string('namapemilik', 100)->nullable();
            $table->string('nostnk', 25)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
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
        Schema::dropIfExists('armada');
    }
}
