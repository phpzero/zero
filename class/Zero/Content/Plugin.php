<?php

/**
 * Контент-блоки страницы.
 *
 * @sample {plugin "Zero_Content_Plugin" target="TopLeft"}
 *
 * @package Zero.Plugin
 * @author Konstantin Shamiev aka ilosa <konstantin@shamiev.ru>
 * @date 2015-01-01
 */
class Zero_Content_Plugin extends Zero_Controller
{
    /**
     * Create views.
     *
     * @return Zero_View
     */
    public function Action_Default()
    {
        $this->Chunk_Init();

        $index = 'Content_' . $this->Params['target'] . '_' . ZERO_LANG;
        if ( false === $Content = Zero_App::$Section->CH->Get($index) )
        {
            $Content = Zero_Content::Make();
            $Content->Load_Page($this->Params['target'], Zero_App::$Section->ID);
            if ( 0 == $Content->ID )
            {
                $Content->Load_Page($this->Params['target']);
            }
            Zero_Cache::Set_Link('Content', $Content->ID);
            Zero_App::$Section->CH->Set($index, $Content);
        }
        $this->View->Assign('Content', $Content->Content);
        return $this->View;
    }
}
