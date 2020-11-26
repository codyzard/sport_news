<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'gender',
        'birthday',
        'address',
        'phone',
        'avartar_src'
    ];
    // User::find(1)->user_info()->create([
    //     'gender' => 1,
    //     'address' => 'LCL',
    //     'phone' => '0774455559'
    // ])
    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function news(){
        return $this->hasMany('App\Models\News');
    }

}
