<?php

/**
 * Controller. Abstract controller for viewing a list of items.
 *
 * @package Zero.Crud.Controller
 * @author Konstantin Shamiev aka ilosa <konstantin@phpzero.com>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
abstract class Zero_Crud_Grid extends Zero_Controller
{
    /**
     * The table stores the objects handled by this controller.
     *
     * @var string
     */
    protected $Source = '';

    /**
     * Template view
     *
     * @var string
     */
    protected $Template = __CLASS__;

    /**
     * Take into account the conditions user
     *
     * @var boolean
     */
    protected $User_Condition = true;

    /**
     * Initialize the stack chunks
     *
     * @param string $action action
     */
    protected function Init_Chunks($action)
    {
        $this->Set_Chunk('Init');
        $this->Set_Chunk('Filter');
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
        $this->View = new Zero_View($this->Template);
        $this->Model = Zero_Model::Make($this->Source);
        return true;
    }

    /**
     * Move to one level up or down for catalogs
     *
     * @param string $action action
     * @return boolean flag run of the next chunk
     */
    protected function Chunk_View_CatalogGo($action)
    {
        $this->Params['obj_parent_id'] = Zero_App::$Route->obj_parent_id;
        //  move up
        if ( isset($this->Params['obj_parent_path'][Zero_App::$Route->obj_parent_id]) )
        {
            $flag = true;
            foreach ($this->Params['obj_parent_path'] as $id => $name)
            {
                if ( $id == Zero_App::$Route->obj_parent_id )
                    $flag = false;
                else if ( false == $flag )
                    unset($this->Params['obj_parent_path'][$id]);
            }
        }
        //  move down
        else
        {
            $ObjectGo = Zero_Model::Make($this->Source, Zero_App::$Route->obj_parent_id);
            $ObjectGo->DB->Load('Name');
            $this->Params['obj_parent_path'][Zero_App::$Route->obj_parent_id] = $ObjectGo->Name;
            unset($ObjectGo);
        }
        Zero_Filter::Factory($this->Model)->Page = 1;
        return true;
    }

    /**
     * Creating the conditions for obtaining the necessary data
     *
     * @param string $action action
     * @return boolean flag run of the next chunk
     */
    protected function Chunk_View_SqlWhere($action)
    {
        //  Filters
        $Filter = Zero_Filter::Factory($this->Model);
        $this->Model->DB->Sql_Where_Filter($Filter);
        //  Condition of cross connection
        if ( isset($this->Params['obj_parent_table']) )
        {
            $link = $this->Model->Get_Config_Link();
            $link = $link[$this->Params['obj_parent_table']];
            $sql = "
            FROM {$this->Model->Get_Source()} as z
                INNER JOIN {$link['table_link']} as p ON p.{$link['prop_this']} = z.ID AND p.{$link['prop_target']} = $this->Params['obj_parent_id']
            ";
            $this->Model->DB->Sql_From($sql);
        }
        //  The coupling condition
        else if ( isset($this->Params['obj_parent_prop']) )
        {
            if ( 0 < $this->Params['obj_parent_id'] )
                $this->Model->DB->Sql_Where('z.' . $this->Params['obj_parent_prop'], '=', $this->Params['obj_parent_id']);
            else
                $this->Model->DB->Sql_Where_IsNull('z.' . $this->Params['obj_parent_prop']);
        }
        //  The user conditions
        if ( true == $this->User_Condition )
        {
            foreach (array_keys($this->Model->Get_Config_Prop()) as $prop)
            {
                if ( isset($users_condition[$prop]) )
                    $this->Model->DB->Sql_Where_In('z.' . $prop, array_keys($users_condition[$prop]));
            }
            unset($users_condition);
        }
        return true;
    }

    /**
     * Initialization and set filters
     *
     * @param string $action action
     * @return boolean flag run of the next chunk
     */
    protected function Chunk_Filter($action)
    {
        $Filter = Zero_Filter::Factory($this->Model);
        //  Reset
        if ( 'Filter_Reset' == $action )
            $Filter->Reset();
        //  Initialization
        if ( false == $Filter->IsInit )
        {
            $condition = Zero_App::$Users->Get_Condition();
            foreach ($this->Model->Get_Config_Filter() as $prop => $row)
            {
                if ( $row['Filter'] )
                {
                    $method = 'Add_Filter_' . $row['Filter'];
                    if ( isset($condition[$prop]) && $this->User_Condition )
                    {
                        if ( 1 < count($condition[$prop]) )
                            $Filter->$method($prop, 1, $condition[$prop]);
                        else
                            $Filter->$method($prop, 0, $condition[$prop]);
                    }
                    else
                        $Filter->$method($prop, 1, 1);
                }
                if ( $row['Search'] )
                {
                    $method = 'Add_Search_' . $row['Search'];
                    $Filter->$method($prop);
                }
                if ( $row['Sort'] )
                {
                    $Filter->Add_Sort($prop);
                    if ( 'Sort' == $prop || 'ID' == $prop )
                        $Filter->Set_Sort($prop);
                }
            }
            $Filter->IsInit = true;
        }

        //  Page by page
        if ( 0 < Zero_App::$Route->page_id )
            $Filter->Page = Zero_App::$Route->page_id;

        //  Set
        if ( 'Filter_Set' == $action )
            $this->Chunk_Filter_Set($action);

        return true;
    }

    /**
     * Set Filters
     *
     * @param string $action action
     * @return boolean flag run of the next chunk
     */
    protected function Chunk_Filter_Set($action)
    {
        $Filter = Zero_Filter::Factory($this->Model);
        //  Filters
        if ( isset($_REQUEST['Filter']) )
        {
            foreach ($_REQUEST['Filter'] as $Prop => $Value)
            {
                $Filter->Set_Filter($Prop, $Value);
            }
            $Filter->Page = 1;
        }
        //  Search
        if ( isset($_REQUEST['Search']['List']) )
        {
            $Filter->Set_Search();
            foreach ($_REQUEST['Search']['List'] as $prop => $value)
            {
                $Filter->Set_Search($prop, $value);
            }
            $Filter->Page = 1;
        }
        else if ( isset($_REQUEST['Search']['Prop']) )
        {
            $Filter->Set_Search();
            $Filter->Set_Search($_REQUEST['Search']['Prop'], $_REQUEST['Search']['Value']);
            $Filter->Page = 1;
        }
        //  Sorting
        if ( isset($_REQUEST['Sort']['List']) )
        {
            $Filter->Set_Sort();
            foreach ($_REQUEST['Sort']['List'] as $prop => $value)
            {
                $Filter->Set_Sort($prop, $value);
            }
        }
        else if ( isset($_REQUEST['Sort']['Prop']) )
        {
            $Filter->Set_Sort();
            $Filter->Set_Sort($_REQUEST['Sort']['Prop'], $_REQUEST['Sort']['Value']);
        }
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
        $Filter = Zero_Filter::Factory($this->Model);
        //  Move to one level up or down for catalogs
        if ( isset($this->Params['obj_parent_id']) && $this->Params['obj_parent_id'] != Zero_App::$Route->obj_parent_id )
            $this->Chunk_View_CatalogGo($action);

        //  Initialize the fields grid
        $props_grid = $this->Model->Get_Config_Grid();
        //  Remove the coupling condition
        if ( isset($this->Params['obj_parent_prop']) )
            unset($props_grid[$this->Params['obj_parent_prop']]);
        //  Remove the user conditions
        if ( true == $this->User_Condition )
        {
            $users_condition = Zero_App::$Users->Get_Condition();
            foreach ($users_condition as $prop => $value)
            {
                if ( 1 == count($value) )
                    unset($props_grid[$prop]);
            }
        }
        $props = [];
        foreach ($props_grid as $prop => $row)
        {
            $props[] = $row['Grid'] . ' AS ' . $prop;
        }

        $this->Chunk_View_SqlWhere($action);

        //  Data
        $data_grid = $this->Model->DB->Select_Array($props, false);
        //  Count
        $pager_count = $this->Model->DB->Select_Count();
        //  Unrelated
        //  TODO sample catalog move (for linked Add)
        $data_link = [];
        $this->Model->DB->Sql_Reset();

        //  Template
        $this->View->Assign('Section', Zero_App::$Section);
        $this->View->Assign('Interface', Zero_App::$Section->Get_Navigation_Child());
        $this->View->Assign('PropsGrid', $props_grid);
        $this->View->Assign('DataGrid', $data_grid);
        $this->View->Assign('Params', $this->Params);
        $this->View->Assign('Object', $this->Model);
        $this->View->Assign('DataLink', $data_link);
        // Page by page
        $this->View->Assign('PagerPage', $Filter->Page);
        $this->View->Assign('PagerPageItem', Zero_App::$Config->View_PageItem);
        $this->View->Assign('PagerPageStep', Zero_App::$Config->View_PageStep);
        $this->View->Assign('PagerCount', $pager_count);
        //  Filter
        $this->View->Assign('Filter', $Filter->Get_Filter());
        $this->View->Assign('Search', $Filter->Get_Search());
        $this->View->Assign('Sort', $Filter->Get_Sort());
        //  Navigation parent
        $this->View->Assign('url_parent', (0 < Zero_App::$Route->obj_parent_id) ? '-pid-' . Zero_App::$Route->obj_parent_id : '');
        return true;
    }

    /**
     * Delete an object.
     *
     * - Reset Cache removed object
     * - Remove from session
     * - Remove binary data object
     * - Remove cross relation
     *
     * @return boolean flag run of the next chunk
     */
    protected function Action_Remove()
    {
        $ObjectRem = Zero_Model::Make($this->Source, $_REQUEST['obj_id']);
        //  Remove binary data object
        $path = ZERO_PATH_DATA . '/' . strtolower($ObjectRem->Source) . '/' . Zero_Helper_FileSystem::Get_Path_Cache($ObjectRem->ID) . '/' . $ObjectRem->ID;
        if ( is_dir($path) )
            Zero_Helper_FileSystem::Folder_Remove($path);
        // Remove from session
        $ObjectRem->Factory_Unset(1);
        //  Reset Cache
        $ObjectRem->Cache->Reset();
        //  Remove
        if ( $ObjectRem->DB->Delete() )
            return $this->Set_Message('Remove', 0);
        else
            return $this->Set_Message('Error_Remove', 1);
    }
}