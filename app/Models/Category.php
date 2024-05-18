<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'cat_title',
        'url',
        'created_by',
    ];
    function posts(){
        // return $this->hasMany('App\Models\Post');
        return $this->hasMany(Post::class);
    }
}
