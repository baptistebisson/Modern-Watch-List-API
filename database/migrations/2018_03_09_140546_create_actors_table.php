<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('imdb_id');
            $table->integer('api_id')->unique();
            $table->string('name');
            $table->string('image_original')->nullable($value = true);
            $table->string('image_small')->nullable($value = true);
            $table->string('biography')->nullable($value = true);
            $table->string('place_of_birth')->nullable($value = true);
            $table->double('popularity', 7, 1)->nullable($value = true);
            $table->string('height')->nullable($value = true);
            $table->string('birth_date')->nullable($value = true);
            $table->string('death_date')->nullable($value = true);
            $table->integer('gender')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actors');
    }
}
