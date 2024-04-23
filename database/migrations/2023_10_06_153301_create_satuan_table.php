<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSatuanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('satuan');
        
        Schema::create('satuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->nullable();
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
        Schema::dropIfExists('satuan');
    }
}
