<?php

class BillDocMap extends Pix_Table
{
    public function init()
    {
        $this->_name = 'billdocmap';
        $this->_primary = array('docname', 'billno');

        $this->_columns['billno'] = array('type' => 'char', 'size' => 16);
        $this->_columns['docname'] = array('type' => 'varchar', 'size' => 64);
    }
}
