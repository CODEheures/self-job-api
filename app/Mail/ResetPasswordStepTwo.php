<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordStepTwo extends Mailable
{
    use Queueable, SerializesModels;
    /**
     * @var
     */
    private $newPassword;

    /**
     * Create a new message instance.
     *
     * @param $newPassword
     */
    public function __construct($newPassword)
    {
        //
        $this->newPassword = $newPassword;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.resetPasswordStepTwo', ['newPassword' => $this->newPassword]);
    }
}
