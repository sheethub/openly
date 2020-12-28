<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::enableLog(Pix_Table::LOG_QUERY);

if (true) {
    $endt = time();
    $startt = time() - 7 * 86400;
    $start = sprintf("%03d/%02d/%02d", date('Y', $startt) - 1911, date('m', $startt), date('d', $startt));
    $end = sprintf("%03d/%02d/%02d", date('Y', $endt) - 1911, date('m', $endt), date('d', $endt));
    $url = "https://misq.ly.gov.tw/MISQ/IQuery/misq5000QueryBill.action?queryMultipleAdvance=Y&specialTimesRadioVal=0&tmpProposalPersonType=0&tmpProposalPersonType1=0&filePath=%2Fmisq%2Ftomcat%2Fwebapps%2FMISQ&proposalDate=&billName=&proposalDateS=" . urlencode($start) . "&proposalDateE=" . urlencode($end) . "&specialTimesRadio=0&term=&sessionPeriod=&sessionTimes=&meetingTimes=&agendaType=&yuanNo=&proposalType=&proposalNo=&mainCommittee=&legislatorTermSelect=10&legislator=undefined&legislatorListSelect=&legislatorName=&proposalPersonType=0&billState=&legislatorTeam=&proposalPersonType1=0&advancedFlag=true";
    error_log($url);
    $content = file_get_contents($url);
    file_put_contents('tmp', $content);
    preg_match_all("#queryDetail\('(\d+)'\)#", $content, $matches);
    $billnos = array_unique($matches[1]);
    $billnos = array_combine($billnos, $billnos);
    error_log("found " . count($billnos) . " bills");
    $existed_billnos = array();
    if ($billnos) {
        $existed_billnos = Bill::search("billno IN (" . implode(",", array_map(function($a) { return "'{$a}'"; }, $billnos)) . ")")->toArray("billno");
    }
    $hit = 0;
    foreach ($billnos as $billno) {
        $hit ++;
        if (in_array($billno, $existed_billnos)) {
            unset($billnos[$billno]);
        }
    }
    foreach ($billnos as $billno) {
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

foreach (Bill::search("(data->>'detail_fetch_at')::int = 0") as $bill) {
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
