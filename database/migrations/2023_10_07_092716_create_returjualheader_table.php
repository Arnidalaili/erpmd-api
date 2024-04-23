<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateReturjualheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('returjualheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('penjualanid')->nullable();
            $table->unsignedBigInteger('customerid')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->double('total',15,2)->nullable();
            $table->date('tglcetak')->nullable();
            $table->string('flag', 20)->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->string('editingby', 100)->nullable();
            $table->dateTime('editingat')->nullable();
            $table->timestamps();

            $table->foreign('penjualanid', 'rjheader_pjheader_penjualanid_foreign')->references('id')->on('penjualanheader');
            $table->foreign('customerid', 'rjheader_customer_customerid_foreign')->references('id')->on('customer');
        });

        DB::statement("ALTER TABLE returjualheader DROP FOREIGN KEY rjheader_customer_customerid_foreign");
        DB::statement("ALTER TABLE returjualheader DROP FOREIGN KEY rjheader_pjheader_penjualanid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('returjualheader');
    }
}
