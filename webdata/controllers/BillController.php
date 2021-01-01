<?php

class BillController extends Pix_Controller
{
    public function showAction()
    {
        list(, /*bill*/, /*show*/, $id) = explode('/', $this->getURI());
        if (!$bill = Bill::find(strval($id))) {
            return $this->notfound();
        }
        $this->view->bill = $bill;
    }

    public function compareAction()
    {
        list(, /*bill*/, /*compare*/, $id) = explode('/', $this->getURI());
        if (!$bill = Bill::find(strval($id))) {
            return $this->notfound();
        }
        $this->view->bill = $bill;
    }

    public function historyAction()
    {
        list(, /*bill*/, /*history*/, $id) = explode('/', $this->getURI());
        if (!$bill = Bill::find(strval($id))) {
            return $this->notfound();
        }
        $this->view->bill = $bill;
    }

    public function docAction()
    {
        list(, /*bill*/, /*doc*/, $billno) = explode('/', $this->getURI());
        if (!$bill = Bill::find(strval($billno))) {
            return $this->notfound();
        }
        $this->view->bill = $bill;
        $this->view->objs = array();
        if (json_decode($bill->data)->doc_bak_url) {
            foreach (json_decode($bill->data)->doc_bak_url as $file) {
                $this->view->objs = array_merge($this->view->objs, array(json_decode(gzdecode('' . S3Lib::get("data/bill-json/{$file}.gz")))));
            }
        }

        if ($_GET['img']) {
            foreach ($this->view->objs as $idx => $obj) {
                foreach ($obj->attachments as $attachment) {
                    if ($idx == $_GET['idx'] and $attachment->file_name == $_GET['img']) {
                        $ext = pathinfo($attachment->file_name, PATHINFO_EXTENSION);
                        header('Content-Type: image/' . $ext);
                        echo base64_decode($attachment->content);
                        exit;
                    }
                }
                echo "not found";
                exit;
            }
        }
    }
}
