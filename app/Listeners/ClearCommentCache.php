<?php

namespace App\Listeners;

use App\Events\CommentUpdated;
use App\Models\Comments;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearCommentCache implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CommentUpdated $event)
    {
        Cache::delete('all_comments');
        $comments = Comments::with('files', 'user')->whereNull('parent_id')->get();
        $this->loadChildren($comments);
//        $comment->load('files','user');

//        if($comment->parent_id === null){
//            $comments = Cache::get('all_comments', []);
//            $comments[] = $comment;
//        } else {
//            $parent = Comments::find($comment->parent_id);
//            $parent->children = $this->loadChildren($parent->children()->with('files', 'user')->get());
//            $parent['children'][] = $comment;
//            $comments = $this->loadChildren(Comments::with(['files', 'user'])->whereNull('parent_id')->get());
//            Cache::delete('all_comments');
//            $comments = Comments::with('files','user')->get();
//            $this->loadChildren($comments);
//        }

//        Log::info($comments);
        Cache::put('all_comments', $comments, now()->addMinutes(10));
    }

    public function loadChildren($comments)
    {
        foreach ($comments as $comment) {
            $comment->children = $this->loadChildren($comment->children()->with('files', 'user')->get());
        }
        return $comments;
    }
}
