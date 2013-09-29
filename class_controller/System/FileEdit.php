<?php

/**
 * Controller. Editing a text file.
 *
 * @package Zero.System.Controller
 * @author Konstantin Shamiev aka ilosa <konstantin@phpzero.com>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
class Zero_System_FileEdit extends Zero_Controller
{
    /**
     * Initialization of the stack chunks and input parameters
     *
     * @param string $action action
     * @return boolean flag stop execute of the next chunk
     */
    protected function Chunk_Init($action)
    {
        $this->Set_Chunk('Action');
        $this->Set_Chunk('View');
        $this->View = new Zero_View(__CLASS__);
        if ( isset($_REQUEST['path']) )
            $this->Params['obj_parent_path'] = $_REQUEST['path'];
        if ( isset($_REQUEST['file_name']) )
            $this->Params['file_name'] = $_REQUEST['file_name'];
    }

    /**
     * Create views.
     *
     * @param string $action action
     * @return boolean flag stop execute of the next chunk
     */
    protected function Chunk_View($action)
    {
        //  в шаблон
        $this->View->Assign('Section', Zero_App::$Section);
        $this->View->Assign('path', $this->Params['obj_parent_path'] . '/' . $this->Params['file_name']);
        $this->View->Assign('name', $this->Params['file_name']);
        $this->View->Assign('data', file_get_contents($this->Params['obj_parent_path'] . '/' . $this->Params['file_name']));
    }

    /**
     * Save a text file.
     *
     * @return boolean flag stop execute of the next chunk
     */
    protected function Action_FileSave()
    {
        $_REQUEST['Prop']['Content'] = str_replace("\r\n", "\n", $_REQUEST['Prop']['Content']);
        file_put_contents($this->Params['obj_parent_path'] . '/' . $this->Params['file_name'], $_REQUEST['Prop']['Content']);
        return $this->Set_Message('FileSave', 0);
    }
}