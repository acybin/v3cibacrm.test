<?php


namespace App\Controller;

use App\Repository\DashboardRepo;
use App\Repository\DefaultRepo;
use App\Twig\Template;
use framework\ajax;
use framework\enum;
use framework\pdo;
use framework\tools;

class DashboardController extends ajax\ajax {

    private $defaultRepoInstance;
    private $dashboardRepoInstance;    
    
    public function __construct() {
        parent::__construct('Controller', 'DashboardController');
    }
    
    
    private function getDefaultRepo() {
        if (!$this->defaultRepoInstance) {
            $this->defaultRepoInstance = new DefaultRepo();
        }
        return $this->defaultRepoInstance;
    }
    
    
    private function getDashboardRepo() {
        if (!$this->dashboardRepoInstance) {
            $this->dashboardRepoInstance = new DashboardRepo();
        }
        return $this->dashboardRepoInstance;
    }
    
    /**
     * @access [5]
     */
    protected function _refreshSetkas($args = []) {
        
        $datepicker = array_key_exists('datepicker', $args) ? $args['datepicker'] : false;
        $channels = array_key_exists('channels_filter', $args) ? $args['channels_filter'] : [];
        $interval_value = array_key_exists('interval_value', $args) ? $args['interval_value'] : 1;
        
        $order_dir = array_key_exists('order_dir', $args) ? $args['order_dir'] : 1;
        $order_column = array_key_exists('order_column', $args) ? $args['order_column'] : 1;
        $procent = array_key_exists('procent', $args) ? $args['procent'] : 0;
        
        $datas = $this->getDashboardRepo()->getSetkas($datepicker, $channels, true, $interval_value, $order_dir, $order_column, $procent);
        $template_data['datas'] = $datas;
              
        $collections = new enum();
        $collections->addItems(
            Template::getTemplate()->render('Dashboards/refresh_setkas.html.twig', $template_data)
        );
        
        $this->getWrapper()->setChildren([$collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _showSetkas($args = [])
    {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showSetkas');
        
        $datepicker = new FilterController();
        $datepicker->showDatepicker(['datepicker' => ['start' => date('d.m.Y', strtotime(date('d.m.Y')." -30 days")), 
                                                        'end' => date('d.m.Y', strtotime(date('d.m.Y')." -1 days"))]]);
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
        
        $collections = new enum();
        $collections->addItems(
            Template::getTemplate()->render('Dashboards/setkas.html.twig', $template_data)
        );
        
        $this->getWrapper()->setChildren([$collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _showDaily($args = [])
    {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDaily');
        $collections = new enum();
        $collections->addItems(
            Template::getTemplate()->render('Dashboards/daily.html.twig')
        );
        
        $this->getWrapper()->setChildren([$collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _refreshDaily($args = []) {
        
        $type = array_key_exists('type', $args) ? $args['type'] : 0;
        $clear = array_key_exists('clear', $args) ? $args['clear'] : 0;

        $datas = $this->getDashboardRepo()->getDaily($type, $clear);
        $template_data['datas'] = $datas;
              
        $collections = new enum();
        $collections->addItems(
            Template::getTemplate()->render('Dashboards/refresh_daily.html.twig', $template_data)
        );
        
        $this->getWrapper()->setChildren([$collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _showOffers($args = [])
    {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showOffers');
        
        $filter_controller = new FilterController();
        $filters = "SELECT DISTINCT datatable FROM filters WHERE page = 'dash_offers'";
        $filters = pdo::getPDO()->prepare($filters);
        $filters->execute(array());
        $filters = $filters->fetchAll(\PDO::FETCH_COLUMN, 0);
        $filters_arr = [];
        foreach ($filters as $filter) {
            $filters_arr[$filter] = [];
        }
        
        pdo::clearPdo();
        
        $filter_controller->showFilters(['filter_types' => $filters, 'filters' => $filters_arr, 'filter_title' => 'Поиск:', 'page' => 'dash_offers']);
        $template_data['filters'] = $filter_controller;  
        
        $collections = new enum();
        $collections->addItems(
            Template::getTemplate()->render('Dashboards/offers.html.twig', $template_data)
        );
        
        $this->getWrapper()->setChildren([$collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _refreshOffers($args = []) {
        
        $filters = array_key_exists('filters', $args) ? $args['filters'] : [];  
        $parent = array_key_exists('parent', $args) ? $args['parent'] : 0;
        
        $no_lid = array_key_exists('no_lid', $args) ? $args['no_lid'] : 0;
        $no_partner = array_key_exists('no_partner', $args) ? $args['no_partner'] : 0;
                 
        $datas = $this->getDashboardRepo()->getOffers($filters, $parent, $no_lid, $no_partner);
        $template_data['datas'] = $datas;
        
        $collections = new enum();
        $collections->addItems(
            Template::getTemplate()->render('Dashboards/refresh_offers.html.twig', $template_data)
        );
        
        $this->getWrapper()->setChildren([$collections]);
    }
    
}