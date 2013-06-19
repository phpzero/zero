<?php

/**
 * Plugin. A two-level navigation through the main sections of the site.
 *
 * - 2 и 3 уровень.
 * Sample: {plugin "Zero_Section_NavigationAccordion" template=""}
 *
 * @package Zero.Section.Plugin
 * @author Konstantin Shamiev aka ilosa <konstantin@phpzero.com>
 * @version $Id$
 * @link http://www.phpzero.com/
 * @copyright <PHP_ZERO_COPYRIGHT>
 * @license http://www.phpzero.com/license/
 */
class Zero_Section_NavigationAccordion extends Zero_Plugin
{
    /**
     * Initialize the stack chunks
     *
     */
    protected function Init_Chunks()
    {
        $this->Set_Chunk('View');
    }

    /**
     * Formation of a two tier navigation through the main sections of the site.
     *
     * @return boolean flag run of the next chunk
     */
    protected function Chunk_View()
    {
        $index = __CLASS__ . Zero_App::$Users->Zero_Groups_ID . Zero_App::$Config->Host;
        $Section = Zero_Model::Make('Zero_Section');
        /* @var $Section Zero_Section */
        $Section->Init_Url(Zero_App::$Config->Host);
        if ( false === $navigation = $Section->Cache->Get($index) )
        {
            $navigation = Zero_Section::DB_Navigation_Child($Section->ID);
            foreach (array_keys($navigation) as $id)
            {
                $navigation[$id]['child'] = Zero_Section::DB_Navigation_Child($id);
            }
            $Section->Cache->Set($index, $navigation);
        }
        if ( isset($this->Params['template']) )
            $this->View = new Zero_View($this->Params['template']);
        else
            $this->View = new Zero_View(get_class($this));
        $this->View->Assign('Section', Zero_App::$Section);
        $this->View->Assign('navigation', $navigation);
        return true;
    }
}