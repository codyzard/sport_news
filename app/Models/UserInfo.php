<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'name',
        'gender',
        'birthday',
        'address',
        'phone',
        'avartar_src'
    ];

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function account(){
        return $this->hasOne('App\Models\User');
    }

    public function news(){
        return $this->hasMany('App\Models\News');
    }

}
