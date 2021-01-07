<?php

class MeetingController extends Pix_Controller
{
    public function indexAction()
    {
        $latest_date = Meeting::getLatestMeetingDate();
        return $this->redirect('/meeting/list/' . date('Y/m', strtotime($latest_date)));
    }

    public function listAction()
    {
        list(, /*meeting*/, /*list*/, $year, $month) = explode('/', $this->getURI());
        $this->view->year = intval($year);
        $this->view->month = intval($month);
    }

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
