<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'src',
        'description',
    ];

    public function news(){
        return $this->belongsTo('App\Models\News');
    }
}
