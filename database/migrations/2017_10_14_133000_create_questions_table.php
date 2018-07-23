<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->tinyInteger('type')->unsigned();
            $table->tinyInteger('order')->unsigned();
            $table->text('datas');
            $table->string('hash', 128);
            $table->boolean('inLibrary')->default(true);
            $table->tinyInteger('library_type')->default(1);
            $table->string('pref_language',4)->nullable()->default(null);
            //relations
            $table->integer('advert_id')->unsigned()->index();
            $table->foreign('advert_id')->references('id')->on('adverts')->onDelete('cascade');
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
        Schema::dropIfExists('questions');
    }
}
