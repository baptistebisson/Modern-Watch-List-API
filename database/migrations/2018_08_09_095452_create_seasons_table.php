<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSeasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_id')->unique();
            $table->integer('tv_id')->unsigned();
            $table->foreign('tv_id')->references('id')->on('tv')->onDelete('cascade');
            $table->string('title');
            $table->string('rating')->nullable($value = true);
            $table->string('description')->nullable($value = true);
            $table->date('release_date')->nullable($value = true);
            $table->boolean('popular')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seasons');
    }
}
