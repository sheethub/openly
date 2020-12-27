<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::enableLog(Pix_Table::LOG_QUERY);
$url = 'https://lci.ly.gov.tw/LyLCEW/lcivAgendaMore.action?rangeCondition2=&sortFieldListSource2=meeting_date1%3A1&termQryS=&periodQryS=&timesQryS=&termQryE=&periodQryE=&timesQryE=&meetingDateQryS=&meetingDateQryE=';
$content = file_get_contents($url);

$doc = new DOMDocument;
@$doc->loadHTML($content);
error_log("更新議事日程列表");
foreach ($doc->getElementsByTagName('td') as $td_dom) {
    if (strpos($td_dom->getAttribute('onclick'), 'queryLcivMaster') === false) {
        continue;
    }
    // XXX
    continue;
    preg_match('#queryLcivMaster\(\'(.*)\'\);#', $td_dom->getAttribute('onclick'), $matches);
    $terms = explode("','", $matches[1]);
    list($term, $session_period, $session_times, $session_type, $meeting_times) = $terms;
    $td_doms = $td_dom->parentNode->getElementsByTagName('td');
    $dates = explode(',', preg_replace('#\s+#', '', $td_doms->item(3)->nodeValue));
    $dates = array_map(function($d) {
        list($y,$m,$d) = explode('/', $d);
        return sprintf("%04d-%02d-%02d", $y + 1911, $m, $d);
    }, $dates);

    if (!$m = Meeting::search(array(
            'term' => intval($term),
            'session_period' => intval($session_period),
            'session_times' => intval($session_times),
            'session_type' => intval($session_type),
            'meeting_times' => intval($meeting_times),
        ))->first()) {

        Meeting::insert(array(
            'term' => intval($term),
            'session_period' => intval($session_period),
            'session_times' => intval($session_times),
            'session_type' => intval($session_type),
            'meeting_times' => intval($meeting_times),
            'dates' => json_encode($dates),
            'data' => json_encode(array('menu_fetch_at' => 0)),
        ));
        // TODO: update dates
    }
}
error_log("更新議事日程列表完成");

error_log("更新議事日程目錄");
foreach (Meeting::search(1) as $meeting) {
    $d = json_decode($meeting->data);
    if ($d->menu_fetch_at) {
        continue;
    }

    $url = sprintf('https://lci.ly.gov.tw/LyLCEW/lcivAgendaMaster.action?term=%02d&session_period=%02d&session_times=%02d&session_type=%02d&meeting_times=%s',
        $meeting->term,
        $meeting->session_period,
        $meeting->session_times,
        $meeting->session_type,
        $meeting->meeting_times ? sprintf("%02d", $meeting->meeting_times) : 'xx'
    );
    error_log($url);
    $content = file_get_contents($url);
    //file_put_contents("tmp2", $content);
    $doc = new DOMDocument;
    @$doc->loadHTML($content);
    foreach ($doc->getElementsByTagName('td') as $td_dom) {
        if (strpos($td_dom->getAttribute('onclick'), 'queryLcivDetail') === false) {
            continue;
        }
        $td_doms = $td_dom->parentNode->getElementsByTagName('td');
        preg_match('#queryLcivDetail\(\'(.*)\'\)#', $td_dom->getAttribute('onclick'), $matches);
        $terms = explode("','", $matches[1]);
        $agenda_type = $terms[3];
        $book_id = $terms[6];
        $meeting_no = intval(trim($td_doms->item(0)->nodeValue, '.'));

        MeetingMenu::insert(array(
            'meeting_id' => $meeting->meeting_id,
            'meeting_no' => $meeting_no,
            'title' => trim($td_doms->item(1)->nodeValue),
            'data' => json_encode(array(
                'agenda_fetch_at' => 0,
                'time' => trim($td_doms->item(2)->nodeValue),
                'location' => trim($td_doms->item(3)->nodeValue),
                'agenda_type' => $agenda_type,
                'book_id' => $book_id,
            )),
        ));
    }
    $d->menu_fetch_at = time();
    $meeting->update(array('data' => json_encode($d)));
}
error_log("更新議事日程目錄完成");

