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
            $table->string('status');
            $table->string('homepage');
            $table->integer('duration')->nullable($value = true);
            $table->string('other_title')->nullable($value = true);
            $table->string('rating')->nullable($value = true);
            $table->string('backdrop_path')->nullable($value = true);
            $table->longText('description')->nullable($value = true);
            $table->string('network');
            $table->integer('gross')->nullable($value = true);
            $table->integer('budget')->nullable($value = true);
            $table->string('country')->nullable($value = true);
            $table->string('filming_location')->nullable($value = true);
            $table->date('first_air_date')->nullable($value = true);
            $table->date('last_air_date')->nullable($value = true);
            $table->boolean('popular')->default(false);
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
        Schema::dropIfExists('tv');
    }
}
