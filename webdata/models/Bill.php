<?php

class BillRow extends Pix_Table_Row
{
    public function getTerm()
    {
        $data = json_decode($this->data);
        foreach ($data->detail->{'議案流程'} as $record) {
            if ($record->{'會期'}) {
                $term = explode('-', $record->{'會期'})[0];
                if ($term) {
                    return $term;
                }
            }
        }
    }

    public function getDocURLs()
    {
        $docUrls = array();

        $values = json_decode($this->data)->detail;
        $pdfUrl = $docUrl = false;
        foreach ($values->{'相關附件'} as $record) {
            if ($record->{'名稱'} == '關係文書(PDF)下載') {
                $pdfUrl = $record->{'網址'};
            } else if ($record->{'名稱'} == '關係文書(DOC)下載') {
                $docUrl = $record->{'網址'};
            } else if (strpos($docUrl, 'http://lci.ly.gov.tw/LyLCEW/LCEWA01') === 0 and strpos($record->{'名稱'}, '檔案上傳時間') === 0) {
                if (strpos($record->{'網址'}, 'http://lci.ly.gov.tw/LyLCEW//LCEWA01') === 0) {
                } else {
                    $docUrls[$record->{'網址'}] = $record->{'網址'};
                }
            }
        }
        if (count($docUrls)) {
            $docUrls = array_values($docUrls);
            return $docUrls;
        } else {
            if (preg_match('#http://lci.ly.gov.tw/LyLCEW/agenda1/(\d+)/pdf(/\d+/\d+/\d+(/\d+)?/LCEWA\d+_\d+_\d+)\.pdf#', $pdfUrl, $matches)) {
                if ($docUrl != 'http://lci.ly.gov.tw/LyLCEW/agenda1/' . $matches[1] . '/word' . $matches[2] . '.doc') {
                    $docUrl = 'http://lci.ly.gov.tw/LyLCEW/agenda1/' . $matches[1] . '/word' . $matches[2] . '.doc';
                }
            }
        }
        if (!$docUrl or $docUrl == 'http://lci.ly.gov.tw/LyLCEW/') {
            return array();
        }
        $docUrl = str_replace('http://', 'https://', $docUrl);
        return array($docUrl);
    }
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
        $this->_columns['docname'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['data'] = array('type' => 'jsonb');

        $this->addIndex('wordno', array('wordno'));
        $this->addIndex('docname', array('docname'));
    }

    public static function findBill($billno)
    {
        return Bill::find(substr($billno, 0, -2) . '00');
    }
}
