<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::enableLog(Pix_Table::LOG_QUERY);

if (true) {
    $endt = time();
    $startt = time() - 7 * 86400;
    $start = sprintf("%03d/%02d/%02d", date('Y', $startt) - 1911, date('m', $startt), date('d', $startt));
    $end = sprintf("%03d/%02d/%02d", date('Y', $endt) - 1911, date('m', $endt), date('d', $endt));
    $start = '';
    $end = '';
    //$term = 10;
    $url = "https://misq.ly.gov.tw/MISQ/IQuery/misq5000QueryBill.action?queryMultipleAdvance=Y&specialTimesRadioVal=0&tmpProposalPersonType=0&tmpProposalPersonType1=0&filePath=%2Fmisq%2Ftomcat%2Fwebapps%2FMISQ&proposalDate=&billName=&proposalDateS=" . urlencode($start) . "&proposalDateE=" . urlencode($end) . "&specialTimesRadio=0&term={$term}&sessionPeriod=&sessionTimes=&meetingTimes=&agendaType=&yuanNo=&proposalType=&proposalNo=&mainCommittee=&legislatorTermSelect=10&legislator=undefined&legislatorListSelect=&legislatorName=&proposalPersonType=0&billState=&legislatorTeam=&proposalPersonType1=0&advancedFlag=true";
    // 搜 「。」
    $url = 'https://misq.ly.gov.tw/MISQ/IQuery/misq5000QueryBill.action?billNo=&meetingNo=&meetingTime=&outNo=&departmentCode=&title=&querySelect=1&billName=%E3%80%82&committeeName=&meetingDateS=&meetingDateE=';
    error_log($url);
    $content = file_get_contents($url);
    file_put_contents('tmp', $content);

    $fp = fopen('tmp', 'r');
    $billnos = array();
    while ($line = fgets($fp)) {
        if (preg_match("#queryDetail\('(\d+)'\)#", $line, $matches)) {
            $billno = $matches[1];
            while ($line = fgets($fp)) {
                if (preg_match('#headers="t52">(.*)</td>#', $line, $matches)) {
                    $billnos[$billno] = $matches[1];
                    break;
                }
            }
        }
    }

    // 找出不存在資料庫中的 billno
    $fetch_billnos = array();
    foreach (array_chunk($billnos, 1000, true) as $chunked_billnos) {
        $ids = implode(",", array_map(function($a){ return "'{$a}'"; }, array_keys($chunked_billnos)));
        $sql = "SELECT billno, data->'detail'->>'議案狀態' FROM bill WHERE billno IN ({$ids})";
        $res = Bill::getDb()->query($sql);
        while ($row = $res->fetch_array()) {
            if (array_key_exists($row[0], $chunked_billnos)) {
                if ($chunked_billnos[$row[0]] == $row[1]) {
                    unset($chunked_billnos[$row[0]]);
                } else {
                    $chunked_billnos[$row[0]] = 'update';
                }
            }
        }
        foreach ($chunked_billnos as $k => $v) {
            $fetch_billnos[$k] = $v;
        }
    }
    error_log("found " . count($billnos) . " bills, " . count($fetch_billnos) . " new bills");
    foreach ($fetch_billnos as $billno => $status) {
        if ($status == 'update') {
            $b = Bill::find($billno);
            $data = json_decode($b->data);
            $data->detail_fetch_at = 0;
            $b->update(array('data' => json_encode($data)));
            $target = getenv('DATA_PATH') . "/bill/{$billno}.gz";
            if (file_exists($target)) {
                unlink($target);
            }
        } else {
            Bill::insert(array(
                'billno' => $billno,
                'wordno' => '',
                'data' => json_encode(array(
                    'detail_fetch_at' => 0,
                    'doc_parse_at' => 0,
                )),
            ));
        }
    }
}

foreach (Bill::search("(data->>'detail_fetch_at')::int = 0 OR JSONB_ARRAY_LENGTH(data->'detail'->'相關附件') = 0")->order('billno DESC') as $bill) {
    $target = getenv('DATA_PATH') . "/bill/{$bill->billno}.gz";
    if (!file_exists($target) or filesize($target) < 100) {
        error_log($target);
        $content = file_get_contents("https://misq.ly.gov.tw/MISQ/IQuery/misq5000QueryBillDetail.action?billNo=" . urlencode($bill->billno));
        file_put_contents($target, gzencode($content));
    }
    $billno = $bill->billno;
    $data = json_decode($bill->data);
    $content = gzdecode(file_get_contents($target));
    $data->detail_fetch_at = filemtime($target);
    $data->detail = Parser::parseBillDetail($billno, $content);
    $bill->update(array('data' => json_encode($data)));
    //sleep(1);
}
