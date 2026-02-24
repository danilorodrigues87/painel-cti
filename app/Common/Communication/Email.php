<?php
namespace App\Common\Communication;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Common\Environment;

Environment::load(__DIR__.'/../../');

define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_CHARSET', getenv('SMTP_CHARSET'));
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL'));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME'));

class Email {

    // CREDENCIAIS
    const HOST    = SMTP_HOST;
    const USER    = SMTP_USER;
    const PASS    = SMTP_PASS;
    const PORT    = SMTP_PORT;
    const CHARSET = SMTP_CHARSET;

    // REMETENTE
    const FROM_EMAIL = SMTP_FROM_EMAIL;
    const FROM_NAME  = SMTP_FROM_NAME;

    private $error;

    public function getError(){
        return $this->error;
    }

    public function sendEmail(
        $addresses,
        $subject,
        $body,
        $attachments = [],
        $ccs = [],
        $bccs = []
    ){
        $this->error = null;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = self::HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::USER;
            $mail->Password   = self::PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = self::PORT;
            $mail->CharSet    = self::CHARSET;
            $mail->Encoding   = 'base64';

            $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);

            foreach ((array)$addresses as $address) {
                if (!empty($address)) {
                    $mail->addAddress($address);
                }
            }

            foreach ((array)$attachments as $attachment) {
                if (!empty($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }

            foreach ((array)$ccs as $cc) {
                if (!empty($cc)) {
                    $mail->addCC($cc);
                }
            }

            foreach ((array)$bccs as $bcc) {
                if (!empty($bcc)) {
                    $mail->addBCC($bcc);
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}
