<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEpisodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_id')->unique();
            $table->string('season_id');
            $table->foreign('season_id')->references('api_id')->on('seasons')->onDelete('cascade');
            $table->integer('episode_number');
            $table->string('title');
            $table->longText('description')->nullable($value = true);
            $table->date('release_date')->nullable($value = true);
            $table->boolean('popular')->default(false);
            $table->string('still_path');
            $table->integer('show_id');
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
        Schema::dropIfExists('episodes');
    }
}
