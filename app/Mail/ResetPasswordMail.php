<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetLink;
    public $email;

    public function __construct($resetLink, $email)
    {
        $this->resetLink = $resetLink;
        $this->email = $email;
    }

    /**
     * Construir el mensaje.
     */
    public function build()
    {
        return $this->subject('🔐 Recupera tu contraseña')
                    ->view('emails.reset-password');
    }
}
