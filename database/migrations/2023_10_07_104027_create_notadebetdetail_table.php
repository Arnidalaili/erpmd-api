<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNotadebetdetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('notadebetdetail');

        Schema::create('notadebetdetail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notadebetid')->nullable();
            $table->unsignedBigInteger('piutangid')->nullable();
            $table->date('tglbuktipiutang')->nullable();
            $table->double('nominalpiutang',15,2)->nullable();
            $table->unsignedBigInteger('hutangid')->nullable();
            $table->date('tglbuktihutang')->nullable();
            $table->double('nominalhutang',15,2)->nullable();
            $table->integer('jenisnotadebet')->length(11)->nullable();
            $table->double('nominal',15,2)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('notadebetid', 'nbdetail_nbheader_notadebetid_foreign')->references('id')->on('notadebetheader')->onDelete('cascade');
            $table->foreign('piutangid', 'nbdetail_piutang_piutangid_foreign')->references('id')->on('piutang');
            $table->foreign('hutangid', 'nbdetail_hutang_hutangid_foreign')->references('id')->on('hutang');
        });

        DB::statement("ALTER TABLE notadebetdetail DROP FOREIGN KEY nbdetail_piutang_piutangid_foreign");
        DB::statement("ALTER TABLE notadebetdetail DROP FOREIGN KEY nbdetail_hutang_hutangid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notadebetdetail');
    }
}
