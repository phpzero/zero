<?php

/**
 * Controller. Development and maintenance of the system.
 *
 * @package Zero.System.Controller
 * @author Konstantin Shamiev aka ilosa <konstantin@phpzero.com>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
class Zero_System_GridService extends Zero_Controller
{
    /**
     * Initialize the stack chunks
     *
     * @param string $action action
     */
    protected function Init_Chunks($action)
    {
        $this->Set_Chunk('Init');
        $this->Set_Chunk('Action');
        $this->Set_Chunk('View');
    }

    /**
     * Initialization of the input parameters
     *
     * @param string $action action
     * @return boolean flag run of the next chunk
     */
    protected function Chunk_Init($action)
    {
        $this->View = new Zero_View(__CLASS__);
        return true;
    }

    /**
     * Create views.
     *
     * @param string $action action
     * @return boolean flag run of the next chunk
     */
    protected function Chunk_View($action)
    {
        $Interface_List = Zero_App::$Section->Get_Navigation_Child();
        $this->View->Assign('Section', Zero_App::$Section);
        $this->View->Assign('Interface', $Interface_List);
        $this->View->Assign('Params', $this->Params);
        $this->View->Assign('modules_db', Zero_Engine::Get_Modules_DB());
        return true;
    }

    /**
     * Engineering models.
     *
     * @return boolean flag run of the next chunk
     */
    protected function Action_Engine_Modules_DB()
    {
        $_REQUEST['paket'] = trim($_REQUEST['paket']);
        if ( !$_REQUEST['paket'] )
            return $this->Set_Message('Error_NotParam', 1);
        $_REQUEST['flag_gird'] = isset($_REQUEST['flag_gird']) ? true : false;
        $_REQUEST['flag_edit'] = isset($_REQUEST['flag_edit']) ? true : false;
        $Controller_Factory = new Zero_Engine;
        if ( $Controller_Factory->Factory_Modules_DB($_REQUEST['paket'], $_REQUEST['flag_gird'], $_REQUEST['flag_edit']) )
            return $this->Set_Message("Engine_Modules_DB", 0);
        else
            return $this->Set_Message("Error_Engine_Modules_DB", 1);
    }

    /**
     * Full reset cache
     *
     * @return boolean flag run of the next chunk
     */
    protected function Action_Cache_Reset()
    {
        Zero_Cache::Reset_All();
        return $this->Set_Message("Cache_Reset", 0);
    }

    /**
     * Full reset session
     *
     * @return boolean flag run of the next chunk
     */
    protected function Action_Session_Reset()
    {
        Zero_Helper_FileSystem::File_Remove(Zero_App::$Config->System_PathSession);
        return $this->Set_Message("Session_Reset", 0);
    }
}