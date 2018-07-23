<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TeamInvitation extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
                    ->subject(trans('strings.mail.teamInvitation.subject'))
                    ->greeting(trans('strings.mail.teamInvitation.greeting'))
                    ->line(trans('strings.mail.teamInvitation.line1'))
                    ->action(trans('strings.mail.teamInvitation.action'), url(env('APP_URL_FRONT') . env('APP_FRONT_REGISTER_ROUTE')))
                    ->line(trans('strings.mail.teamInvitation.line2', ['companyName' => $notifiable->company->name]))
                    ->salutation(trans('strings.mail.teamInvitation.salutation', ['username' => auth()->user()->name]));
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
