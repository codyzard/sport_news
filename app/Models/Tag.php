<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'name',
    ];

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function news(){
        return $this->belongsToMany('App\Models\News', 'tag_news', 'tag_id', 'news_id');
    }
}
