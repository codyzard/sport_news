<?php

namespace App\Helper;

use App\Models\Image;

class CrawlerHelper{
    public static function clean_image_trash(){
        Image::where(['news_id' =>null])->delete();
    }
}
