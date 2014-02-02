<?php

/**
 * Controller. Modules
 *
 * @package Zero.System.Controller
 * @author Konstantin Shamiev aka ilosa <konstantin@phpzero.com>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
class Zero_System_GridModules extends Zero_Controller
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

        //  Filter reset
        if ( 'Filter_Reset' == $action )
            $this->Params = [];

        //  Filter Init
        if ( empty($this->Params['SearchProp']) )
            $this->Params['SearchProp'] = '';
        if ( empty($this->Params['SearchValue']) )
            $this->Params['SearchValue'] = '';

        //  Filter set
        if ( 'Filter_Set' == $action )
            $this->Chunk_Filter_Set($action);
    }

    /**
     * Filter set
     *
     * @param string $action action
     * @return boolean flag stop execute of the next chunk
     */
    protected function Chunk_Filter_Set($action)
    {
        $this->Params['SearchProp'] = $_REQUEST['Params']['SearchProp'];
        $this->Params['SearchValue'] = $_REQUEST['Params']['SearchValue'];
    }

    /**
     * Create views.
     *
     * @param string $action action
     * @return boolean flag stop execute of the next chunk
     */
    protected function Chunk_View($action)
    {
        $modules_list = [];
        //  read modules
        $arr = Zero_Lib_FileSystem::Get_Config('', 'main');
        foreach ($arr as $module => $row)
        {
            $modules_list[$module]['Description'] = $row['Description'];
            $modules_list[$module]['Version'] = $row['Version'];
            if ( file_exists(ZERO_PATH_APPLICATION . '/' . $module . '/setup/INSTALL') )
            {
                $modules_list[$module]['Installed'] = true;
                if ( file_exists(ZERO_PATH_APPLICATION . '/' . $module . '/setup/remove.php') )
                    $modules_list[$module]['Rem'] = true;
            }
            else
            {
                $modules_list[$module]['Installed'] = false;
                if ( file_exists(ZERO_PATH_APPLICATION . '/' . $module . '/setup/install.php') )
                    $modules_list[$module]['Set'] = true;
            }
        }
        //  search and filters
        foreach ($modules_list as $name => $row)
        {
            //  search
            if ( 'Description' == $this->Params['SearchProp'] && $this->Params['SearchValue'] )
            {
                if ( 1 == count(explode($this->Params['SearchValue'], $row['Description'])) )
                    unset($modules_list[$name]);
            }
        }

        //  output
        $this->View->Assign('modules_list', $modules_list);
        $this->View->Assign('Section', Zero_App::$Section);
        $this->View->Assign('Params', $this->Params);
        $this->View->Assign('PagerCount', count($modules_list));
    }

    /**
     * Module installed.
     *
     * @return boolean flag stop execute of the next chunk
     */
    protected function Action_Set()
    {
        $module = $_REQUEST['obj_id'];
        if ( file_exists(ZERO_PATH_APPLICATION . '/' . $module . '/setup/INSTALL') )
            return $this->Set_Message('Module already installed', 1, false);

        if ( file_exists($path = ZERO_PATH_APPLICATION . '/' . $module . '/setup/install.php') )
            $subj = include $path;
        else
            $subj = 'Installer Not Found';

        if ( $subj )
            return $this->Set_Message($subj, 1, false);
        else
            return $this->Set_Message('Module installed', 0);
    }

    /**
     * Module update
     *
     * @return boolean flag stop execute of the next chunk
     */
    protected function Action_Up()
    {
    }

    /**
     * Module remove.
     *
     * @return boolean flag stop execute of the next chunk
     */
    protected function Action_Rem()
    {
        $module = $_REQUEST['obj_id'];
        if ( !file_exists(ZERO_PATH_APPLICATION . '/' . $module . '/setup/INSTALL') )
            return $this->Set_Message('Module already removed', 1, false);

        $module = $_REQUEST['obj_id'];
        if ( file_exists($path = ZERO_PATH_APPLICATION . '/' . $module . '/setup/remove.php') )
            include $path;
        else
            $this->Set_Message('DeInstaller Not Found', 1);
        return false;
    }
}
