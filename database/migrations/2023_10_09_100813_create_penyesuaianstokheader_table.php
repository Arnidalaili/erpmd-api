<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenyesuaianstokheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('penyesuaianstokheader');

        Schema::create('penyesuaianstokheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('total',15,2)->nullable();
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
        Schema::dropIfExists('penyesuaianstokheader');
    }
}
