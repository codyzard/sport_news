<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tag_news', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id')->nullable();
            $table->unsignedBigInteger('news_id')->nullable();
            $table->timestamps();

            $table->foreign('tag_id')
                    ->references('id')
                    ->on('tags');
            $table->foreign('news_id')
                    ->references('id')
                    ->on('news');
            $table ->unique(array('tag_id', 'news_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tag_news');
    }
}