foreach (MeetingMenu::search(1) as $meeting_menu) {
    $d = json_decode($meeting_menu->data);
    $meeting = Meeting::find($meeting_menu->meeting_id);
    if ($d->agenda_fetch_at) {
        continue;
    }

    $url = sprintf('https://lci.ly.gov.tw/LyLCEW/lcivAgendaDetail.action?sortFieldListSource=%s&queryIndexeListSource=%s&fieldNameListSource=%s&term=%02d&session_period=%02d&session_times=%02d&agenda_type=%s&session_type=%02d&meeting_times=%s&book_id=%s',
        urlencode('agenda_type:0,file_seqno:0'),
        urlencode('0:lciv_agendafile,0:lciv_agendafile2,0:lcia_agendafile'),
        urlencode('subject,proposal_type_seqno,options,term_session,pdf_filename,term,session_period,session_times,file_seqno,bill_no,agenda_many,assign_char,assign_page_no,session_type,meeting_times,agenda_type'),
        $meeting->term,
        $meeting->session_period,
        $meeting->session_times,
        urlencode($d->agenda_type),
        $meeting->session_type,
        $meeting->meeting_times ? sprintf("%02d", $meeting->meeting_times) : 'xx',
        urlencode($d->book_id)
    );
    error_log($url);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($curl);
    //file_put_contents("tmp2", $content);
    $doc = new DOMDocument;
    @$doc->loadHTML($content);
    $tbody_dom = $doc->getElementsByTagName('tbody')->item(0);

    if ($tbody_dom) {
        foreach ($tbody_dom->getElementsByTagName('tr') as $tr_dom) {
            $td_doms = $tr_dom->getElementsByTagName('td');
            // 第二欄只會是 openPDF 和 window.open 嗎？
            $onclick = $td_doms->item(1)->getAttribute('onclick');
            $data = new StdClass;
            $data->title = trim($td_doms->item(1)->nodeValue);
            if (strpos($onclick, 'openPDF') === 0) {
                // 宣讀前一次議事錄 
            } else if (preg_match('#window.open\(\'(http://lci.ly.gov.tw/LyLCEW/.*.pdf)\s*\'\)#', $onclick, $matches)) {
                $data->pdf_link = $matches[1];
                if (!$button_dom = $td_doms->item(5)->getElementsByTagName('button')->item(0)) {
                    throw new Exception("{$data->title} 找不到 button");
                }
                $onclick = $button_dom->getAttribute('onclick');
                if (!preg_match('#^openpop\(\'(.*)\'\)$#', $onclick, $matches)) {
                    throw new Exception("{$data->title} 未知的 onclick " . $onclick);
                }
                $terms = explode("','", $matches[1]);
                if (preg_match('#\.doc$#', $terms[6])) {
                    $data->doc_link = $terms[6];
                    //throw new Exception("{$data->title} 未知的 onclick " . $onclick);
                }
            } else {
                //throw new Exception("{$data->title} 未知的 onclick " . $onclick);
            }
            $data->mapping_bill = trim($td_doms->item(2)->nodeValue);
            $data->opinion = trim($td_doms->item(3)->nodeValue);
            $data->term_period = trim($td_doms->item(4)->nodeValue);
            error_log(json_encode($data, JSON_UNESCAPED_UNICODE));
            MeetingAgenda::insert(array(
                'meeting_id' => $meeting->meeting_id,
                'meetingmenu_id' => $meeting_menu->meetingmenu_id,
                'data' => json_encode($data),
            ));
        }
    }
    $d->agenda_fetch_at = time();
    $meeting_menu->update(array('data' => json_encode($d)));
}
