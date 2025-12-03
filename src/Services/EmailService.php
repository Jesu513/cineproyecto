<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // Config SMTP
        $this->mail->isSMTP();
        $this->mail->Host = env('MAIL_HOST');
        $this->mail->SMTPAuth = true;
        $this->mail->Username = env('MAIL_USERNAME');
        $this->mail->Password = env('MAIL_PASSWORD');
        $this->mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
        $this->mail->Port = env('MAIL_PORT');

        // Remitente
        $this->mail->setFrom(env('MAIL_FROM'), env('MAIL_FROM_NAME'));
    }

    public function sendEmail(string $to, string $subject, string $html, ?string $attachment = null)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->isHTML(true);
            $this->mail->Body = $html;

            if ($attachment) {
                $this->mail->addAttachment($attachment);
            }

            $this->mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
}
