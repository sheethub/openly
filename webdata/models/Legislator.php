<?php

// http://data.ly.gov.tw/odw/usageFile.action?id=16&type=CSV&fname=16_CSV.csv
class Legislator extends Pix_Table
{
    public function init()
    {
        $this->_name = 'legislator';
        $this->_primary = array('term', 'name');

        $this->_columns['term'] = array('type' => 'int');
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['data'] = array('type' => 'jsonb');
    }
}
