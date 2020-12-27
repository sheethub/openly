<?php

class MeetingAgenda  extends Pix_Table
{
    public function init()
    {
        $this->_name = 'meeting_agenda';
        $this->_primary = 'meetingagenda_id';

        $this->_columns['meetingagenda_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['meeting_id'] = array('type' => 'int');
        $this->_columns['meetingmenu_id'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'jsonb');

        $this->addIndex('meeting_id', array('meeting_id'));
        $this->addIndex('meetingmenu_id', array('meetingmenu_id'));
    }
}
