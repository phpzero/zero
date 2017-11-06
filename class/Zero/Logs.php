<?php

/**
 * Мониторинг и сниатие статистики работы приложения.
 *
 * Profilirovanny`e danny`e po prilozheniiam:
 * - Paulzovatel`skie soobshcheniia
 * - Oshibki programmirovaniia
 * - Iscliucheniia
 * - Tai`mery` vremeni vy`polneniia
 * - Zatrachennaia pamiat`
 * - Dei`stvii` pol`zovatelia
 *
 * @package Component
 * @author Konstantin Shamiev aka ilosa <konstantin@shamiev.ru>
 * @date 2015-01-01
 */
class Zero_Logs
{
    /**
     * Massiv soobshchenii` sistemy`
     *
     * @var array
     */
    private static $_Message = [];

    /**
     * Data i vremia v formate timestamp
     *
     * @var integer
     */
    private static $_StartTime;

    /**
     * Massiv vremenny`kh metok
     *
     * @var array
     */
    private static $_CurrentTime;

    /**
     * Uroven` vlozhennosti vremenny`kh metok
     *
     * @var int
     */
    private static $_CurrentTimeLevel = 0;

    /**
     * Danny`e o rabote prilozheniia v tcelom.
     *
     * Obrabotanny`e danny`e profilirovaniia tai`merov i pamiati.
     *
     * @var array
     */
    private static $_OutputApplication;

    /**
     * Osnovnoe imia log fai`lov
     *
     * @var string
     */
    private static $_FileLog = '';

    /**
     * Инициализация логера
     *
     * @param string $fileLog имя файллога
     */
    public static function Init($fileLog)
    {
        self::$_Message = [];
        self::$_StartTime = microtime(1);
        self::$_CurrentTime = [];
        self::$_FileLog = $fileLog;
    }

    /**
     * Получение полного времени выполнения на момент запроса этого метода
     *
     * @return string
     */
    public static function Get_FullTime()
    {
        return sprintf("%01.3f", microtime(1) - self::$_StartTime);
    }

    /**
     * Инициализация входиащего системного сообщения.
     *
     * @param mixed $value Сообщение об ошибке
     * @return mixed
     */
    public static function Set_Message_Error($value)
    {
        self::$_Message[] = [print_r($value, true), 'error'];
        if ( Zero_App::$Config->Log_Profile_Error )
        {
            self::Start(print_r($value, true));
            self::Stop(print_r($value, true));
        }
        return $value;
    }

    /**
     * Инициализация входиащего системного сообщения.
     *
     * @param string $value Soobshchenie ob oshibke
     */
    public static function Set_Message_ErrorTrace($value)
    {
        self::$_Message[] = [print_r($value, true), 'errorTrace'];
    }

    /**
     * Инициализация входиащего системного сообщения.
     *
     * @param mixed $value Сообщение об ошибке
     * @return mixed
     */
    public static function Set_Message_Warning($value)
    {
        self::$_Message[] = [print_r($value, true), 'warning'];
        if ( Zero_App::$Config->Log_Profile_Warning )
        {
            self::Start(print_r($value, true));
            self::Stop(print_r($value, true));
        }
        return $value;
    }

    /**
     * Инициализация входиащего системного сообщения.
     *
     * @param mixed $value Сообщение об ошибке
     * @return mixed
     */
    public static function Set_Message_Info($value)
    {
        self::$_Message[] = [print_r($value, true), 'info'];
        self::Start(print_r($value, true));
        self::Stop(print_r($value, true));
        return $value;
    }

    /**
     * Инициализация входиащего системного сообщения.
     *
     * @param mixed $value Сообщение об ошибке
     * @return mixed
     */
    public static function Set_Message_Notice($value)
    {
        self::$_Message[] = [print_r($value, true), 'notice'];
        if ( Zero_App::$Config->Log_Profile_Notice )
        {
            self::Start(print_r($value, true));
            self::Stop(print_r($value, true));
        }
        return $value;
    }

    public static function Get_Message()
    {
        return self::$_Message;
    }

    /**
     * Start tai`mera po cliuchu
     *
     * @param string $key cliuch
     */
    public static function Start($key)
    {
        self::$_CurrentTime[$key]['start'] = microtime(true);
        self::$_CurrentTime[$key]['level'] = self::$_CurrentTimeLevel;
        self::$_CurrentTimeLevel++;
    }

    /**
     * Ostanovka tai`mera po cliuchu
     *
     * @param string $key cliuch
     */
    public static function Stop($key)
    {
        self::$_CurrentTime[$key]['stop'] = microtime(true);
        self::$_CurrentTimeLevel--;
    }

