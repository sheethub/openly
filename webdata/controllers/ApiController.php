<?php

class ApiController extends Pix_Controller
{
    public function init()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
    }

    public function error($message)
    {
        return $this->json(array(
            "error" => true,
            "message" => $message,
        ));
    }

    public function billAction()
    {
        list(, /*api*/, /*bill*/, $billno) = explode('/', $this->getURI());
        if (!$bill = Bill::find(strval($billno))) {
            return $this->error("not found");
        }
        $billdoc = BillDoc::find(strval($billno));
        $billdata = json_decode($billdoc->data);
        $obj = new StdClass;
        $obj->data = new StdClass;
        $obj->data->related = array();

        $clean_law = function($content) {
            $content = preg_replace("/\n+/", "\n", $content);
            return $content;
        };

        if ($billdata->{'立法種類'} == '修正條文' or $billdata->{'立法種類'} == '審查會版本') {
            $law = new StdClass;
            $law->type = 'lawproposal';
            $law->name = $billdata->{'對照表標題'};
            $law->header = array('修正條文', '現行條文', '說明');
            $law->content = array();

            foreach ($billdata->{'修正記錄'} as $line) {
                $law->content[] = array(
                    $clean_law($line->{'修正條文'}),
                    $clean_law($line->{'現行條文'}),
                    $clean_law($line->{'說明'}),
                );
            }
            $obj->data->content = array($law);
        } else if ($billdata->{'立法種類'} == '制定條文') {
            $law = new StdClass;
            $law->type = 'lawproposal';
            $law->name = $billdata->{'對照表標題'};
            $law->header = array('條文', '說明');
            $law->content = array();

            foreach ($billdata->{'修正記錄'} as $line) {
                $law->content[] = array(
                    $clean_law($line->{'條文'}),
                    $clean_law($line->{'說明'}),
                );
            }
            $obj->data->content = array($law);
        }
        $bill_obj = json_decode($bill->data)->detail;
        $obj->doc = array(
            $bill_obj->{'相關附件'}[0]->{'網址'},
            $bill_obj->{'相關附件'}[1]->{'網址'},
        );
        $obj->bill_id = $billno;
        $obj->bill_ref = $billno;
        $obj->summary = $bill_obj->{'議案名稱'};
        $obj->proposed_by = $bill_obj->{'提案單位/提案委員'};
        $obj->abstract = $billdata->{'案由'};
        $obj->bill_type = 'legislative';
        $obj->report_of = null;
        $obj->reconsideration_of = null;
        $obj->sitting_introduced = '08-04-YS-07';
        $split = function($str) {
            $str = str_replace(' ', '', $str);
            $str = str_replace('陳　瑩', '陳瑩', $str);
            $str = str_replace('范　雲', '范雲', $str);
            $str = preg_replace('#　+$#u', '', $str);
            return preg_split('#　+#u', $str);
        };
        $obj->sponsors = $split($bill_obj->{'提案人'});
        $obj->cosponsors = $split($bill_obj->{'連署人'});
        $obj->motions = array();
        /*$obj->motions[] = array(
        'agenda_item' => 54,
        'committee' => "{JUD}",
        'dates' => array(),
        'item' => 54,
        'motion_class' => "announcement",
        'resolution' => "決定：交司法及法制委員會審查。",
        'sitting' => 7,
        'sitting_id' => "08-04-YS-07",
        'status' => "committee",
        );*/
        return $this->json($obj);
    }

    public function ttsmotionsAction()
    {
        $obj = new StdClass;
        $obj->paging = new StdClass;
        $obj->paging->count = 1;
        $obj->entries = array();
        // TODO: change this, fake data
        foreach ($bill_obj->{'議案流程'} as $line) {
        }
?>

    {"paging":{"count":1},"entries":[{"tts_key":"17498:154","date":"2013-10-25","source":"{\"{\\\"text\\\":\\\"12\\\",\\\"link\\\":[\\\"a\\\",8,4,7,71,\\\"12\\\"]}\",\"{\\\"text\\\":\\\"209-238\\\",\\\"link\\\":[\\\"a\\\",8,4,7,72,\\\"209-238\\\"]}\"}","sitting_id":"08-04-YS-07","chair":"{\u738b\u91d1\u5e73,\u6d2a\u79c0\u67f1}","motion_type":"{\u6cd5\u5f8b\u6848,\u6cd5\u5f8b\u6848}","summary":"\u4e94\u5341\u56db\u3001\u672c\u9662\u59d4\u54e1\u912d\u9e97\u541b\u7b4922\u4eba\u64ec\u5177\u300c\u6c11\u6cd5\u89aa\u5c6c\u7de8\u3001\u7e7c\u627f\u7de8\u90e8\u5206\u689d\u6587\u4fee\u6b63\u8349\u6848\u300d\uff0c\u8acb\u5be9\u8b70\u6848\u3002(\u66f4\u6b63\u672c)","resolution":"\u4ea4\u53f8\u6cd5\u53ca\u6cd5\u5236\u59d4\u54e1\u6703\u5be9\u67e5\u3002(p.14 [\"g\",102,58,1,14,14])","progress":"\u4e00\u8b80","topic":null,"category":null,"tags":null,"bill_refs":"{1150L15359}","memo":null,"agencies":null,"speakers":null}]}
<?php
        exit;
    }

    public function legislatorAction()
    {
        list(, /*api*/, /*legislator*/, $term) = explode('/', $this->getURI());
        $records = array();
        $parseConstuiency = function($str) {
            // TODO: 桃園市第五選區 => ["TAO",5]
            return array();
        };

        foreach (Legislator::search(1) as $legislator) {
            if ($term and $term != $legislator->term) {
                continue;
            }
            $data = json_decode($legislator->data);
            $records[] = array(
                'name' => $legislator->name,
                'party' => $data->party,
                'caucus' => $data->partyGroup,
                'constuiency' => $parseConstuiency($data->areaName),
            );
        }

        return $this->json($records);
    }

    public function sittingAction()
    {
        list(, /*api*/, /*sitting*/, $term) = explode('/', $this->getURI());
        $ret = array();
        foreach (Meeting::search(array('term' => intval($term)))->order('meeting_id DESC') as $meeting) {
            $data = $meeting->toArray();
            $data['data'] = json_decode($data['data']);
            $data['dates'] = json_decode($data['dates']);
            $data['name'] = $meeting->getName();
            $ret[] = $data;
        }
        return $this->json($ret);
    }
}
