<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'link',
        'user_id',
    ];

    // Optional: relation to the user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
