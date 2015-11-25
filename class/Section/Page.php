<?php

/**
 * Content Page.
 *
 * @package Zero.Section.Page
 * @author Konstantin Shamiev aka ilosa <konstantin@shamiev.ru>
 * @date 2015.01.01
 */
class Zero_Section_Page extends Zero_Controller
{
    /**
     * Create views.
     *
     * @return boolean flag stop execute of the next chunk
     */
    public function Action_Default()
    {
        $this->View = new Zero_View(get_class($this));
        $this->View->Assign('Name', Zero_App::$Section->Name);
        $this->View->Assign('Content', Zero_App::$Section->Content);
        return $this->View;
    }
}
