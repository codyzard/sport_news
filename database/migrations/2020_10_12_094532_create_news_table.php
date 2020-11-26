<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->string('title_img');
            $table->text('summary');
            $table->text('content')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->boolean('hot_or_nor')->nullable()->default(0); // 1 hot, 0 nor
            $table->integer('status')->default(0);
            $table->date('date_publish');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('slug');
            $table->string('content_image_dectect')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news');
    }
}
