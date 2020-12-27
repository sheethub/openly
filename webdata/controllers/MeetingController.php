<?php

class MeetingController extends Pix_Controller
{
    public function showAction()
    {
        list(,/*meeting*/, /*show*/, $id, $menu_id) = explode('/', $this->getURI());
        if (!$meeting = Meeting::find($id)) {
            return $this->notfound();
        }
        $this->view->meeting = $meeting;
        if (intval($menu_id) and $mmenu = MeetingMenu::find(intval($menu_id)) and $mmenu->meeting_id == $id) {
            $this->view->mmenu = $mmenu;
        } else {
            $this->view->mmenu = MeetingMenu::search(array('meeting_id' => $id))->order('meeting_no ASC')->first();
        }
    }
}
