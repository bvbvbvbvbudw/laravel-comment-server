<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\CreateComment;
use App\Models\Comments;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use HTMLPurifier;
use HTMLPurifier_Config;
use Pusher\Pusher;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('id')) {
            return $this->fetchCommentById($request->id);
        }
        $comments = $this->getCachedComments();

        if ($comments) {
            return $this->paginateCachedComments($comments, $request);
        }

        return $this->fetchComments($request);
    }

    protected function getCachedComments()
    {
        return Cache::get('all_comments', []);
    }

    protected function paginateCachedComments($comments, $request)
    {
        if (!is_array($comments)) {
            $comments = $comments->toArray();
        }

        $sortBy = $request->sort_by;
        $sortOrder = $request->sort_order;
        $sortedComments = $this->sortComments($comments, $sortBy, $sortOrder);

        $page = Paginator::resolveCurrentPage('page');
        $perPage = 25;
        $slicedComments = array_slice($sortedComments, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $slicedComments,
            count($sortedComments),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        $data = $paginator->toArray();

        return [
            'success' => true,
            'cache' => true,
            'data' => [
                'current_page' => $data['current_page'],
                'data' => $data['data'],
                'first_page_url' => $data['first_page_url'],
                'from' => $data['from'],
                'last_page' => $data['last_page'],
                'last_page_url' => $data['last_page_url'],
                'next_page_url' => $data['next_page_url'],
                'path' => $data['path'],
                'per_page' => $data['per_page'],
                'prev_page_url' => $data['prev_page_url'],
                'to' => $data['to'],
                'total' => $data['total'],
            ],
        ];
    }

    protected function sortComments($comments, $sortBy, $sortOrder = 'asc')
    {
        $commentsCollection = collect($comments);

        switch ($sortBy) {
            case 'username':
                $sortedComments = $commentsCollection->sortBy('username');
                break;
            case 'email':
                $sortedComments = $commentsCollection->sortBy('email');
                break;
            case 'created_at':
                $sortedComments = $commentsCollection->sortBy('created_at');
                break;
            default:
                $sortedComments = $commentsCollection->sortBy('id');
                break;
        }

        if ($sortOrder === 'desc') {
            $sortedComments = $sortedComments->reverse();
        }

        $sortedCommentsArray = $sortedComments->values()->all();

        return $sortedCommentsArray;
    }


    protected function fetchComments(Request $request)
    {
        if ($request->has('id')) {
            return $this->fetchCommentById($request->id);
        }

        return $this->fetchSortedComments($request);
    }

    protected function fetchCommentById($id)
    {
        $comment = Comments::where('id', $id)->with('files', 'user')->get();
        $commentsWithChildren = $this->loadChildren($comment);

        return response()->json([
            'success' => true,
            'data' => $commentsWithChildren,
        ], 200);
    }

    protected function fetchSortedComments(Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'asc');

        $query = Comments::with('files', 'user')->whereNull('parent_id');

        if ($sortBy == 'username' || $sortBy == 'email') {
            $query->addSelect(['user_sort' => User::select($sortBy)
                ->join('comments_user', 'users.id', '=', 'comments_user.user_id')
                ->whereColumn('comments.id', 'comments_user.comment_id')
                ->limit(1)
            ])->orderBy('user_sort', $sortOrder);
        }

        if ($sortBy == 'created_at') {
            $query->orderBy($sortBy, $sortOrder);
        }

        if (!$request->has('sort_by') && !$request->has('sort_order')) {
            $query->orderBy('id', 'desc');
        }

        $comments = $query->paginate(25);
        $commentsWithChildren = $this->loadChildren($comments);

        return response()->json([
            'success' => true,
            'data' => $commentsWithChildren,
        ], 200);
    }

    public function loadChildren($comments)
    {
        foreach ($comments as $comment) {
            $comment->children = $this->loadChildren($comment->children()->with('files', 'user')->get());
        }
        return $comments;
    }

    public function store(Request $request)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'strong,a[href|title],i,code');
        $purifier = new HTMLPurifier($config);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'name' => ['required', 'regex:/^[a-zA-Z0-9]+$/'],
            'text' => ['required', 'string', function ($attribute, $value, $fail) use ($purifier) {
                $clean_html = $purifier->purify($value);
                if ($clean_html === '') {
                    $fail('Invalid characters detected in the text.');
                }
            }],
            'file' => ['nullable', 'file', 'max:1024', 'mimes:jpg,png,gif,txt'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();
        $data['parent_id'] = $request -> parent_id;
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if ($file->getSize() > 1024 * 1024) {
                return response()->json(['error' => 'File size exceeds the limit of 1MB.'], 400);
            }

            $allowedFormats = ['jpg', 'png', 'gif', 'txt'];
            $fileExtension = $file->getClientOriginalExtension();

            if (!in_array($fileExtension, $allowedFormats)) {
                return response()->json(['error' => 'Unsupported file format. Supported formats: JPG, PNG, GIF, TXT.'], 400);
            }

            $data['file'] = $file;
        }

        $createComment = new CreateComment($data); // queue
        $comment = $createComment->handle(); // queue
        event(new CommentUpdated($comment)); // events , send email

        $pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'useTLS' => true,
            ]
        );

        $pusher->trigger('comment-channel', 'comment-updated', [
            'comment' => $comment->load('files','user'),
        ]);

        return response()->json(['success' => true], 200);
    }
}
