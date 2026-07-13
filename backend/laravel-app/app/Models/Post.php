<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'content',
        'image_url',
        'authenticity_score',
        'view_count',
        'embedding',
    ];

    protected $casts = [
        'authenticity_score' => 'float',
        'view_count' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }
}
