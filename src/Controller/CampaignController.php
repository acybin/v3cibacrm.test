<?php

namespace App\Controller;

use App\Repository\CampaignRepo;
use App\Repository\DefaultRepo;
use App\Twig\Template;
use framework\ajax;
use framework\enum;
use framework\pdo;
use framework\tools;


class CampaignController extends ajax\ajax {
    
    private $defaultRepoInstance;
    private $campaignRepoInstance;
    
    
    public function __construct() {
        parent::__construct('Controller', 'CampaignController');
    }
    
    
    private function getDefaultRepo() {
        if (!$this->defaultRepoInstance) {
            $this->defaultRepoInstance = new DefaultRepo();
        }
        return $this->defaultRepoInstance;
    }
    
    
    private function getCampaignRepo() {
        if (!$this->campaignRepoInstance) {
            $this->campaignRepoInstance = new CampaignRepo();
        }
        return $this->campaignRepoInstance;
    }
    
    /**
     * @access [5]
     */
    protected function _showCampaigns($args = []) {       
        $template_data = [];
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showCampaigns'); 
        
        $filter_controller = new FilterController();
        $filters = "SELECT DISTINCT datatable FROM filters WHERE page = 'campaign'";
        $filters = pdo::getPDO()->prepare($filters);
        $filters->execute(array());
        $filters = $filters->fetchAll(\PDO::FETCH_COLUMN, 0);
        $filters_arr = [];
        foreach ($filters as $filter) {
            $filters_arr[$filter] = [];
        }
        
        pdo::clearPdo();
        
        $filter_controller->showFilters(['filter_types' => $filters, 'filters' => $filters_arr, 'filter_title' => 'Поиск:', 'page' => 'campaign']);
        $template_data['filters'] = $filter_controller;       
      
        $campaigns_collections = new enum();
        $campaigns_collections->addItems(
            Template::getTemplate()->render('Campaigns/campaigns.html.twig', $template_data)
        );
        $this->getWrapper()->setChildren([$campaigns_collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _refreshCampaigns($args = []) {
        if ($args['s_mode']) {
            $campaigns = $this->getCampaignRepo()->getCampaigns($args);
            $this->getWrapper()->setChildren([$campaigns]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _lidRequests($args = []) {       
        $campaigns = $this->getCampaignRepo()->getLidRequests($args);
        $this->getWrapper()->setChildren([$campaigns]);
    }
    
    /**
     * @access [5]
     */
    protected function _showCampaignsTags($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showCampaignsTags');
        $campaigns_tags_collections = new enum();
        $campaigns_tags_collections->addItems(
            Template::getTemplate()->render('Campaigns/campaigns_tags.html.twig')
        );
        $this->getWrapper()->setChildren([$campaigns_tags_collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _selectCampaignsTags($args = []) {
        $q = $args['q'] ?? '';
        $page_limit = $args['page_limit'] ?? 10;
        $page = $args['page'] ?? 1;
        $campaigns_data = [
            'q' => $q,
            'page_limit' => $page_limit,
            'page' => $page
        ];
        $answer = $this->getCampaignRepo()->selectCampaignsTags($campaigns_data);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    /**
     * @access [5]
     */
    protected function _getCampaignsTags($args = []) {
        $answer = $this->getCampaignRepo()->getCampaignsTags($args);
        $this->getWrapper()->setChildren([$answer]);        
    }
    
    /**
     * @access [5]
     */
    protected function _saveCampaignsTags($args = []) {
        $campaign_id = $args['campaign_id'] ?? false;
        $suffics = $args['suffics'] ?? 0;
        $campaign_tags = $args['tags'] ?? false;
        if (is_numeric($campaign_id)) {
            $answer = $this->getCampaignRepo()->saveCampaignsTags(['id' => $campaign_id, 'suffics' => $suffics, 'tags' => $campaign_tags]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _showSourceName($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showSourceName');
        $campaigns_collections = new enum();
        $campaigns_collections->addItems(
            Template::getTemplate()->render('Campaigns/campaigns_source_name.html.twig')
            );
        $this->getWrapper()->setChildren([$campaigns_collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _selectSourceName($args = []) {
        $q = $args['q'] ?? '';
        $page_limit = $args['page_limit'] ?? 10;
        $page = $args['page'] ?? 1;
        $source_data = [
            'q' => $q,
            'page_limit' => $page_limit,
            'page' => $page
        ];
        $answer = $this->getCampaignRepo()->selectSourceName($source_data);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    /**
     * @access [5]
     */
    protected function _selectCurrentSource($args = []) {
        $answer = $this->getCampaignRepo()->selectCurrentSource($args);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    /**
     * @access [5]
     */
    protected function _saveSourceName($args) {
        $campaign_id = $args['campaign_id'] ?? false;
        $suffics = $args['suffics'] ?? 0;
        $source_id = $args['source_id'] ?? false;
        if (is_numeric($campaign_id) && is_numeric($source_id)) {
            $answer = $this->getCampaignRepo()->saveSourceName(['id' => $campaign_id, 'source_id' => $source_id, 'suffics' => $suffics]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _showKeys($args = []) {
        if (is_numeric($args['campaign_id'])) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showKeys');
            $keys = $this->getCampaignRepo()->getKeys($args['campaign_id']);
            $template_data['keys'] = $keys;
            
            $campaigns_collections = new enum();
            $campaigns_collections->addItems(
                Template::getTemplate()->render('Campaigns/campaigns_keys.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$campaigns_collections]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _refreshKey() {
        exec("php ".\DOCUMENT_ROOT."admin/index.php op=start_stop args[mode]=refreshKey > /dev/null &", $output, $return_var);
        $answer = true;
        $this->getWrapper()->setChildren([$answer]);   
    }
    
}