    /**
     * Вывод отладочной информации в браузер длиа разработчиков
     *
     * @return string
     */
    public static function Output_Display()
    {
        $iterator_list = [];
        if ( isset($_SESSION['Session']) )
            foreach ($_SESSION['Session'] as $key => $val)
            {
                $iterator_list[$key]['name'] = get_class($val);
                $iterator_list[$key]['type'] = gettype($val);
            }
        if ( isset($_SESSION['Session']) )
            foreach ($_SESSION as $key => $val)
            {
                $iterator_list[$key]['name'] = '';
                $iterator_list[$key]['type'] = gettype($val);
            }
        unset($iterator_list['Session']);
        $View = new Zero_View('Zero_Debug_Info');
        $View->Assign('output', self::Get_Usage_MemoryAndTime());
        $View->Assign('message', self::$_Message);
        $View->Assign('iterator_list', $iterator_list);
        echo $View->Fetch();
    }

    /**
     * Vy`vod vsei` profilirovannoi` informatcii v log fai`ly`
     *
     */
    public static function Output_File()
    {
        // Логируем работу приложения в целом
        if ( Zero_App::$Config->Log_Profile_Application )
        {
            $output = join("\n", self::Get_Usage_MemoryAndTime()) . "\n";
            Helper_File::File_Save_After(self::$_FileLog . '_info.log', $output);
        }
        if ( 0 < count(self::$_Message) )
        {
            $output = self::Get_Usage_MemoryAndTime()[0];
            $errors = [];
            $warnings = [];
            $notice = [];
            foreach (self::$_Message as $row)
            {
                if ( 'error' == $row[1] )
                    $errors[] = str_replace(["\r", "\t"], " ", $row[0]);
                else if ( 'warning' == $row[1] )
                    $warnings[] = str_replace(["\r", "\t"], " ", $row[0]);
                else if ( 'notice' == $row[1] )
                    $notice[] = str_replace(["\r", "\t"], " ", $row[0]);
            }
            // логирование ошибки в файл
            if ( Zero_App::$Config->Log_Profile_Error && 0 < count($errors) )
            {
                array_unshift($errors, str_replace(["\r", "\t"], " ", $output));
                $errors = preg_replace('![ ]{2,}!', ' ', join("\n", $errors));
                Helper_File::File_Save_After(self::$_FileLog . '_error.log', $errors);
            }
            // логирование предупреждений в файл
            if ( Zero_App::$Config->Log_Profile_Warning && 0 < count($warnings) )
            {
                array_unshift($warnings, str_replace(["\r", "\t"], " ", $output));
                $warnings = preg_replace('![ ]{2,}!', ' ', join("\n", $warnings));
                Helper_File::File_Save_After(self::$_FileLog . '_warning.log', $warnings);
            }
            // логирование сообщений в файл
            if ( Zero_App::$Config->Log_Profile_Notice && 0 < count($notice) )
            {
                array_unshift($notice, str_replace(["\r", "\t"], " ", $output));
                $notice = preg_replace('![ ]{2,}!', ' ', join("\n", $notice));
                Helper_File::File_Save_After(self::$_FileLog . '_notice.log', $notice);
            }
        }
        // логирование операций пользователиа в файл
        if ( Zero_App::$Config->Log_Profile_Action && isset($_REQUEST['act']) && 'Action_Default' != $_REQUEST['act'] )
            if ( is_object(Zero_App::$Users) && is_object(Zero_App::$Section) )
            {
                $act = date('[d.m.Y H:i:s]') . "\t";
                $act .= Zero_App::$Users->Login . "\t";
                if ( Zero_App::$Controller->Controller )
                    $act .= Zero_App::$Controller->Controller . " -> " . $_REQUEST['act'] . "\t";
                $act .= ZERO_HTTP . $_SERVER['REQUEST_URI'];
                Helper_File::File_Save_After(self::$_FileLog . '_action.log', $act);
            }
    }

