<?php

/**
 * Controller. User authentication.
 *
 * @package Zero.Users.Controller
 * @author Konstantin Shamiev aka ilosa <konstantin@phpzero.com>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
class Zero_Users_Login extends Zero_Controller
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
        $this->View = new Zero_View(get_class($this));
    }

    /**
     * Create views.
     *
     * @param string $action action
     * @return boolean flag stop execute of the next chunk
     */
    protected function Chunk_View($action)
    {
        if ( !isset($this->Params['url_history']) )
            $this->Params['url_history'] = ZERO_HTTPH;
        $this->View->Assign('Users', Zero_App::$Users);
    }

    /**
     * User authentication.
     *
     * @return boolean flag stop execute of the next chunk
     */
    protected function Action_Login()
    {
        if ( !$_REQUEST['Login'] || !$_REQUEST['Password'] )
            return true;

        $Users = Zero_Model::Make('Www_Users');
        $Users->DB->Sql_Where('Login', '=', $_REQUEST['Login']);
        $Users->DB->Load('*');

        //  Check
        if ( 0 == $Users->ID )
            return $this->Set_Message("Error_Registration", 1);
        else if ( $Users->Password != md5($_REQUEST['Password']) )
            return $this->Set_Message("Error_Password", 1);
        else if ( !$Users->Zero_Groups_ID )
            return $this->Set_Message("Error_Groups", 1);

        Zero_App::$Users = $Users;
        Zero_App::$Users->Factory_Set();
        $url_history = $this->Params['url_history'];
        unset($this->Params['url_history']);
        Zero_App::ResponseRedirect($url_history);
        return false;
    }

    /**
     * User exit.
     *
     * @return boolean flag stop execute of the next chunk
     */
    protected function Action_Logout()
    {
        Zero_Session::Unset_Instance();
        session_unset();
        session_destroy();
        //        Zero_App::$Users = Zero_Model::Make('Www_Users');
        //        Zero_App::$Users->Factory_Set();
        Zero_App::ResponseRedirect(ZERO_HTTP);
        return false;
    }
}