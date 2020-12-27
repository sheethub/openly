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
}
