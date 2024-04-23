<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAlatbayarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('alatbayar');

        Schema::create('alatbayar', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->unsignedBigInteger('bankid')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('bankid', 'alatbayar_bank_bankid_foreign')->references('id')->on('bank');
        });

        DB::statement("ALTER TABLE alatbayar DROP FOREIGN KEY alatbayar_bank_bankid_foreign");
    }

    /**p
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('alatbayar');
    }
}
