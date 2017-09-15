<?php

/**
 * Вывод результата работы приложения.
 *
 * Компонент
 *
 * @package Zero.Component
 * @author Konstantin Shamiev aka ilosa <konstantin@shamiev.ru>
 * @date 2017-09-14
 */
class Zero_Response
{
    /**
     * Завершение консольного приложения
     */
    public static function Console()
    {
        // закрываем соединение с браузером (работает только под нгинx)
        if ( function_exists('fastcgi_finish_request') )
            fastcgi_finish_request();

        // Логирование в файлы
        if ( Zero_App::$Config->Log_Output_File )
            Zero_Logs::Output_File();
        exit;
    }

    /**
     * Отдача файла картинки
     *
     * @param string $path путь до файла картинки
     */
    public static function Img($path)
    {
        header("Content-Type: " . Helper_File::File_Type($path));
        header("Content-Length: " . filesize($path));
        if ( file_exists($path) )
            echo file_get_contents($path);
        exit;
    }

    /**
     * Отдача файла
     *
     * @param string $path путь до файла
     */
    public static function File($path)
    {
        header("Content-Type: " . Helper_File::File_Type($path));
        header("Content-Length: " . filesize($path));
        header('Content-Disposition: attachment; filename = "' . basename($path) . '"');
        if ( file_exists($path) )
            echo file_get_contents($path);
        exit;
    }

    /**
     * Выдача контента в формате html
     *
     * @param string $content
     * @param int $status
     */
    public static function Html($content, $status = 200)
    {
        header('Pragma: no-cache');
        header('Last-Modified: ' . date('D, d M Y H:i:s') . 'GMT');
        header('Expires: Mon, 26 Jul 2007 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header("Content-Type: text/html; charset=utf-8");
        header("Content-Length: " . strlen($content));
        // header('Access-Control-Allow-Origin: *');
        header('HTTP/1.1 ' . $status . ' ' . $status);
        echo $content;

        // Логирование (в браузер)
        if ( Zero_App::$Config->Log_Output_Display )
            Zero_Logs::Output_Display();

        // закрываем соединение с браузером (работает только под нгинx)
        if ( function_exists('fastcgi_finish_request') )
            fastcgi_finish_request();

        // Логирование в файлы
        if ( Zero_App::$Config->Log_Output_File )
            Zero_Logs::Output_File();
        exit;
    }

    /**
     * Выдача контента в формате json
     *
     * @param mixed $content данные
     * @param int $status http код ответа
     */
    public static function Json($content, $status = 200)
    {
        $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        header('Pragma: no-cache');
        header('Last-Modified: ' . date('D, d M Y H:i:s') . 'GMT');
        header('Expires: Mon, 26 Jul 2007 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header("Content-Type: application/json; charset=utf-8");
        header("Content-Length: " . strlen($content));
        // header('Access-Control-Allow-Origin: *');
        header('HTTP/1.1 ' . $status . ' ' . $status);
        echo $content;

        // закрываем соединение с браузером (работает только под нгинx)
        if ( function_exists('fastcgi_finish_request') )
            fastcgi_finish_request();

        // Логирование в файлы
        if ( Zero_App::$Config->Log_Output_File )
            Zero_Logs::Output_File();
        exit;
    }

    /**
     * Выдача структурного контента в формате json
     *
     * @param mixed $content данные
     * @param int $code внутренний код ответа приложения
     * @param array $message
     * @param int $status http код ответа
     */
    public static function JsonRestful($content = null, $code = 0, $message = [], $status = 200)
    {
        if ( Zero_App::$Controller->Controller )
            $message = Zero_I18n::Message(Zero_App::$Controller->Controller, $code, $message);
        else
            $message = Zero_I18n::Message('Zero', $code, $message);

        $error = false;
        if ( 299 < $status )
            $error = true;

        $data = [
            'Code' => $message[0],
            'Message' => $message[1],
            'ErrorStatus' => $error,
        ];

        if ( $content )
            $data['Content'] = $content;

        $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

        header('Pragma: no-cache');
        header('Last-Modified: ' . date('D, d M Y H:i:s') . 'GMT');
        header('Expires: Mon, 26 Jul 2007 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header("Content-Type: application/json; charset=utf-8");
        header("Content-Length: " . strlen($data));
        // header('Access-Control-Allow-Origin: *');
        header('HTTP/1.1 ' . $status . ' ' . $status);
        echo $data;

        // закрываем соединение с браузером (работает только под нгинx)
        if ( function_exists('fastcgi_finish_request') )
            fastcgi_finish_request();

        // Логирование в файлы
        if ( Zero_App::$Config->Log_Output_File )
            Zero_Logs::Output_File();
        exit;
    }

    /**
     * Безусловный переход (редирект) на указываемую страницу
     *
     * @param string $url
     */
    public static function ResponseRedirect($url)
    {
        header('HTTP/1.1 301 Redirect');
        header('Location: ' . $url);
        exit;
    }
}