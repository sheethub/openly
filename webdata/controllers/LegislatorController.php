<?php

class LegislatorController extends Pix_Controller
{
    public function indexAction()
    {
        $term = Legislator::search(1)->max('term')->term;
        return $this->redirect('/legislator/term/' . $term);
    }

    public function termAction()
    {
        list(, /*legislator*/, /*term*/, $term) = explode('/', $this->getURI());
        $term = intval($term);
        $this->view->term = $term;
        $this->view->max_term = Legislator::search(1)->max('term')->term;
    }
}
