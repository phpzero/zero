<?php

/**
 * Работа с почтой (почтовыми сообщениями).
 *
 * Поставнока в очередь на отправку.
 * Получение очереди для отправления по почте.
 *
 * @package Helper.Mail
 * @author Konstantin Shamiev aka ilosa <konstantin@shamiev.ru>
 * @date 2015-01-01
 */
class Helper_Mail
{
    /**
     * Возможные методы отправки почтового сообщения
     */
    const SendMessage = 'SendMessage';
    const SendMessageAuth = 'SendMessageAuth';
    const SendMessageAuthSsl = 'SendMessageAuthSsl';

    /**
     *
     * @var string $smtp_username - логин
     * @var string $smtp_password - пароль
     * @var string $smtp_host - хост
     * @var string $smtp_from - от кого
     * @var integer $smtp_port - порт
     * @var string $smtp_charset - кодировка
     *
     */
    private $smtp_username;

    private $smtp_password;

    private $smtp_host;

    private $smtp_port;

    private $smtp_charset;

    /**
     * Конструткор
     *
     * @param string $smtp_username
     * @param string $smtp_password
     * @param string $smtp_host
     * @param int $smtp_port
     * @param string $smtp_charset
     */
    public function __construct($smtp_username, $smtp_password, $smtp_host, $smtp_port = 25, $smtp_charset = "UTF-8")
    {
        $this->smtp_username = $smtp_username;
        $this->smtp_password = $smtp_password;
        $this->smtp_host = $smtp_host;
        $this->smtp_port = $smtp_port;
        $this->smtp_charset = $smtp_charset;
    }

