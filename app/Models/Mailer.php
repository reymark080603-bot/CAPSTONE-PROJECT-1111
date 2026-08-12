<?php

namespace App\Models;

use PHPMailer\PHPMailer\PHPMailer;
use Exception;
use Illuminate\Support\Facades\Log;

class Mailer
{
    private function configureMailer()
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = env('MAIL_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('MAIL_USERNAME', 'dealacarloise@gmail.com');
        $mail->Password   = env('MAIL_PASSWORD', 'igpswwyawxrarhac');
        $mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
        $mail->Port       = env('MAIL_PORT', 587);

        return $mail;
    }

    public function generate_password($clientID, $password, $clientEmail = null)
    {
        try {
            if ($clientID && $password && $clientEmail) {
                $mail = $this->configureMailer();

                if (!$mail) {
                    throw new Exception('Mailer not properly configured.');
                }

                $fromAddress = env('MAIL_FROM_ADDRESS', 'dealacarloise@gmail.com');
                $fromName = env('MAIL_FROM_NAME', 'Knowly Library System');

                $mail->setFrom($fromAddress, $fromName);
                $mail->addAddress(trim($clientEmail), 'Knowly User');

                $mail->isHTML(true);
                $mail->Subject = 'Knowly Library - Password Reset Request';

                $safeClientID = htmlspecialchars($clientID, ENT_QUOTES, 'UTF-8');
                $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

                $mail->Body = '
                   <div style="font-family: Arial, sans-serif; padding: 20px; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 12px;">
                        <h2 style="color: #059669; margin-bottom: 8px;">Knowly Library System</h2>
                        <p style="font-size: 15px; color: #4b5563; margin-top: 0;">Password Recovery Assistance</p>
                        <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 15px 0;">
                        <p style="font-size: 15px;">Your Account ID / Email: <strong>' . $safeClientID . '</strong></p>
                        <p style="font-size: 15px;">A new temporary password has been generated for your account:</p>
                        <div style="font-size: 24px; color: #059669; font-weight: bold; margin: 16px 0; background: #ecfdf5; padding: 12px 20px; border-radius: 8px; border: 1px dashed #059669; display: inline-block; letter-spacing: 2px;">
                            ' . $safePassword . '
                        </div>
                        <p style="font-size: 14px; color: #6b7280; margin-top: 15px;">
                            Please use this new password to log in. Once logged in, you can update your password at any time.
                        </p>
                    </div>
                ';

                if ($mail->send()) {
                    return ["status" => 200];
                } else {
                    throw new Exception('Mail could not be sent.');
                }
            } else {
                throw new Exception('Client ID, password, or recipient email missing.');
            }
        } catch (Exception $e) {
            Log::error("Email verification could not be sent. Error: {$e->getMessage()}");
            return ["status" => 500, "message" => "failure", "error" => $e->getMessage()];
        }
    }
}
