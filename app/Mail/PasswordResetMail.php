<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;

    public function __construct(public User $user, string $token)
    {
        $this->resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset Your Password – ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password_reset');
    }
}
