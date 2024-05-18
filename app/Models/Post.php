<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    function category(){
        // return $this->belongsTo('App\Models\Category');
        return $this->belongsTo(Category::class,'cat_id','id');
    }
    function comments(){
        return $this->hasMany(Comment::class);
    }
}
