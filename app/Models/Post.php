<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'challenge_id',
        'caption',
        'media_url',
        'media_type',
        'thumbnail_url',
        'status',
        'likes_count',
        'comments_count',
        'views_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function views()
    {
        return $this->hasMany(PostView::class);
    }
}
