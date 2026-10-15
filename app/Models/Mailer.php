<?php

namespace App\Models;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class Mailer
{
    /**
     * Send a password reset email with a newly generated temporary password.
     *
     * @param  string       $clientID    The user's email / identifier shown in the email body
     * @param  string       $password    The plain-text temporary password to send
     * @param  string|null  $clientEmail The recipient email address
     * @return array
     */
    public function generate_password($clientID, $password, $clientEmail = null)
    {
        try {
            if (!$clientID || !$password || !$clientEmail) {
                throw new \Exception('Client ID, password, or recipient email missing.');
            }

            if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid recipient email address: ' . $clientEmail);
            }

            $safeClientID = htmlspecialchars($clientID, ENT_QUOTES, 'UTF-8');
            $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
            $fromName     = config('mail.from.name', 'Knowly Library');

            $htmlBody = '
                <div style="font-family: Arial, sans-serif; padding: 20px; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 12px;">
                    <h2 style="color: #059669; margin-bottom: 8px;">Knowly Library System</h2>
                    <p style="font-size: 15px; color: #4b5563; margin-top: 0;">Password Recovery Assistance</p>
                    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 15px 0;">
                    <p style="font-size: 15px;">Your Account Email: <strong>' . $safeClientID . '</strong></p>
                    <p style="font-size: 15px;">A new temporary password has been generated for your account:</p>
                    <div style="font-size: 24px; color: #059669; font-weight: bold; margin: 16px 0; background: #ecfdf5; padding: 12px 20px; border-radius: 8px; border: 1px dashed #059669; display: inline-block; letter-spacing: 2px;">
                        ' . $safePassword . '
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin-top: 15px;">
                        Please use this new password to log in. Once logged in, you can update your password at any time from your profile settings.
                    </p>
                    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 15px 0;">
                    <p style="font-size: 12px; color: #9ca3af;">
                        If you did not request a password reset, please ignore this email. Your account remains secure.
                    </p>
                </div>
            ';

            // Allow up to 20s for this operation to prevent PHP fatal timeout errors
            set_time_limit(20);

            Mail::html($htmlBody, function ($message) use ($clientEmail, $fromName) {
                $message->to(trim($clientEmail))
                        ->subject('Knowly Library – Password Reset Request');
            });

            Log::info("Password reset email sent successfully to: {$clientEmail}");
            return ['status' => 200];

        } catch (\Throwable $e) {
            Log::error("Password reset email failed for [{$clientEmail}]: {$e->getMessage()}");
            return ['status' => 500, 'message' => 'failure', 'error' => $e->getMessage()];
        }
    }
}
