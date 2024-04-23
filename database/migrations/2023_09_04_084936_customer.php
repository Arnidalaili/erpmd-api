<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Customer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('customers');

        Schema::create('customers', function (Blueprint $table) {
            $table->id();   
            $table->string('code',250)->nullable()->unique();
            $table->string('name',250)->nullable();
            $table->string('contactname',250)->nullable();
            $table->string('description',250)->nullable();
            $table->string('telephone',50)->nullable();
            $table->longText('address')->nullable();
            $table->longText('city')->nullable();
            $table->integer('postalcode')->nullable();
            $table->integer('statusaktif_id')->Length(11)->nullable();
            $table->string('modifiedby',50)->nullable();
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
        Schema::dropIfExists('customers');
    
    }
}
