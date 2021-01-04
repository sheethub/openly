<?php

class SearchLib
{
    public function searchBill($name, $page = 1)
    {
        $curl = curl_init();
        $from = 20 * ($page - 1);
        $cmd = array(
            'query' => array(
                'filtered' => array(
                    'query' => array('multi_match' => array(
                        'query' => $name,
                        'fields' => array('議案名稱', '案由', '立法說明'),
                        'type' => 'phrase',
                        'operator' => 'and'
                    )),
                ),
            ),
            'from' => 20 * ($page - 1),
            'size' => 20,
            'highlight' => array(
                'fields' => array(
                    '議案名稱' => new StdClass,
                    '案由' => new StdClass,
                    '立法說明' => new StdClass,
                ),
            ),
            'sort' => array(
                array('billno' => 'desc'),
            ),
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cmd));
        curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/bill/_search?from=' . $from);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $ret = curl_exec($curl);
        return json_decode($ret);
    }
}
