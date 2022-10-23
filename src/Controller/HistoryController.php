<?php

namespace App\Controller;

use App\Repository\HistoryRepo;
use App\Repository\DefaultRepo;
use App\Twig\Template;
use framework\ajax;
use framework\enum;
use framework\pdo;


class HistoryController extends ajax\ajax {
    private $defaultRepoInstance;
    private $HistoryRepoInstance;


    public function __construct() {
        parent::__construct('Controller', 'HistoryController');
    }


    private function getDefaultRepo() {
        if (!$this->defaultRepoInstance) {
            $this->defaultRepoInstance = new DefaultRepo();
        }
        return $this->defaultRepoInstance;
    }


    private function getHistoryRepo() {
        if (!$this->HistoryRepoInstance) {
            $this->HistoryRepoInstance = new HistoryRepo();
        }
        return $this->HistoryRepoInstance;
    }


    /**
     * @access [5]
     */
    protected function _setHistoryDatabase() {
        $answer = $this->getHistoryRepo()->setHistoryUserDatabase($args);
        $this->getWrapper()->setChildren([$answer]);
    }


    /**
     * @access [5]
     */
    protected function _showHistory($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showHistory');

        $filter_controller = new FilterController();
        $filters = "SELECT DISTINCT datatable FROM filters WHERE page = 'history'";
        $filters = pdo::getPDO()->prepare($filters);
        $filters->execute(array());
        $filters = $filters->fetchAll(\PDO::FETCH_COLUMN, 0);
        $filters_arr = [];
        foreach ($filters as $filter) {
            $filters_arr[$filter] = [];
        }

        $filter_controller->showFilters(['filter_types' => $filters, 'filters' => $filters_arr, 'filter_title' => 'Поиск:', 'page' => 'history']);
        $template_data['filters'] = $filter_controller;

        $datepicker = new FilterController();
        $datepicker->showDatepicker(['datepicker' => ['start' => date('d.m.Y', strtotime(date('d.m.Y')." -6 days")), 'end' => date('d.m.Y')]]);
        $template_data['datepicker'] = $datepicker;       
        
        $history_temp = $this->getHistoryRepo()->setHistoryTemp();
        $template_data['history_temp'] = $history_temp;

        $history_collections = new enum();
        $history_collections->addItems(
            Template::getTemplate()->render('History/history.html.twig', $template_data)
        );
        $this->getWrapper()->setChildren([$history_collections]);        
    }


    /**
     * @access [5]
     */
    protected function _refreshHistory($args = []) {
        if ($args['s_mode']) {
            $history = $this->getHistoryRepo()->getHistoryData($args);
            $this->getWrapper()->setChildren([$history]);
        }              
    }

    
}