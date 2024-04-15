<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentsUser extends Model
{
    use HasFactory;
    protected $table = 'comments_user';
    public $timestamps = false;
    protected $fillable = ['comment_id', 'user_id'];
}
