<?php declare(strict_types=1);

namespace App\Base\Service;

use App\Software;
use League\Plates\Engine;
use Monolog\Level;
use Monolog\Logger;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    public function __construct(
        private readonly Logger $logger
    )
    {
    }

    public function sendPasswordResetEmail(object $account, string $token): bool
    {
        try {
            $mail = new PHPMailer(true);
            
            // E-Mail Adresse setzen
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('no-reply@' . $_ENV['SOFTWARE_HOST'], $_ENV['SOFTWARE_APPNAME']);
            $mail->addAddress($account->email);
            
            // Betreff setzen
            $mail->Subject = 'Passwort-Zurücksetzen';
            
            // Körper der E-Mail
            $mail->Body = $this->loadTemplate('email/passwordReset', ['account' => $account, 'token' => $token, 'appName' => $_ENV['SOFTWARE_APPNAME']]);
            $mail->isSMTP();
            $mail->Host = $_ENV['SOFTWARE_SMTP_HOST'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SOFTWARE_SMTP_USERNAME'] ?? '';
            $mail->Password = $_ENV['SOFTWARE_SMTP_PASSWORD'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // E-Mail senden
            if ($mail->send()) {
                $this->logger->info(sprintf('Password reset email sent to %s', $account->email), [
                    'token' => $token,
                    'account_uuid' => $account->uuid->toString(),
                ]);
                return true;
            } else {
                $this->logger->error(sprintf('Failed to send password reset email to %s: %s', $account->email, $mail->ErrorInfo));
                return false;
            }
        } catch (PHPMailerException $e) {
            $this->logger->error(sprintf('PHPMailer exception when sending password reset email to %s: %s', $account->email, $e->getMessage()));
            return false;
        }
    }

    public function sendEmail(string $to, string $subject, string $body, ?string $appName = null): bool
    {
        try {
            $mail = new PHPMailer(true);
            
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('no-reply@' . $_ENV['SOFTWARE_HOST'], $appName ?? $_ENV['SOFTWARE_APPNAME']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isSMTP();
            $mail->Host = $_ENV['SOFTWARE_SMTP_HOST'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SOFTWARE_SMTP_USERNAME'] ?? '';
            $mail->Password = $_ENV['SOFTWARE_SMTP_PASSWORD'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            if ($mail->send()) {
                $this->logger->info(sprintf('Email sent to %s', $to));
                return true;
            } else {
                $this->logger->error(sprintf('Failed to send email to %s: %s', $to, $mail->ErrorInfo));
                return false;
            }
        } catch (PHPMailerException $e) {
            $this->logger->error(sprintf('PHPMailer exception when sending email to %s: %s', $to, $e->getMessage()));
            return false;
        }
    }

    private function loadTemplate(string $templateName, array $data): string
    {
        $engine = new Engine(__DIR__.'/../../template');
        return $engine->render($templateName, $data);
    }

}
