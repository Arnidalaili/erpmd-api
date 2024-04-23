<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateKaryawanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('karyawan');

        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->nullable();
            $table->string('nama2', 100)->nullable();
            $table->string('username', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('telepon', 255)->nullable();
            $table->string('alamat', 255)->nullable(); 
            $table->string('keterangan', 255)->nullable();
            $table->unsignedBigInteger('armadaid')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('armadaid', 'karyawan_armada_armadaid_foreign')->references('id')->on('armada');
        });
        DB::statement("ALTER TABLE karyawan DROP FOREIGN KEY karyawan_armada_armadaid_foreign");
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('karyawan');
    }
}
