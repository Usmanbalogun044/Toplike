<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{
    protected $fillable = [
        'type',
        'file_path',
        
    ];
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
    
}