    /**
     * Отправка письма
     *
     * @param array $data [
     *      'Reply' => ['Name' => 'ReplyName', 'Email' => 'reply@mail.ru'],
     *      'From' => ['Name' => 'FromName', 'Email' => 'from@mail.ru'],
     *      'Subject' => 'Тема сообщения',
     *      'Message' => 'Текст или тело сообщения',
     *      'To' => [
     *          'Recipient@mail.ru' => 'NameRecipient',
     *      ],
     *      'Attach' => [
     *          'pathFile' => 'nameFile',
     *      ],
     * ]
     * @return bool|string В случаи отправки вернет true, иначе текст ошибки
     */
    private function sending($data)
    {
        if ( empty($data['From']['Name']) )
            $data['From']['Name'] = Zero_App::$Config->Site_Name;
        if ( empty($data['From']['Email']) )
            $data['From']['Email'] = Zero_App::$Config->Site_Email;

        $contentMail = "";
        // reply
        if ( isset($data['Reply']) )
            // $contentMail .= "Reply-To: {$data['Reply']['Name']} <{$data['Reply']['Email']}>\r\n";
            $contentMail .= 'Reply-To: =?' . $this->smtp_charset . '?B?' . base64_encode("{$data['Reply']['Name']}") . "=?= <{$data['Reply']['Email']}>\r\n";
        // from
        // $contentMail .= "From: {$data['From']['Name']} <{$data['From']['Email']}>\r\n";
        $contentMail .= 'From: =?' . $this->smtp_charset . '?B?' . base64_encode("{$data['From']['Name']}") . "=?= <{$data['From']['Email']}>\r\n";
        // to
        $contentMail .= "To:";
        foreach ($data['To'] as $key => $val)
        {
            $contentMail .= " {$val} <{$key}>,";
        }
        $contentMail = rtrim($contentMail, ',');
        $contentMail .= "\r\n";
        // subject
        $contentMail .= 'Subject: =?' . $this->smtp_charset . '?B?' . base64_encode($data['Subject']) . "=?=\r\n";
        // заголовки
        $boundary = "--" . md5(uniqid(time())); // генерируем разделитель
        $contentMail .= "Date: " . date("D, d M Y H:i:s") . " UT\r\n";
        $contentMail .= "MIME-Version: 1.0\r\n";
        $contentMail .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n\r\n";
        // сообщения
        $contentMail .= "--" . $boundary . "\r\n";
        $contentMail .= "Content-type: text/html; charset={$this->smtp_charset}\r\n";
        $contentMail .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $contentMail .= $data['Message'] . "\r\n\r\n";
        // вложения
        if ( isset($data['Attach']) )
            foreach ($data['Attach'] as $path => $fileName)
            {
                $contentMail .= "--" . $boundary . "\r\n";
                $contentMail .= "Content-Type: application/octet-stream; name=\"" . $fileName . "\"\r\n";
                $contentMail .= "Content-Transfer-Encoding: base64\r\n";
                $contentMail .= "Content-Disposition: attachment; filename=\"" . $fileName . "\"\r\n\r\n";
                $contentMail .= chunk_split(base64_encode(file_get_contents($path))) . "\r\n\r\n";
            }
        $contentMail .= "--" . $boundary . "--";

        try
        {
            if ( !$socket = @fsockopen($this->smtp_host, $this->smtp_port, $errorNumber, $errorDescription, 30) )
            {
                throw new Exception($errorNumber . "." . $errorDescription, 409);
            }
            if ( !$this->_parseServer($socket, "220") )
            {
                throw new Exception('Connection error', 409);
            }

            $server_name = Zero_App::$Config->Site_Domain;
            fputs($socket, "HELO $server_name\r\n");
            if ( !$this->_parseServer($socket, "250") )
            {
                fclose($socket);
                throw new Exception('Error of command sending: HELO', 409);
            }

            fputs($socket, "AUTH LOGIN\r\n");
            if ( !$this->_parseServer($socket, "334") )
            {
                fclose($socket);
                throw new Exception('Autorization error', 409);
            }

            fputs($socket, base64_encode($this->smtp_username) . "\r\n");
            if ( !$this->_parseServer($socket, "334") )
            {
                fclose($socket);
                throw new Exception('Autorization error', 409);
            }

            fputs($socket, base64_encode($this->smtp_password) . "\r\n");
            if ( !$this->_parseServer($socket, "235") )
            {
                fclose($socket);
                throw new Exception('Autorization error', 409);
            }

            fputs($socket, "MAIL FROM: <" . $this->smtp_username . ">\r\n");
            if ( !$this->_parseServer($socket, "250") )
            {
                fclose($socket);
                throw new Exception('Error of command sending: MAIL FROM', 409);
            }

            foreach ($data['To'] as $key => $val)
            {
                fputs($socket, "RCPT TO: <" . $key . ">\r\n");
                if ( !$this->_parseServer($socket, "250") )
                {
                    fclose($socket);
                    throw new Exception('Error of command sending: RCPT TO ' . $key, 409);
                }
            }

            //            fputs($socket, "\r\n");

            //                        $rcpt_to = '';
            //                        foreach ($data['To'] as $key => $val)
            //                        {
            //                            $rcpt_to .= " <" . $key . ">,";
            //                        }
            //                        $rcpt_to = rtrim($rcpt_to, ',');
            //                        fputs($socket, "RCPT TO:{$rcpt_to}\r\n");
            //                        if ( !$this->_parseServer($socket, "250") )
            //                        {
            //                            fclose($socket);
            //                            throw new Exception('Error of command sending: RCPT TO ' . $key, 409);
            //                        }

            fputs($socket, "DATA\r\n");
            if ( !$this->_parseServer($socket, "354") )
            {
                fclose($socket);
                throw new Exception('Error of command sending: DATA', 409);
            }
            fputs($socket, $contentMail . "\r\n.\r\n");
            if ( !$this->_parseServer($socket, "250") )
            {
                fclose($socket);
                throw new Exception("E-mail didn't sent", 409);
            }

            fputs($socket, "QUIT\r\n");
            fclose($socket);
        } catch ( Exception $e )
        {
            return $e->getMessage();
        }
        return true;
    }

    /**
     * Обработчик ошибок
     *
     * @param $socket
     * @param $response
     * @return bool
     */
    private function _parseServer($socket, $response)
    {
        $responseServer = '';
        while ( @substr($responseServer, 3, 1) != ' ' )
        {
            if ( !($responseServer = fgets($socket, 256)) )
            {
                return false;
            }
        }
        if ( !(substr($responseServer, 0, 3) == $response) )
        {
            return false;
        }
        return true;
    }

    /**
     * Постановка почтового сообщения в очередь
     *
     * @param string $name краткое название сообщения для идетификации
     * @param string $description подробное описание сообщения для идетификации
     * @param array $content [
     *      'Reply' => ['Name' => 'ReplyName', 'Email' => 'reply@mail.ru'],
     *      'From' => ['Name' => 'FromName', 'Email' => 'from@mail.ru'],
     *      'To' => [
     *          'Recipient@mail.ru' => 'NameRecipient',
     *      ],
     *      'Subject' => 'Тема сообщения',
     *      'Message' => 'Текст или тело сообщения',
     *      'Attach' => [
     *          'pathFile' => 'nameFile',
     *      ],
     * ]
     * @param string $method метод реализующий отправку почты
     * @return int
     */
    public static function Queuing($name, $description, $content, $method = self::SendMessage)
    {
        $sql = "INSERT MailQueue SET
            Name = " . Zero_DB::EscT($name) . ",
            Description = " . Zero_DB::EscT($description) . ",
            Content = '" . json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "',
            Date = NOW(),
            RetryCnt = 0,
            Method = " . Zero_DB::EscT($method) . "
        ";
        return Zero_DB::Insert($sql);
    }

