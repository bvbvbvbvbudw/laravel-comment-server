<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comments extends Model
{
    public $timestamps = true;
    protected $fillable = ['text', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(Comments::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Comments::class, 'parent_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(Files::class, 'comment_id');
    }

    public function user()
    {
        return $this->belongsToMany(User::class, 'comments_user', 'comment_id', 'user_id');
    }
}
