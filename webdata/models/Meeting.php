<?php

class Meeting extends Pix_Table
{
    public function init()
    {
        $this->_name = 'meeting';
        $this->_primary = 'meeting_id';

        $this->_columns['meeting_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['term'] = array('type' => 'int');
        $this->_columns['session_period'] = array('type' => 'int');
        $this->_columns['session_times'] = array('type' => 'int');
        $this->_columns['meeting_times'] = array('type' => 'int');
        $this->_columns['session_type'] = array('type' => 'int');
        $this->_columns['dates'] = array('type' => 'jsonb');
        $this->_columns['data'] = array('type' => 'jsonb');

        $this->addIndex('term_period_times', array('term', 'session_period', 'session_times', 'meeting_times', 'session_type'), 'unique');

    }
}
