<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNotadebetheaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('notadebetheader');

        Schema::create('notadebetheader', function (Blueprint $table) {
            $table->id();
            $table->string('nobukti', 20)->unique();
            $table->date('tglbukti')->nullable();
            $table->unsignedBigInteger('pelunasanpiutangid')->nullable();
            $table->date('tglbuktipelunasanpiutang')->nullable();
            $table->unsignedBigInteger('pelunasanhutangid')->nullable();
            $table->date('tglbuktipelunasanhutang')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->date('tglcetak')->nullable();
            $table->integer('status')->length(11)->nullable();
            $table->string('modifiedby', 100)->nullable();
            $table->timestamps();

            $table->foreign('pelunasanpiutangid', 'nbheader_ppheader_pelunasanpiutangid_foreign')->references('id')->on('pelunasanpiutangheader');
            $table->foreign('pelunasanhutangid', 'nbheader_phheader_pelunasanhutangid_foreign')->references('id')->on('pelunasanhutangheader');
        });

        DB::statement("ALTER TABLE notadebetheader DROP FOREIGN KEY nbheader_ppheader_pelunasanpiutangid_foreign");
        DB::statement("ALTER TABLE notadebetheader DROP FOREIGN KEY nbheader_phheader_pelunasanhutangid_foreign");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notadebetheader');
    }
}
