<?php

require ZERO_PATH_LIBRARY . '/PHPMailer/PHPMailerAutoload.php';

/**
 * Lib. PHP email transport class
 *
 * @package Zero.Lib
 * @author Konstantin Shamiev aka ilosa <konstantin@phpzero.com>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
class Zero_System_Mail
{
    /**
     * Отправка сообщения
     *
     * @param array $from from
     * @param array $to to
     * @param array $reply to
     * @param string $subject subject
     * @param string $message message
     * @param array $attach attachments
     * @return int количесвто ошибок отправления
     */
    public static function Send($from, $to, $reply, $subject, $message, $attach = [])
    {
        $cntFail = 0;
        foreach ($to as $row)
        {
            $mail = new PHPMailer;
            //  Header mail
            $mail->CharSet = 'utf-8';
            $mail->setFrom($from['Email'], $from['Name']);
            $mail->addReplyTo($reply['Email'], $reply['Name']);
            $mail->addAddress($row['Email'], $row['Name']);
            //  The message body
            $mail->Subject = $subject;
            $mail->msgHTML($message);
            $mail->AltBody = $message;
            //  Attachments
            foreach ($attach as $path => $name)
            {
                $mail->AddAttachment($path, $name);
            }
            //  Send
            if ( !$mail->send() )
            {
                $cntFail++;
                Zero_Logs::Set_Message_Error("From: {$from['Email']}; To: {$row['Email']}; Subject: {$subject}");
            }
            //
            $mail->ClearAddresses();
            $mail->ClearAttachments();
        }
        return $cntFail;
    }
}
