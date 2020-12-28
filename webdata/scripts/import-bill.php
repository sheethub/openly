<?php

// 匯入舊資料
include(__DIR__ . '/../init.inc.php');
Pix_Table::enableLog(Pix_Table::LOG_QUERY);
foreach (glob("/srv/db1/ly-parser/20201113/full/bills/*.gz") as $gz_file) {
    preg_match('#([^/]*)\.gz#', $gz_file, $matches);
    $billno = $matches[1];
    $content = gzdecode(file_get_contents($gz_file));
    $obj = Parser::parseBillDetail($billno, $content);
    Bill::insert(array(
        'billno' => $billno,
        'wordno' => '',
        'data' => json_encode(array(
            'detail_fetch_at' => filemtime($gz_file),
            'detail' => $obj,
            'doc_parse_at' => 0,
        )),
    ));
}
