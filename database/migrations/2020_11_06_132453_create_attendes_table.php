<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('attende_code_id')->references('id')->on('attende_codes')->nullable();
            $table->foreignId('attende_status_id')->references('id')->on('attende_statuses');
            $table->datetime('attend_time')->nullable();
            $table->float('latitude')->default(0);
            $table->float('longitude')->default(0);
            $table->text('address')->nullable();
            $table->text('photo')->nullable();
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
        Schema::dropIfExists('attendes');
    }
}
