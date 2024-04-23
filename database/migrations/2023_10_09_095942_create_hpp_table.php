<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateHppTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('hpp');

        Schema::create('hpp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pengeluaranid')->nullable();
            $table->date('tglbukti')->nullable();
            $table->string('pengeluarannobukti', 100)->nullable();
            $table->unsignedBigInteger('pengeluarandetailid')->nullable();
            $table->unsignedBigInteger('penerimaanid')->nullable();
            $table->unsignedBigInteger('penerimaandetailid')->nullable();
            $table->unsignedBigInteger('productid')->nullable();
            $table->double('urut',15,2)->nullable();
            $table->double('pengeluaranqty', 15, 2)->length(11)->nullable();
            $table->double('penerimaanharga',15,2)->nullable();
            $table->double('pengeluaranharga',15,2)->nullable();
            $table->double('penerimaantotal',15,2)->nullable();
            $table->double('pengeluarantotal',15,2)->nullable();
            $table->double('profit',15,2)->nullable();
            $table->string('flag', 100)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pengeluaranid', 'hpp_pjheader_pengeluaranid_foreign')->references('id')->on('penjualanheader');
            $table->foreign('pengeluarandetailid', 'hpp_pjdetail_pengeluarandetailid_foreign')->references('id')->on('penjualandetail');
            $table->foreign('penerimaanid', 'hpp_pbheader_penerimaanid_foreign')->references('id')->on('pembelianheader');
            $table->foreign('penerimaandetailid', 'hpp_pbheader_penerimaandetailid_foreign')->references('id')->on('pembeliandetail');
            $table->foreign('productid', 'hpp_product_productid_foreign')->references('id')->on('product');
        });

        DB::statement("ALTER TABLE hpp DROP FOREIGN KEY hpp_pjheader_pengeluaranid_foreign");
        DB::statement("ALTER TABLE hpp DROP FOREIGN KEY hpp_pjdetail_pengeluarandetailid_foreign");
        DB::statement("ALTER TABLE hpp DROP FOREIGN KEY hpp_pbheader_penerimaanid_foreign");
        DB::statement("ALTER TABLE hpp DROP FOREIGN KEY hpp_pbheader_penerimaandetailid_foreign");
        DB::statement("ALTER TABLE hpp DROP FOREIGN KEY hpp_product_productid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hpp');
    }
}
