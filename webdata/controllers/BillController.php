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
}
