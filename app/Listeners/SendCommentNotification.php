<?php

namespace App\Listeners;

use App\Models\Comments;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;

class SendCommentNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $comment = $event->comment;

        $mail = new PHPMailer(true);
        try {
            // Настройки сервера SMTP
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION');
            $mail->Port = env('MAIL_PORT');

            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress('b12522033@gmail.com', 'Recipient Name');

            $mail->isHTML(true);
            $htmlContent = view('emails.comment_notification', ['comment' => $comment])->render();
            $mail->Subject = 'New Comment Notification';
            $mail->Body = $htmlContent;

//            Log::info($comment);
            $mail->send();
            Log::info('Email sent successfully');
        } catch (Exception $e) {
            Log::error('Email sending failed: ' . $mail->ErrorInfo);
        }
    }

}