    /**
     * Poluchenie ishodnogo kuska koda v oblasti oshibki fai`la
     *
     * @param string $file put` do fai`la
     * @param int $line stroka s oshibkoi`
     * @param int $range_file_error diapazon vy`vodimy`kh strok fai`la vokrug oshibochneoi` stroki
     * @return string
     */
    public static function Get_SourceCode($file, $line, $range_file_error)
    {
        if ( !$file )
            return '';
        $file_line = explode('<br />', highlight_file($file, true));
        $offset = $line - $range_file_error;
        if ( $offset < 0 )
            $offset = 0;
        $length = isset($file_line[$line + $range_file_error]) ? $line + $range_file_error : count($file_line) - 1;
        $View = new Zero_View('Zero_Debug_SourceCode');
        $View->Assign('file_line', $file_line);
        $View->Assign('offset', $offset < 0 ? 0 : $offset);
        $View->Assign('length', $length);
        $View->Assign('line', $line);
        return $View->Fetch();
    }

    /**
     * Obrabotka i poluchenie profilirovanny`kh tai`merov i zatrachennoi` pamiati.
     *
     * @return array
     */
    protected static function Get_Usage_MemoryAndTime()
    {
        if ( null === self::$_OutputApplication )
        {
            // initcializatciia logov
            if ( isset($_SERVER['REQUEST_URI']) )
                self::$_OutputApplication = [date('[d.m.Y H:i:s]') . ' [' . $_SERVER['REQUEST_METHOD'] . '] ' . ZERO_HTTP . $_SERVER['REQUEST_URI']];
            else if ( isset($_SERVER['argv'][1]) )
                self::$_OutputApplication = [date('[d.m.Y H:i:s]') . ' ' . $_SERVER['argv'][1]];
            else
                self::$_OutputApplication = [date('[d.m.Y H:i:s]')];
            // Sobiraem tai`mery` v kuchu
            foreach (self::$_CurrentTime as $description => $time)
            {
                $limit = -1;
                if ( isset($time['stop']) )
                {
                    $limit = round($time['stop'] - $time['start'], 4);
                }
                //                if ( $limit != -1 )
                //                    continue;
                $description = str_replace("\r", "", $description);
                $description = preg_replace("~(\s+\n){1,}~si", "\n", $description);
                $description = preg_replace('~[ ]{2,}~', ' ', $description);
                if ( strpos($description, '#{SQL}') === 0 )
                    $description = str_replace("\n", "\n\t\t", $description);
                $indent = '';
                for ($i = 0; $i < $time['level']; $i++)
                {
                    $indent .= "\t";
                }
                self::$_OutputApplication[] = $indent . '{' . $limit . '} ' . trim($description);
            }
            self::$_OutputApplication[] = "#{System.Full} " . self::Get_FullTime();
            self::$_OutputApplication[] = "#{MEMORY} " . memory_get_usage();
            self::$_CurrentTime = [];
        }
        return self::$_OutputApplication;
    }

    /**
     * Логирование в файл.
     *
     * Обциональное количество параметров после имени файла
     *
     * @param string $file_log имиа файл-лога
     * @return bool
     * @deprecated Custom
     */
    public static function File($file_log)
    {
        $arr = func_get_args();
        $file_log = array_shift($arr) . '.log';
        if ( 0 == count($arr) )
            return true;
        foreach ($arr as $val)
        {
            Helper_File::File_Save_After(self::$_FileLog . '_' . $file_log, print_r($val, true));
        }
        return true;
    }

    /**
     * Логирование в файл.
     *
     * @param string $file_log имиа файл-лога
     * @param array|string $data данные
     * @return bool
     */
    public static function Custom($file_log, $data)
    {
        Helper_File::File_Save_After(ZERO_PATH_LOG . '/' . $file_log . '.log', print_r($data, true));
        return true;
    }

    /**
     * Логирование в файл.
     *
     * Обциональное количество параметров после имени файла
     *
     * @param string $file_log имиа файл-лога
     * @return bool
     * @deprecated Custom_DateTime
     */
    public static function File_DateTime($file_log)
    {
        $arr = func_get_args();
        $file_log = array_shift($arr) . '.log';
        if ( 0 == count($arr) )
            return true;
        foreach ($arr as $val)
        {
            Helper_File::File_Save_After(self::$_FileLog . '_' . $file_log, date('[d.m.Y H:i:s]'));
            Helper_File::File_Save_After(self::$_FileLog . '_' . $file_log, print_r($val, true));
        }
        return true;
    }

    /**
     * Логирование в файл.
     *
     * @param string $file_log имя файл-лога
     * @param array|string $data данные
     * @return bool
     */
    public static function Custom_DateTime($file_log, $data)
    {
        Helper_File::File_Save_After(ZERO_PATH_LOG . '/' . $file_log . '.log', date('[d.m.Y H:i:s]'));
        Helper_File::File_Save_After(ZERO_PATH_LOG . '/' . $file_log . '.log', print_r($data, true));
        return true;
    }
}
