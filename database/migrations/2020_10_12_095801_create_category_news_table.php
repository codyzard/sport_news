<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_news', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('news_id')->nullable();
            $table->timestamps();

            $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->onDelete('cascade');
            $table->foreign('news_id')
                    ->references('id')
                    ->on('news')
                    ->onDelete('cascade');
            $table ->unique(array('category_id', 'news_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_news');
    }
}
