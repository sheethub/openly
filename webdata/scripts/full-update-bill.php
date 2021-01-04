<?php

include(__DIR__ . '/../init.inc.php');

Pix_Table::$_save_memory = true;
Pix_Table::enableLog(Pix_Table::LOG_QUERY);

$billnos = array();
$command = '';
$handle_bills = function(&$billnos) use (&$command) {
    foreach(BillDoc::search(1)->searchIn('billno', array_keys($billnos)) as $billdoc) {
        $bill = $billnos[$billdoc->billno];
        $bill_data = json_decode($bill->data);
        $billdoc_data = json_decode($billdoc->data);

        $command .= json_encode(array(
            'update' => array('_id' => $billdoc->billno),
        )) . "\n";

        $command .= json_encode(array(
            'doc' => array(
                'billno' => $bill->billno,
                '第字號' => $bill->wordno,
                '提案人' => $bill_data->detail->{'提案人'},
                '連署人' => $bill_data->detail->{'連署人'},
                '議案名稱' => $bill_data->detail->{'議案名稱'},
                '案由' => $billdoc_data->{'案由'},
                '立法說明' => implode('', array_map(function($r) { return $r->{'說明'}; }, $billdoc_data->{'修正記錄'})),
            ),
            'doc_as_upsert' => true,
        ), JSON_UNESCAPED_UNICODE) . "\n";
    }
    $billnos = array();
    $url = getenv('SEARCH_URL') . '/bill/_bulk';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_PROXY, '');
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $command);
    $ret = json_decode(curl_exec($curl));
    $info = curl_getinfo($curl);
    if (!in_array($info['http_code'], array(200, 201))) {
        throw new Exception($info['http_code'] . ' ' . $ret);
    }
    $count = 0;
    $command = '';
    if ($ret->errors) {
        print_r($ret);
        exit;
    }
};

foreach (Bill::search(1)->order('billno')->volumemode(1000) as $bill) {
    $billnos[$bill->billno] = $bill;
    if (count($billnos) == 1000) {
        $handle_bills($billnos);
    }
}
$handle_bills($billnos);
