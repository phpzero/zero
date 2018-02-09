<?php

/**
 * Запросы к внешним источникам (службам, сервисам)
 *
 * Расширяемый компонент
 * Путем добавления конфигураций и указания методов ниже в комментарии
 * Либо через методы геттеры (паттерн композиция)
 *
 * @package Zero
 * @author Konstantin Shamiev aka ilosa <konstantin@shamiev.ru>
 * @date 2017-09-14
 *
 * @method Zero_Request_Type Simple($method, $uri, $content = null, $headers = []) Запросы к неопределенному ресурсу
 */
class Zero_Request
{
    /**
     * Обертка запросов к внешнему источнику
     *
     * @var mixed
     */
    //    private $sampleCustomRequest = null;

    /**
     * Обертка запросов к внешнему источнику
     *
     * @return mixed
     */
    //    protected function Get_SampleCustomRequest()
    //    {
    //        if ( is_null($this->sampleCustomRequest) )
    //        {
    //            $access = Zero_App::$Config->AccessOutside['SampleCustomRequest'];
    //            // Инициализация ранее реализованного функционала для реализации запросов к нужному ресурсу
    //
    //        }
    //        return $this->sampleCustomRequest;
    //    }

    /**
     * API запрос к внешнему ресурсу
     *
     * @param string $access
     * @param string $method
     * @param string $uri
     * @param mixed $content
     * @param array $headers список заголовков
     * @return Zero_Request_Type
     */
    private function request($access, $method, $uri, $content = null, $headers = [])
    {
        if ( empty(Zero_App::$Config->AccessOutside[$access]) )
        {
            Zero_Logs::Set_Message_Error("Реквизиты метода {$access} для запросов не определены");
            return new Zero_Request_Type;
        }
        $accessConf = Zero_App::$Config->AccessOutside[$access];

        // $content = json_encode($content, JSON_PRESERVE_ZERO_FRACTION);
        $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

        $ch = curl_init($accessConf['Url'] . $uri);
        //	время работы
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);          //	полное время сеанса
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);    //	время ожидания соединения в секундах
        //	Передаем и возвращаем Заголовки и тело страницы
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        //	Заголовки
        $headers[] = "Content-Type: application/json; charset=utf-8";
        $headers[] = "Content-Length: " . strlen($content);
        if ( isset($accessConf['AuthUserToken']) && $accessConf['AuthUserToken'] )
            $headers[] = "AuthUserToken: " . $accessConf['AuthUserToken'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //	АВТОРИЗАЦИЯ МЕТОДОМ APACHE
        if ( isset($accessConf['Login']) && $accessConf['Login'] && isset($accessConf['Password']) && $accessConf['Password'] )
        {
            curl_setopt($ch, CURLOPT_USERPWD, $accessConf['Login'] . ":" . $accessConf['Password']);
        }
        // Метод запроса и тело запроса
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        //	возвращаем результат в переменную
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if ( isset($accessConf['IsDebug']) && $accessConf['IsDebug'] )
        {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_STDERR, fopen(ZERO_PATH_LOG . "/curl_{$access}.log", 'a'));
        }
        // Запрос
        $body = curl_exec($ch);
        $head = curl_getinfo($ch);
        $error_code = curl_errno($ch);
        $error_subj = curl_error($ch);
        if ( 0 < $error_code )
        {
            Zero_Logs::Set_Message_Error('Curl error: ' . $error_code . ' - ' . $error_subj);
            $response = new Zero_Request_Type;
            $response->Head = $head;
            $response->Body = $body;
            $response->Code = $error_code;
            $response->Message = $error_subj;
            $response->Content = $body;
            $response->Error = true;
            $response->ErrorStatus = true;
            return $response;
        }
        curl_close($ch);
        // Заголовки
        switch ( $head['http_code'] )
        {
            case '201':
                break;
            case '400':
                break;
            case '401':
                break;
            case '403':
                break;
            case '404':
                break;
            case '409':
                break;
            case '503':
                break;
            default:
                break;
        }
        // Данные
        $typ = explode(' ', $head['content_type'])[0];
        if ( 'application/json;' == $typ )
        {
            $body = json_decode($body, true);
        }
        //
        $response = new Zero_Request_Type;
        $response->Head = $head;
        $response->Body = $body;
        if ( isset($body['Code']) )
            $response->Code = $body['Code'];
        if ( isset($body['Message']) )
            $response->Message = $body['Message'];
        if ( isset($body['Content']) )
            $response->Content = $body['Content'];
        if ( isset($body['ErrorStatus']) )
            $response->ErrorStatus = $body['ErrorStatus'];
        if ( isset($body['Error']) )
            $response->Error = $body['Error'];
        return $response;
    }

    /**
     * Геттер для реализации пользовательских классов - запросов
     *
     * @param string $prop
     * @return mixed
     */
    public function __get($prop)
    {
        if ( method_exists($this, $method = 'Get_' . $prop) )
            return $this->$method();
        else
            Zero_Logs::Set_Message_Error("обращение к нереализованному функционалу запросов '{$prop}'");
        return null;
    }

    /**
     * Метод перегрузки
     *
     * @param string $method имя вызываемого метода
     * @param array $params массив передаваемых параметров
     * @return Zero_Request_Type ответ
     */
    public function __call($method, $params)
    {
        if ( empty($params[2]) )
            $params[2] = null; // $content
        if ( empty($params[3]) )
            $params[3] = []; // $headers
        return $this->request($method, $params[0], $params[1], $params[2], $params[3]);
    }
}

/**
 * Возвращаемое значение
 *
 * @package Zero.Component
 */
class Zero_Request_Type
{
    /**
     * Заголовки
     *
     * @var array
     */
    public $Head = [];

    /**
     * Тело
     *
     * @var array
     */
    public $Body = [];

    /**
     * Код ответа (phpzero)
     *
     * @var int
     */
    public $Code = 0;

    /**
     * Информационное сообщение (phpzero)
     *
     * @var string
     */
    public $Message = '';

    /**
     * Тело ответа (phpzero)
     *
     * @var null
     */
    public $Content = null;

    /**
     * Признак ошибки (phpzero)
     *
     * @var bool
     */
    public $Error = false;

    /**
     * Признак ошибки (phpzero)
     *
     * @var bool
     * @deprecated
     */
    public $ErrorStatus = false;
}