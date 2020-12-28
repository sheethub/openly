<?php

class BillDoc extends Pix_Table
{
    public function init()
    {
        $this->_name = 'billdoc';
        $this->_primary = 'billno';

        $this->_columns['billno'] = array('type' => 'char', 'size' => 16);
        $this->_columns['data'] = array('type' => 'jsonb');
    }
}
