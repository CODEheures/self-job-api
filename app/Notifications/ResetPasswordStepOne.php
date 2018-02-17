<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordStepOne extends Notification
{
    use Queueable;

    private $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject(trans('strings.mail.resetPasswordStepOne.subject'))
                    ->greeting(trans('strings.mail.resetPasswordStepOne.greeting', ['username' => $notifiable->name]))
                    ->line(trans('strings.mail.resetPasswordStepOne.line1'))
                    ->action(trans('strings.mail.resetPasswordStepOne.action'), url(route('confirmResetPassword', ['token' => $this->token, 'language' => $notifiable->pref_language])))
                    ->salutation(trans('strings.mail.resetPasswordStepOne.salutation'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
