<?php

/**
 * Plugin. Content Page.
 *
 * {plugin "Www_Content_Page" Block="content"}
 *
 * @package Zero.Content.Plugin
 * @author Konstantin Shamiev aka ilosa <konstantin@phpzero.com>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
class Zero_Content_Page extends Zero_Plugin
{
    /**
     * Initialize the stack chunks
     */
    protected function Init_Chunks()
    {
        $this->Set_Chunk('View');
    }

    /**
     * Create views.
     *
     * @return boolean flag run of the next chunk
     */
    protected function Chunk_View()
    {
        if ( empty($this->Params['Block']) )
            $this->Params['Block'] = 'content';
        $index = 'Content_' . $this->Params['Block'] . Zero_App::$Route->lang_id;
        if ( false === $Content = Zero_App::$Section->Cache->Get($index) )
        {
            $Content = Zero_Model::Make('Zero_Content');
            $Content->DB->Sql_Where('Zero_Section_ID', '=', Zero_App::$Section->ID);
            $Content->DB->Sql_Where('Zero_Language_ID', '=', Zero_App::$Route->lang_id);
            $Content->DB->Sql_Where('Block', '=', $this->Params['Block']);
            $Content->DB->Load('*');
            if ( 0 == $Content->ID )
            {
                $Content = Zero_Model::Make('Zero_Content');
                $Content->DB->Sql_Where('Zero_Layout_ID', '=', Zero_App::$Section->Zero_Layout_ID);
                $Content->DB->Sql_Where('Zero_Language_ID', '=', Zero_App::$Route->lang_id);
                $Content->DB->Sql_Where('Block', '=', $this->Params['Block']);
                $Content->DB->Load('*');
            }
            Zero_Cache::Set_Link('Zero_Content', $Content->ID);
            Zero_App::$Section->Cache->Set($index, $Content);
        }
        if ( 'content' == $this->Params['Block'] )
        {
            $this->View = new Zero_View(get_class($this));
            $this->View->Assign('Name', $Content->Name);
            $this->View->Assign('Content', $Content->Content);
        }
        else
        {
            $this->View = $Content->Content;
        }
        return true;
    }
}
