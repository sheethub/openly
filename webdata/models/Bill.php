<?php

class BillRow extends Pix_Table_Row
{
}

class Bill extends Pix_Table
{
    public function init()
    {
        $this->_name = 'bill';
        $this->_primary = 'billno';
        $this->_rowClass = 'BillRow';

        $this->_columns['billno'] = array('type' => 'char', 'size' => 16);
        $this->_columns['wordno'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['data'] = array('type' => 'jsonb');

        $this->addIndex('wordno', array('wordno'));
    }
}
