<?php

/**
 * Navigating the subsections of the current section.
 *
 * @sample {plugin "Zero_Section_Plugin_NavigationChild" view="" section_id="0"}
 *
 * @package Zero.Section.Navigation
 * @author Konstantin Shamiev aka ilosa <konstantin@shamiev.ru>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
class Zero_Section_Plugin_NavigationChild extends Zero_Controller
{
    /**
     * Vy`polnenie dei`stvii`
     *
     * @return Zero_View or string
     */
    public function Action_Default()
    {
        if ( isset($this->Params['section_id']) && 0 < $this->Params['section_id'] )
            $Section = Zero_Model::Makes('Zero_Section', $this->Params['section_id']);
        else
            $Section = Zero_App::$Section;
        /* @var $Section Zero_Section */
        $navigation = $Section->Get_Navigation_Child();
        if ( 0 == count($navigation) )
        {
            $Section = Zero_Model::Makes('Zero_Section', Zero_App::$Section->Section_ID);
            $navigation = $Section->Get_Navigation_Child();
        }
        $this->Chunk_Init();
        $this->View->Assign('Section', Zero_App::$Section);
        $this->View->Assign('navigation', $navigation);
        return $this->View;
    }

    /**
     * ������������� �����������
     *
     * @return bool
     */
    protected function Chunk_Init()
    {
        // ������
        if ( isset($this->Params['view']) )
            $this->View = new Zero_View($this->Params['view']);
        else if ( isset($this->Params['tpl']) )
            $this->View = new Zero_View($this->Params['tpl']);
        else if ( isset($this->Params['template']) )
            $this->View = new Zero_View($this->Params['template']);
        else
            $this->View = new Zero_View(get_class($this));
        // ������ (������)
        // $this->Model = Zero_Model::Makes('Zero_Users');
        return true;
    }
}