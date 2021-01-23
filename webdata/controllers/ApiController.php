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

        if ($billdata->{'立法種類'} == '修正條文') {
            $law = new StdClass;
            $law->type = 'lawproposal';
            $law->name = $billdata->{'對照表標題'};
            $law->header = array('修正條文', '現行條文', '說明');
            $law->content = array();

            foreach ($billdata->{'修正記錄'} as $line) {
                $law->content[] = array(
                    $line->{'修正條文'},
                    $line->{'現行條文'},
                    $line->{'說明'},
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
                    $line->{'條文'},
                    $line->{'說明'},
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
            $str = str_replace('陳　瑩', '陳瑩', $str);
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
}
