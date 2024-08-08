<?php
require_once 'smtp/PHPMailerAutoload.php';

class Mailer {
    private static $username = "mr.sandeepmcscet@gmail.com";
    private static $password = "gtjy yrfo bhtd kjxf";
    private static $from = "email"; // Replace with your actual email address

    public static function sendEmail($to, $subject, $msg) {
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 587;
        $mail->IsHTML(true);
        $mail->CharSet = 'UTF-8';
        //$mail->SMTPDebug = 2; // Uncomment for debugging SMTP issues

        $mail->Username = self::$username;
        $mail->Password = self::$password;
        $mail->SetFrom(self::$from);
        $mail->Subject = $subject;
        $mail->Body = $msg;
        $mail->AddAddress($to);

        // Disable SSL verification (for localhost or development purposes)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => false
            )
        );

        if (!$mail->Send()) {
            return $mail->ErrorInfo;
        } else {
            return 'Sent';
        }
    }
}
?>
