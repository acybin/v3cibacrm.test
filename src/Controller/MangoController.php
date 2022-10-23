<?php

namespace App\Controller;

use App\Repository\MangoRepo;
use App\Repository\DefaultRepo;
use App\Twig\Template;
use framework\ajax;
use framework\enum;
use framework\pdo;
use framework\tools;


class MangoController extends ajax\ajax {
    
    private $defaultRepoInstance;
    private $mangoRepoInstance;
    
    
    public function __construct() {
        parent::__construct('Controller', 'MangoController');
    }
    
    
    private function getDefaultRepo() {
        if (!$this->defaultRepoInstance) {
            $this->defaultRepoInstance = new DefaultRepo();
        }
        return $this->defaultRepoInstance;
    }
    
    
    private function getMangoRepo() {
        if (!$this->mangoRepoInstance) {
            $this->mangoRepoInstance = new MangoRepo();
        }
        return $this->mangoRepoInstance;
    }
    
    /**
     * @access [5]
     */
    protected function _showMangos($args = []) {       
        $template_data = [];
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showMangos');
        
        $filter_controller = new FilterController();
        $filters = "SELECT DISTINCT datatable FROM filters WHERE page = 'mango'";
        $filters = pdo::getPDO()->prepare($filters);
        $filters->execute(array());
        $filters = $filters->fetchAll(\PDO::FETCH_COLUMN, 0);
        $filters_arr = [];
        foreach ($filters as $filter) {
            $filters_arr[$filter] = [];
        }
        
        $datepicker = new FilterController();
        $datepicker->showDatepicker(['datepicker' => ['start' => date('d.m.Y', strtotime(date('d.m.Y')." -29 days")), 'end' => date('d.m.Y')]]);
        $template_data['datepicker'] = $datepicker;
        
        pdo::clearPdo();
        $channel_filter = "SELECT `id`, `name` FROM channels WHERE (`no_active` IS NULL OR `no_active` = 0)";
        $channel_filter = pdo::getCiba2Pdo()->prepare($channel_filter);
        $channel_filter->execute(array());
        $channels = [];
        while ($row = $channel_filter->fetch(\PDO::FETCH_ASSOC)) {
            $channels[$row['id']] = $row['name'];
        }
        
        $channels[0] = 'Без канала';
        $template_data['channels'] = $channels;
        pdo::clearPdo();
        
        $filter_controller->showFilters(['filter_types' => $filters, 'filters' => $filters_arr, 'filter_title' => 'Поиск:', 'page' => 'mango']);
        $template_data['filters'] = $filter_controller;
        
        $mangos_collections = new enum();
        $mangos_collections->addItems(
            Template::getTemplate()->render('Mangos/mangos.html.twig', $template_data)
        );
        $this->getWrapper()->setChildren([$mangos_collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _refreshMangos($args = []) {
        if ($args['s_mode']) {
            $mangos = $this->getMangoRepo()->getMangos($args);
            $this->getWrapper()->setChildren([$mangos]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _ShowChart($args = []) {       
        if (is_numeric($args['mango_id'])) {

            $template_data = [];            
            $datepicker = array_key_exists('datepicker', $args) ? $args['datepicker'] : false;
            $tags = (array_key_exists('tags', $args) && $args['tags']) ? explode(',', $args['tags']) : [];
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'ShowChart');
            
            $charts = $this->getMangoRepo()->getChart($args['mango_id'], $datepicker, $tags);
            $template_data['charts'] = $charts;
            
            $mangos_collections = new enum();
            $mangos_collections->addItems(
                Template::getTemplate()->render('Mangos/mangos_chart.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$mangos_collections]);
        }
    }
    
}