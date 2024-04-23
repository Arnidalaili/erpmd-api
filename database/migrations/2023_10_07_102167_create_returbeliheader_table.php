<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateReturbeliheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('returbeliheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('pembelianid')->nullable();
            $table->unsignedBigInteger('supplierid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('total',15,2)->nullable();
            $table->date('tglcetak')->nullable();
            $table->string('flag', 20)->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('pembelianid', 'rjheader_pbheader_pembelianid_foreign')->references('id')->on('pembelianheader');
            $table->foreign('supplierid', 'rbheader_supplier_supplierid_foreign')->references('id')->on('supplier');
        });
        DB::statement("ALTER TABLE returbeliheader DROP FOREIGN KEY rbheader_supplier_supplierid_foreign");
        DB::statement("ALTER TABLE returbeliheader DROP FOREIGN KEY rjheader_pbheader_pembelianid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('returbeliheader');
    }
}
