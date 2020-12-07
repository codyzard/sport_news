<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title',
        'summary',
        'content',
        'view_count',
        'hot_or_nor',
        'status',
        'date_publish',
    ];

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function images()
    {
        return $this->hasMany('App\Models\Image');
    }

    public function videos()
    {
        return $this->hasMany('App\Models\Video');
    }

    public function categories()
    {
        return $this->belongsToMany('App\Models\Category', 'category_news', 'news_id', 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany('App\Models\Tag', 'tag_news', 'news_id', 'tag_id');
    }

    public static function saveNews(
        $title,
        $title_img,
        $summary,
        $content,
        $datetime,
        $status,
        $view_count,
        $hot_or_nor,
        $news_image_detect,
        $images_arr,
        $categories_arr,
        $tags_arr,
        $news_src
    ) {
        $news = new News;
        $news->title = $title;
        $news->title_img = $title_img;
        $news->summary = $summary;
        $news->content = $content;
        $news->date_publish = $datetime;
        $news->status = $status;
        $news->view_count = $view_count;
        $news->hot_or_nor = $hot_or_nor;
        $news->content_image_dectect = $news_image_detect;
        $news->news_src = $news_src;
        $news->save();
        $news->images()->saveMany($images_arr);
        $news->categories()->attach($categories_arr);
        $news->tags()->attach($tags_arr);
    }
}
