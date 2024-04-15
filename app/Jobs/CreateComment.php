<?php

namespace App\Jobs;

use App\Models\Comments;
use App\Models\CommentsUser;
use App\Models\Files;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CreateComment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $comment = Comments::create([
            'text' => $this->data['text'],
            'parent_id' => $this->data['parent_id'],
        ]);

        $user = User::create([
            'username' => $this->data['name'],
            'email' => $this->data['email']
        ]);

        CommentsUser::create([
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);

        if (isset($this->data['file'])) {
            $file = $this->data['file'];
            if ($file->isValid()) {
                $fileName = time() . '_' . mb_strtolower($file->getClientOriginalName(), 'UTF-8');
                $fileData = file_get_contents($file->getRealPath());
                Storage::disk('public')->put('files/' . $fileName, "\xEF\xBB\xBF" . $fileData);

                $fileModel = Files::create([
                    'comment_id' => $comment->id,
                    'path' => $fileName
                ]);
            }
        }
        return $comment;
    }}
