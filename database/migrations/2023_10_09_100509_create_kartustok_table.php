<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKartustokTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('kartustok');
        
        Schema::create('kartustok', function (Blueprint $table) {
            $table->id();
            $table->date('tglbukti')->nullable();
            $table->string('nobukti', 20);
            $table->unsignedBigInteger('productid')->nullable();
            $table->unsignedBigInteger('pengeluarandetailid')->nullable();
            $table->unsignedBigInteger('penerimaandetailid')->nullable();
            $table->double('qtypenerimaan', 15, 2)->nullable();
            $table->double('qtypengeluaran', 15, 2)->nullable();
            $table->double('totalpenerimaan',15,2)->nullable();
            $table->double('totalpengeluaran',15,2)->nullable();
            $table->double('qtysaldo', 15, 2)->nullable();
            $table->double('totalsaldo',15,2)->nullable();
            $table->string('flag', 100)->nullable();
            $table->integer('seqno')->length(11)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
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
        Schema::dropIfExists('kartustok');
    }
}
