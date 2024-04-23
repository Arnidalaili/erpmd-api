<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePembelianheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pembelianheader');

        Schema::create('pembelianheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('supplierid')->nullable();
            $table->unsignedBigInteger('top')->nullable();
            $table->unsignedBigInteger('karyawanid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->date('tglterima')->nullable();
            $table->double('subtotal',15,2)->nullable();
            $table->double('potongan',15,2)->nullable();
            $table->double('total',15,2)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('supplierid', 'pbheader_supplier_supplierid_foreign')->references('id')->on('supplier');
            $table->foreign('karyawanid', 'pbheader_karyawan_karyawanid_foreign')->references('id')->on('karyawan');
        });

        DB::statement("ALTER TABLE pembelianheader DROP FOREIGN KEY pbheader_supplier_supplierid_foreign");
        DB::statement("ALTER TABLE pembelianheader DROP FOREIGN KEY pbheader_karyawan_karyawanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pembelianheader');
    }
}
