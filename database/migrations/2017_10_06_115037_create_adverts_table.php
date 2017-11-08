<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdvertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adverts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('documentIndex', 24);
            $table->string('title', \App\Advert::titleLength);
            $table->text('description');
            $table->string('location');
            $table->string('formatted_address');
            $table->text('tags')->nullable()->default(null);
            $table->text('requirements')->nullable()->default(null);
            $table->string('contract', \App\Advert::contractLenght)->nullable()->default(null);
            //relations
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adverts');
    }
}
