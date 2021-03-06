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
            $table->string('pictureUrl')->nullable()->default(null);
            $table->boolean('is_internal_private')->default(false);
            $table->boolean('is_publish')->default(false);
            //relations
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('company_id')->unsigned()->index();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
