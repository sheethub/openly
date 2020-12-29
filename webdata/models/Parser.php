<?php

class Parser
{
    public function parseBillDetail($billno, $content)
    {
        $doc = new DOMDocument;
        @$doc->loadHTML($content);
        $th_dom = $doc->getElementById('t1');
        $tbody_dom = $th_dom->parentNode;
        while ($tbody_dom->nodeName != 'tbody') {
            $tbody_dom = $tbody_dom->parentNode;
            if (!$tbody_dom) {
                throw new Exception($billno);
            }
        }
        $obj = new StdClass;
        $obj->billNo = $billno;

        foreach ($tbody_dom->childNodes as $tr_dom) {
            if ($tr_dom->nodeName != 'tr') {
                continue;
            }
            $th_dom = $tr_dom->getElementsByTagName('th')->item(0);
            $key = trim($th_dom->nodeValue);

            if (in_array($key, array('審查委員會', '議案名稱', '提案單位/提案委員', '議案狀態', '交付協商'))) {
                $td_dom = $tr_dom->getElementsByTagName('td')->item(0);
                $value = trim($td_dom->nodeValue);
                $obj->{$key} = $value;
            } else if ($key == '相關附件') {
                $obj->{'相關附件'} = array();
                preg_match_all('/<a class="[^"]*"[^>]*href="([^"]*)"\s+title="([^"]*)"/', $doc->saveHTML($tr_dom), $matches);
                foreach ($matches[0] as $idx => $m) {
                    $obj->{'相關附件'}[] = array(
                        '網址' => trim($matches[1][$idx]),
                        '名稱' => trim($matches[2][$idx]),
                    );
                }
            } else if ($key == '關連議案') {
                $obj->{'關連議案'} = array();
                foreach ($tr_dom->getElementsByTagName('a') as $a_dom) {
                    $name = preg_split("/\s+/", trim($a_dom->nodeValue));
                    $billno = explode("'", $a_dom->getAttribute('onclick'))[1];
                    $obj->{'關連議案'}[] = array(
                        'billNo' => $billno,
                        '提案人' => $name[0],
                        '議案名稱' => $name[1],
                    );
                }
            } else if ('提案人' == $key or '連署人' == $key) {
                $obj->{$key} = '';
                if (preg_match("/getLawMakerName\('([^']*)', '([^']*)'\);/", $doc->saveHTML($tr_dom), $matches)) {
                    $obj->{$key} = trim($matches[2]);
                }
            } else if ('議案流程' == $key) {
                $obj->{'議案流程'} = array();
                foreach ($tr_dom->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr') as $sub_tr_dom) {
                    $record = array();
                    $sub_td_doms = $sub_tr_dom->getElementsByTagName('td');
                    $record['會期'] = trim($sub_td_doms->item(0)->nodeValue);
                    $record['日期'] = array();
                    foreach ($sub_td_doms->item(1)->getElementsByTagName('div')->item(0)->childNodes as $dom) {
                        if ($dom->nodeName == 'a') {
                            $record['日期'][] = trim($dom->nodeValue);
                        } else if ($dom->nodeName == '#text' and trim($dom->nodeValue)) {
                            $record['日期'][] = trim($dom->nodeValue);
                        }
                    }
                    $record['院會/委員會'] = trim($sub_td_doms->item(2)->nodeValue);
                    $record['狀態'] = '';
                    foreach ($sub_td_doms->item(3)->childNodes as $n) {
                        if ($n->nodeName == '#text') {
                            $record['狀態'] .= trim($n->nodeValue);
                        }
                    }
                    $record['狀態'] = preg_replace('/\s+/', ' ', $record['狀態']);
                    $obj->{'議案流程'}[] = $record;
                }
            } else {
                $td_dom = $tr_dom->getElementsByTagName('td')->item(0);
                throw new Exception("{$key} 找不到");
            }
        }
        return $obj;
    }

    public static function onlystr($str)
    {
        return preg_Replace('/\s+/', '', $str);
    }

