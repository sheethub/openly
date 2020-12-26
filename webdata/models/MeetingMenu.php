<?php

class MeetingMenu extends Pix_Table
{
    public function init()
    {
        $this->_name = 'meeting_menu';
        $this->_primary = 'meetingmenu_id';

        $this->_columns['meetingmenu_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['meeting_id'] = array('type' => 'int');
        $this->_columns['meeting_no'] = array('type' => 'int');
        $this->_columns['title'] = array('type' => 'text');
        $this->_columns['data'] = array('type' => 'jsonb');

        $this->addIndex('meeting_no', array('meeting_id', 'meeting_no'), 'unique');
    }
}
