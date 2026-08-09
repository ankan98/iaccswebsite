<?php
require 'vendor/autoload.php';

/**
 * @param string $to              Recipient email
 * @param string $subject         Email subject
 * @param string $msg             HTML message body
 * @param array|string $bcc       Single email string or an array of emails
 * @param array|string $attachments Single file path string or an array of file paths
 */
function smtp_mailer($to, $subject, $msg, $bcc = [], $attachments = []) {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    
    try {
        // --- Server Settings ---
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = 'tls';
        $mail->Host       = "smtp.gmail.com";
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Username   = "noreply.accsmembership@gmail.com";
        $mail->Password   = "iianqvpnqehjuchu"; 
        
        // --- SSL Options ---
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
        );

        // --- Recipients ---
        $mail->setFrom("noreply.accsmembership@gmail.com", "IACCS");
        $mail->addAddress($to);
        
        // Handle Multiple BCCs
        if (!empty($bcc)) {
            $bccArray = is_array($bcc) ? $bcc : [$bcc];
            foreach ($bccArray as $bccEmail) {
                $mail->addBCC(trim($bccEmail));
            }
        }

        // --- Handle Multiple Attachments ---
        if (!empty($attachments)) {
            // Force it into an array if a single string is passed
            $fileArray = is_array($attachments) ? $attachments : [$attachments];
            
            foreach ($fileArray as $filePath) {
                if (file_exists($filePath)) {
                    $mail->addAttachment($filePath); 
                }
            }
        }

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $msg;
        $mail->AltBody = strip_tags($msg);

        if ($mail->send()) {
            return true;
        }
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
        
    }
}