    public static function parseBillDoc($billNo, $content)
    {
        $record = new StdClass;
        $record->billNo = $billNo;

        $doc = new DOMDocument;
        @$doc->loadHTML($content);
        foreach ($doc->getElementsByTagName('p') as $p_dom) {
            if (strpos(trim($p_dom->nodeValue), '院總第') === 0) {
                $tr_dom = $p_dom->parentNode;
                while ('tr' != $tr_dom->nodeName) {
                    $tr_dom = $tr_dom->parentNode;
                }
                // TODO: 審查報告的字號可能會有多筆
                $record->{'字號'} = self::onlystr($tr_dom->nodeValue);
            } else if (strpos(trim($p_dom->nodeValue), '案由：') === 0) {
                $record->{'案由'} = preg_replace('/^案由：/u', '', trim($p_dom->nodeValue));
            } else if (strpos(trim($p_dom->nodeValue), '提案人：') === 0) {
                $record->{'提案人'} = preg_replace('/^提案人：/u', '', trim($p_dom->nodeValue));
            } else if (strpos(trim($p_dom->nodeValue), '連署人：') === 0) {
                $record->{'連署人'} = preg_replace('/^連署人：/u', '', trim($p_dom->nodeValue));
            } else if (in_array(self::onlystr($p_dom->nodeValue), array('修正條文', '增訂條文', '條文', '審查會通過條文', '審查會通過', '審查會條文'))) {
                if (in_array(self::onlystr($p_dom->nodeValue), array('審查會通過', '審查會條文', '審查會通過條文'))) {
                    $record->{'立法種類'} = '審查會版本';
                    // TODO: 審查會通過條文 (處理多筆字號)
                    unset($record->{'字號'});
                }
                $table_dom = $p_dom->parentNode;
                while ('table' != $table_dom->nodeName) {
                    $table_dom = $table_dom->parentNode;
                    if (!$table_dom) {
                        continue 2;
                        throw new Exception("table not found");
                    }
                }
                $record->{'修正記錄'} = array();
                $tr_doms = array();
                foreach ($table_dom->childNodes as $tbody_dom) {
                    if ('tbody' == $tbody_dom->nodeName) {
                        foreach ($tbody_dom->childNodes as $tr_dom) {
                            if ('tr' != $tr_dom->nodeName) {
                                continue;
                            }
                            $tr_doms[] = $tr_dom;
                        }
                    } else if ('tr' == $tbody_dom->nodeName) {
                        $tr_doms[] = $tbody_dom;
                    }
                }
                while ($tr_dom = array_shift($tr_doms)) {
                    $td_doms = array();
                    $only_first = true;
                    foreach ($tr_dom->childNodes as $td_dom) {
                        if ('td' != $td_dom->nodeName) {
                            continue;
                        }
                        if (!count($td_doms) and trim($td_dom->nodeValue) == '') {
                            continue;
                        }
                        if (count($td_doms) and trim($td_dom->nodeValue) != '') {
                            $only_first = false;
                        }
                        if ($td_dom->getAttribute('rowspan')) {
                            for ($i = 0; $i < $td_dom->getAttribute('rowspan') - 1; $i ++) {
                                array_shift($tr_doms);
                            }
                            continue 2;
                        }
                        $td_doms[] = $td_dom;
                    }
                    if ($only_first) {
                        $record->{'對照表標題'} = self::onlystr($td_doms[0]->nodeValue);
                    } else if (in_array(self::onlystr($td_doms[0]->nodeValue), array('審查會通過條文', '審查會通過', '審查會條文'))) {
                        // TODO: 審查會通過條文 (處理多筆字號)
                        unset($record->{'字號'});
                        $columns = array();
                        foreach ($td_doms as $idx => $td_dom) {
                            if (in_array(self::onlystr($td_dom->nodeValue), array('審查會通過條文', '審查會通過', '審查會條文'))) {
                                $columns['審查會通過條文'] = $idx;
                            } else if (in_array(self::onlystr($td_dom->nodeValue), array('現行條文', '現行法條文', '現行法'))) {
                                $columns['現行條文'] = $idx;
                            } else if (self::onlystr($td_dom->nodeValue) == '說明') {
                                $columns['說明'] = $idx;
                            }
                        }
                        $record->{'立法種類'} = '審查會版本';
                        if (!array_key_exists('審查會通過條文', $columns) or !array_key_exists('說明', $columns)) {
                            throw new Exception("找不到審查會通過條文和說明欄位");
                            //echo $doc->saveHTML($tr_dom);
                            //echo json_encode($columns, JSON_UNESCAPED_UNICODE) . "\n";
                            //exit;
                        }
                    } else if (count($td_doms) >= 2 and trim($td_doms[0]->nodeValue) == '修正條文') {
                        $record->{'立法種類'} = '修正條文';
                    } else if (count($td_doms) == 2 and trim($td_doms[0]->nodeValue) == '增訂條文') {
                        $record->{'立法種類'} = '增訂條文';
                    } else if (count($td_doms) == 3 and self::onlystr($td_doms[0]->nodeValue) == '條文' and trim($td_doms[1]->nodeValue) == '現行條文') {
                        $record->{'立法種類'} = '修正條文';
                    } else if (count($td_doms) == 3 and self::onlystr($td_doms[0]->nodeValue) == '條文' and self::onlystr($td_doms[1]->nodeValue) == '參考條文' and self::onlystr($td_doms[2]->nodeValue) == '說明') {
                        $record->{'立法種類'} = '制定條文';
                        $columns['條文'] = 0;
                        $columns['說明'] = 2;
                    } else if (count($td_doms) == 2 and self::onlystr($td_doms[0]->nodeValue) == '條文') {
                        $record->{'立法種類'} = '制定條文';
                        $columns['條文'] = 0;
                        $columns['說明'] = 1;
                    } else if (count($td_doms) == 3 and trim($td_doms[0]->nodeValue) == '修正名稱') {
                        $tr_dom = array_shift($tr_doms);
                        $td_doms = $tr_dom->getElementsByTagName('td');
                        $record->{'名稱修正'} = array(
                            '修正名稱' => trim($td_doms->item(0)->nodeValue),
                            '現行名稱' => trim($td_doms->item(1)->nodeValue),
                            '說明' => str_replace("\t", "", trim($td_doms->item(2)->nodeValue)),
                        );

                    } else if (count($td_doms) == 2 and in_array(trim($td_doms[0]->nodeValue), array('名稱', '法案名稱'))) {
                        $tr_dom = array_shift($tr_doms);
                        $td_doms = $tr_dom->getElementsByTagName('td');
                        $record->{'名稱說明'} = str_replace("\t", "", trim($td_doms->item(1)->nodeValue));
                    } else if ('審查會版本' == $record->{'立法種類'}) {
                        $record->{'修正記錄'}[] = array(
                            '修正條文' => str_replace("\t", "", trim($td_doms[$columns['審查會通過條文']]->nodeValue)),
                            '現行條文' => array_key_exists('現行條文', $columns) ? str_replace("\t", "", trim($td_doms[$columns['現行條文']]->nodeValue)) : '',
                            '說明' => str_replace("\t", "", trim($td_doms[$columns['說明']]->nodeValue)),
                        );
                    } else if ('修正條文' == $record->{'立法種類'}) { // and $td_doms->length == 3) {
                        $record->{'修正記錄'}[] = array(
                            '修正條文' => str_replace("\t", "", trim($td_doms[0]->nodeValue)),
                            '現行條文' => str_replace("\t", "", trim($td_doms[1]->nodeValue)),
                            '說明' => str_replace("\t", "", trim($td_doms[2]->nodeValue)),
                        );
                    } else if ('增訂條文' == $record->{'立法種類'} and count($td_doms) == 2) {
                        $record->{'修正記錄'}[] = array(
                            '增訂條文' => str_replace("\t", "", trim($td_doms[0]->nodeValue)),
                            '說明' => str_replace("\t", "", trim($td_doms[1]->nodeValue)),
                        );
                    } else if ('制定條文' == $record->{'立法種類'}) {
                        $record->{'修正記錄'}[] = array(
                            '條文' => str_replace("\t", "", trim($td_doms[$columns['條文']]->nodeValue)),
                            '說明' => str_replace("\t", "", trim($td_doms[$columns['說明']]->nodeValue)),
                        );
                    } else {
                        if ($record->{'立法種類'} == '審查會版本') {
                           // == '1070321070300100') {
                            continue;
                        }
                        continue;
                        echo $doc->saveHTML($tr_dom);
                        echo 'trim($td_doms[0]->nodeValue) => ' .trim($td_doms[0]->nodeValue) . "\n";
                        throw new Exception("error");
                        exit;
                    }
                }
            }
        }
        return $record;
    }
}
