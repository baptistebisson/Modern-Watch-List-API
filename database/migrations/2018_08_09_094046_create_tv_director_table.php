<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTvDirectorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tv_director', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('director_id')->unsigned();
            $table->foreign('director_id')->references('id')->on('directors')->onDelete('cascade');
            $table->integer('tv_id')->unsigned();
            $table->foreign('tv_id')->references('id')->on('tv')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tv_director');
    }
}