    /**
     * Отправка очереди почтовых сообщений адресатам.
     *
     * Отправка почты ранее поставленной в очередь
     * Через БД
     */
    public static function SendConsole()
    {
        $sql = "
        SELECT
          *
        FROM MailQueue
        WHERE
          DateSend IS NULL
          AND RetryCnt < " . Zero_App::$Config->Mail_RetryCnt . "
        ORDER BY
          Date ASC
        LIMIT 0, 100
        ";
        $rows = Zero_DB::Select_Array_Index($sql);
        foreach ($rows as $id => $row)
        {
            $data = json_decode($row["Content"], true);
            if ( !method_exists(__CLASS__, $method = $row['Method']) )
                $method = self::SendMessage;
            $cntFail = self::$method($data);
            if ( 0 < $cntFail )
                $sql = "UPDATE MailQueue SET RetryCnt = RetryCnt + 1 WHERE Id = {$id}";
            else
                $sql = "UPDATE MailQueue SET RetryCnt = RetryCnt + 1, DateSend = NOW() WHERE Id = {$id}";
            Zero_DB::Update($sql);
        }
    }

    /**
     * Отправка сообщения
     *
     * @param array $data [
     *      'Reply' => ['Name' => 'ReplyName', 'Email' => 'reply@mail.ru'],
     *      'From' => ['Name' => 'FromName', 'Email' => 'from@mail.ru'],
     *      'Subject' => 'Тема сообщения',
     *      'Message' => 'Текст или тело сообщения',
     *      'To' => [
     *          'Recipient@mail.ru' => 'NameRecipient',
     *      ],
     *      'Attach' => [
     *          'pathFile' => 'nameFile',
     *      ],
     * ]
     * @return int количесвто ошибок отправления
     */
    public static function SendMessage($data)
    {
        // sample $data
        $dataSample = [
            'Reply' => ['Name' => 'Reply', 'Email' => 'reply@mail.ru'],
            'From' => ['Name' => 'From', 'Email' => 'from@mail.ru'],
            'Subject' => 'Тема сообщения',
            'Message' => 'Текст или тело сообщения',
            'To' => [
                'Recipient@mail.ru' => 'NameRecipient',
            ],
            'Attach' => [
                'pathFile' => 'nameFile',
            ],
        ];

        $errorCnt = 0;
        $mailSMTP = new self(Zero_App::$Config->Mail_Username, Zero_App::$Config->Mail_Password, Zero_App::$Config->Mail_Host, Zero_App::$Config->Mail_Port);
        $result = $mailSMTP->sending($data);
        if ( $result !== true )
        {
            $errorCnt = count($data['To']);
            Zero_Logs::Set_Message_Error($result);
        }
        return $errorCnt;
    }

    /**
     * Отправка сообщения
     *
     * @param array $data [
     *      'Reply' => ['Name' => 'ReplyName', 'Email' => 'reply@mail.ru'],
     *      'From' => ['Name' => 'FromName', 'Email' => 'from@mail.ru'],
     *      'Subject' => 'Тема сообщения',
     *      'Message' => 'Текст или тело сообщения',
     *      'To' => [
     *          'Recipient@mail.ru' => 'NameRecipient',
     *      ],
     *      'Attach' => [
     *          'pathFile' => 'nameFile',
     *      ],
     * ]
     * @return int количесвто ошибок отправления
     */
    public static function Send($data)
    {
        $mailSMTP = new Helper_SendMailSmtpClass(Zero_App::$Config->Mail_Username, Zero_App::$Config->Mail_Password, Zero_App::$Config->Mail_Host, Zero_App::$Config->Mail_Port, Zero_App::$Config->Mail_CharSet);
        // от кого
        $from = array(
            $data['From']['Name'], // Имя отправителя
            $data['From']['Email'] // почта отправителя
        );
        // кому отправка. Можно указывать несколько получателей через запятую
        $to = implode(', ', array_keys($data['To']));
        // добавляем файлы
        if ( isset($data['Attach']) )
            foreach (array_keys($data['Attach']) as $path)
            {
                $mailSMTP->addFile($path);
            }
        // отправляем письмо
        $result = $mailSMTP->send($to, $data['Subject'], $data['Message'], $from);
        if ( $result === true )
        {
            return true;
        }
        else
        {
            Zero_Logs::Set_Message_Error($result);
            return $result;
        }
    }
}