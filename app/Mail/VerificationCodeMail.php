<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $username;

    public function __construct(string $code, string $username)
    {
        $this->code     = $code;
        $this->username = $username;
    }

    public function build(): self
    {
        return $this->subject('Wingman — Your Verification Code')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto;'>
                            <h2 style='color: #1a1a1a;'>Welcome to Wingman, {$this->username}!</h2>
                            <p style='color: #444;'>Your verification code is:</p>
                            <div style='background: #f4f4f4; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                                <span style='font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1a1a1a;'>{$this->code}</span>
                            </div>
                            <p style='color: #444;'>This code expires in <strong>10 minutes</strong>.</p>
                            <p style='color: #888; font-size: 12px;'>If you did not request this, please ignore this email.</p>
                        </div>
                    ");
    }
}