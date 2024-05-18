<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = [
        'comment_content',
        'post_id',
        'user_id',
        'parent_comment_id',
        'created_at',
    ];
    function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
    function replies()
    {
        return $this->hasMany(Comment::class,'parent_comment_id','id');
    }
    function comment()
    {
        // return $this->belongsTo(Comment::class, 'parent_comment_id', 'id');
    }
}
