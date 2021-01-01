<?php

class MeetingRow extends Pix_Table_Row
{
    public function getName()
    {
        if ($this->session_type == 1) { // 全院委員會
            return sprintf("第%02d屆第%02d會期第%02d次會議", $this->term, $this->session_period, $this->session_times);
        } else if ($this->session_type == 2) { // 常會
            return sprintf("第%02d屆第%02d會期第%02d次會議", $this->term, $this->session_period, $this->session_times);
        } else if ($this->session_type == 3) { // 臨時會
            return sprintf("第%02d屆第%02d會期第%02d次臨時會第%02d次會議", $this->term, $this->session_period, $this->session_times, $this->meeting_times);
        } else if ($this->session_type == 4) { // 臨時會全院委員會
            return sprintf("第%02d屆第%02d會期第%02d次臨時會第%02d次全院委員會", $this->term, $this->session_period, $this->session_times, $this->meeting_times);
        }

    }
}

class Meeting extends Pix_Table
{
    public function init()
    {
        $this->_name = 'meeting';
        $this->_primary = 'meeting_id';
        $this->_rowClass = 'MeetingRow';

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

    public static function searchByYearMonth($year, $month)
    {
        return Meeting::search(sprintf("dates::text LIKE '%%%04d-%02d-%%'", intval($year), intval($month)));
    }

    public static function getLatestMeetingDate()
    {
        $sql = "SELECT JSONB_ARRAY_ELEMENTS(dates) AS d FROM meeting ORDER BY d DESC LIMIT 1;";
        $res = Meeting::getDb()->query($sql);
        return json_decode($res->fetch_array()[0]);
    }
}
