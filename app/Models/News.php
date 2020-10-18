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

    public function author(){
        return $this->belongsTo('App\Models\UserInfo');
    }

    public function images(){
        return $this->hasMany('App\Models\Image');
    }

    public function videos(){
        return $this->hasMany('App\Models\Video');
    }

    public function categories(){
        return $this->belongsToMany('App\Models\Category', 'categories_news', 'news_id', 'category_id');
    }

    public function tags(){
        return $this->belongsToMany('App\Models\Tag', 'tags_news', 'news_id', 'tag_id');
    }

}
