<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
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
        return $this->belongsToMany('App\Models\News', 'category_news', 'category_id', 'news_id');
    }

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    
    public function childs() {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public static function store_category($validated)
    {
        $category = new Category($validated);
        $category->save();

        return $category;
    }
}
