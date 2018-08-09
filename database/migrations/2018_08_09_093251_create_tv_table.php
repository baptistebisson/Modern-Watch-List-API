<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTvTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tv', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_id')->unique();
            $table->string('title');
            $table->string('homepage');
            $table->string('other_title')->nullable($value = true);
            $table->string('rating')->nullable($value = true);
            $table->string('backdrop_path')->nullable($value = true);
            $table->string('description')->nullable($value = true);
            $table->integer('gross')->nullable($value = true);
            $table->integer('budget')->nullable($value = true);
            $table->string('country')->nullable($value = true);
            $table->string('filming_location')->nullable($value = true);
            $table->date('first_air_date')->nullable($value = true);
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
        Schema::dropIfExists('tv');
    }
}
