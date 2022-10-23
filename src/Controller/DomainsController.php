<?php

namespace App\Controller;

use App\Repository\DomainsRepo;
use App\Repository\DefaultRepo;
use App\Twig\Template;

use framework\ajax;
use framework\enum;
use framework\pdo;
use framework\tools;

class DomainsController extends ajax\ajax {
    private $defaultRepoInstance;
    private $domainsRepoInstance;
    
    
    public function __construct() {
        parent::__construct('Controller', 'DomainsController');
    }
    
    
    private function getDefaultRepo() {
        if (!$this->defaultRepoInstance) {
            $this->defaultRepoInstance = new DefaultRepo();
        }
        return $this->defaultRepoInstance;
    }
    
    
    private function getDomainsRepo() {
        if (!$this->domainsRepoInstance) {
            $this->domainsRepoInstance = new DomainsRepo();
        }
        return $this->domainsRepoInstance;
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomains($args = []) {
        $template_data = [];
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomains');
        
        $filter_controller = new FilterController();
        $filters = "SELECT DISTINCT datatable FROM filters WHERE page = 'domains'";
        $filters = pdo::getPDO()->prepare($filters);
        $filters->execute(array());
        $filters = $filters->fetchAll(\PDO::FETCH_COLUMN, 0);
        $filters_arr = [];
        foreach ($filters as $filter) {
            $filters_arr[$filter] = [];
        }
        
        pdo::clearPdo();
        $ranges = "SELECT MIN(expired) AS start, MAX(expired) AS end FROM domains WHERE domains.no_active = 0";
        $ranges = pdo::getCiba2Pdo()->prepare($ranges);
        $ranges->execute(array());
        $ranges = $ranges->fetch(\PDO::FETCH_ASSOC);  
        pdo::clearPdo();
        $datepicker = new FilterController();
        $datepicker->showDatepicker(['datepicker' => ['start' => date('d.m.Y', strtotime($ranges['start'])), 'end' => date('d.m.Y', strtotime($ranges['end']))]]);
        $template_data['datepicker'] = $datepicker;
        $template_data['start'] = date('d.m.Y', strtotime($ranges['start']));
        $template_data['end'] = date('d.m.Y', strtotime($ranges['end']));

        $filter_controller->showFilters(['filter_types' => $filters, 'filters' => $filters_arr, 'filter_title' => 'Поиск:', 'page' => 'domains']);
        $template_data['filters'] = $filter_controller;
        
        $domains_collections = new enum();
        $domains_collections->addItems(
            Template::getTemplate()->render('Domains/domains.html.twig', $template_data)
        );
        $this->getWrapper()->setChildren([$domains_collections]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _refreshDomains($args = []) {
        if ($args['s_mode']) {
            $domains = $this->getDomainsRepo()->getDomains($args);
            $this->getWrapper()->setChildren([$domains]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsName($args = []) {
        $domain_name = $args['domain_name'] ?? false;
        if (is_string($domain_name)) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsName');
            $template_data['domain_name'] = $domain_name;
            $domains_name_collections = new enum();
            $domains_name_collections->addItems(
                Template::getTemplate()->render('Domains/domains_name.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$domains_name_collections]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsName($args = []) {
        $domain_id = $args['domain_id'] ?? false;
        $domain_name = $args['domain_name'] ?? false;  
        $old_domain = $args['old_domain'] ?? false;      
        if (is_numeric($domain_id) && is_string($domain_name) && is_string($old_domain)) {
            $answer = $this->getDomainsRepo()->saveDomainsName(['id' => $domain_id, 'name' => $domain_name, 'old_domain' => $old_domain]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsSetkas($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsSetkas');
        $domains_setkas_collections = new enum();
        $domains_setkas_collections->addItems(
            Template::getTemplate()->render('Domains/domains_setkas.html.twig')
        );
        $this->getWrapper()->setChildren([$domains_setkas_collections]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectDomainsSetkas($args = []) {
        $q = $args['q'] ?? '';
        $page_limit = $args['page_limit'] ?? 10;
        $page = $args['page'] ?? 1;
        $domains_data = [
            'q' => $q,
            'page_limit' => $page_limit,
            'page' => $page
        ];
        $answer = $this->getDomainsRepo()->selectDomainsSetkas($domains_data);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectCurrentSetkas($args = []) {
        $answer = $this->getDomainsRepo()->selectCurrentSetkas($args);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsSetkas($args) {
        $domain_id = $args['domain_id'] ?? false;
        $setka_id = $args['setka_id'] ?? false;
        if (is_numeric($domain_id) && is_numeric($setka_id)) {
            $answer = $this->getDomainsRepo()->saveDomainsSetkas(['id' => $domain_id, 'setka_id' => $setka_id]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsExpired($args = []) {
        $expired = $args['expired'] ?? false;
        if (is_string($expired)) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsExpired');
            $template_data['expired'] = $expired;
            $domains_expired_collections = new enum();
            $domains_expired_collections->addItems(
                Template::getTemplate()->render('Domains/domains_expired.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$domains_expired_collections]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsExpired($args = []) {
        $domain_id = $args['domain_id'] ?? false;
        $domain_expired = $args['domain_expired'] ?? false;
        if (is_numeric($domain_id) && is_string($domain_expired)) {
            $answer = $this->getDomainsRepo()->saveDomainsExpired(['id' => $domain_id, 'expired' => $domain_expired]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsNoActive($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsNoActive');
        $domains_no_active_collections = new enum();
        $domains_no_active_collections->addItems(
            Template::getTemplate()->render('Domains/domains_no_active.html.twig')
        );
        $this->getWrapper()->setChildren([$domains_no_active_collections]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectDomainsNoActive($args = []) {
        $q = $args['q'] ?? '';
        $page_limit = $args['page_limit'] ?? 10;
        $page = $args['page'] ?? 1;
        $domains_data = [
            'q' => $q,
            'page_limit' => $page_limit,
            'page' => $page
        ];
        $answer = $this->getDomainsRepo()->selectDomainsNoActive($domains_data);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectCurrentNoActive($args = []) {
        $answer = $this->getDomainsRepo()->selectCurrentNoActive($args);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsNoActive($args) {
        $domain_id = $args['domain_id'] ?? false;
        $no_active = $args['no_active'] ?? false;
        if (is_numeric($domain_id) && is_numeric($no_active)) {
            $answer = $this->getDomainsRepo()->saveDomainsNoActive(['id' => $domain_id, 'no_active' => $no_active]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsServer($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsServer');
        $domains_server_collections = new enum();
        $domains_server_collections->addItems(
            Template::getTemplate()->render('Domains/domains_server.html.twig')
            );
        $this->getWrapper()->setChildren([$domains_server_collections]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectDomainsServer($args = []) {
        $q = $args['q'] ?? '';
        $page_limit = $args['page_limit'] ?? 10;
        $page = $args['page'] ?? 1;
        $domains_data = [
            'q' => $q,
            'page_limit' => $page_limit,
            'page' => $page
        ];
        $answer = $this->getDomainsRepo()->selectDomainsServer($domains_data);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectCurrentServer($args = []) {
        $answer = $this->getDomainsRepo()->selectCurrentServer($args);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsServer($args) {
        $domain_id = $args['domain_id'] ?? false;
        $server_id = $args['server_id'] ?? false;
        if (is_numeric($domain_id) && is_numeric($server_id)) {
            $answer = $this->getDomainsRepo()->saveDomainsServer(['id' => $domain_id, 'server_id' => $server_id]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsMirror($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsMirror');
        $domains_mirror_collections = new enum();
        $domains_mirror_collections->addItems(
            Template::getTemplate()->render('Domains/domains_mirror.html.twig')
            );
        $this->getWrapper()->setChildren([$domains_mirror_collections]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectDomainsMirror($args = []) {
        $q = $args['q'] ?? '';
        $page_limit = $args['page_limit'] ?? 10;
        $page = $args['page'] ?? 1;
        $domains_data = [
            'q' => $q,
            'page_limit' => $page_limit,
            'page' => $page
        ];
        $answer = $this->getDomainsRepo()->selectDomainsMirror($domains_data);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectCurrentMirror($args = []) {
        $answer = $this->getDomainsRepo()->selectCurrentMirror($args);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsMirror($args) {
        $domain_id = $args['domain_id'] ?? false;
        $mirror_id = $args['mirror_id'] ?? false;
        if (is_numeric($domain_id) && is_numeric($mirror_id)) {
            $answer = $this->getDomainsRepo()->saveDomainsMirror(['id' => $domain_id, 'mirror_id' => $mirror_id]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsOwner($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsOwner');
        $domains_owner_collections = new enum();
        $domains_owner_collections->addItems(
            Template::getTemplate()->render('Domains/domains_owner.html.twig')
            );
        $this->getWrapper()->setChildren([$domains_owner_collections]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectDomainsOwner($args = []) {
        $q = $args['q'] ?? '';
        $page_limit = $args['page_limit'] ?? 10;
        $page = $args['page'] ?? 1;
        $domains_data = [
            'q' => $q,
            'page_limit' => $page_limit,
            'page' => $page
        ];
        $answer = $this->getDomainsRepo()->selectDomainsOwner($domains_data);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _selectCurrentOwner($args = []) {
        $answer = $this->getDomainsRepo()->selectCurrentOwner($args);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsOwner($args) {
        $domain_id = $args['domain_id'] ?? false;
        $owner_id = $args['owner_id'] ?? false;
        if (is_numeric($domain_id) && is_numeric($owner_id)) {
            $answer = $this->getDomainsRepo()->saveDomainsOwner(['id' => $domain_id, 'owner_id' => $owner_id]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsPurchased($args = []) {
        $purchased = $args['purchased'] ?? false;
        if (is_string($purchased)) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsPurchased');
            $template_data['purchased'] = $purchased;
            $domains_purchased_collections = new enum();
            $domains_purchased_collections->addItems(
                Template::getTemplate()->render('Domains/domains_purchased.html.twig', $template_data)
                );
            $this->getWrapper()->setChildren([$domains_purchased_collections]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsPurchased($args = []) {
        $domain_id = $args['domain_id'] ?? false;
        $domains_purchased = $args['domain_purchased'] ?? false;
        if (is_numeric($domain_id) && is_string($domains_purchased)) {
            $answer = $this->getDomainsRepo()->saveDomainsPurchased(['id' => $domain_id, 'purchased' => $domains_purchased]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainsCost($args = []) {
        $domain_cost = $args['domain_cost'] ?? false;
        if (is_string($domain_cost)) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsCost');
            $template_data['domain_cost'] = $domain_cost;
            $domains_cost_collections = new enum();
            $domains_cost_collections->addItems(
                Template::getTemplate()->render('Domains/domains_cost.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$domains_cost_collections]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsCost($args = []) {
        $domain_id = $args['domain_id'] ?? false;
        $domain_cost = $args['domain_cost'] ?? false;
        if (is_numeric($domain_id) && is_numeric($domain_cost)) {
            $answer = $this->getDomainsRepo()->saveDomainsCost(['id' => $domain_id, 'cost' => $domain_cost]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _addNewDomain() {
        $answer = $this->getDomainsRepo()->addNewDomain();
        $this->getWrapper()->setChildren([$answer]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _deleteDomain($args) {
        $domain_name = $args['domain_name'] ?? false;
        if (is_string($domain_name)) {
            $answer = $this->getDomainsRepo()->deleteDomain($domain_name);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _enableDomain($args) {
        $domain_name = $args['domain_name'] ?? false;
        if (is_string($domain_name)) {
            $answer = $this->getDomainsRepo()->enableDomain($domain_name);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _showDomainSites($args = []) {
        $template_data = [];
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainSites');        
        $domains_collections = new enum();
        $domains_collections->addItems(
            Template::getTemplate()->render('Domains/domains_sites.html.twig', $template_data)
        );
        $this->getWrapper()->setChildren([$domains_collections]);
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _refreshDomainSites($args = []) {
        if ($args['s_mode']) {
            $domains = $this->getDomainsRepo()->getDomainSites($args);
            $this->getWrapper()->setChildren([$domains]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _showDomainsComment($args = []) {
        $domain_comment = $args['domain_comment'] ?? false;
        if (is_string($domain_comment)) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showDomainsComment');
            $template_data['domain_comment'] = $domain_comment;
            $domains_comment_collections = new enum();
            $domains_comment_collections->addItems(
                Template::getTemplate()->render('Domains/domains_comment.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$domains_comment_collections]);
        }
    }
    
    
    
    /**
     * @access [5]
     */
    protected function _saveDomainsComment($args = []) {
        $domain_id = $args['domain_id'] ?? false;
        $domain_comment = $args['domain_comment'] ?? false;  
        if (is_numeric($domain_id) && is_string($domain_comment)) {
            $answer = $this->getDomainsRepo()->saveDomainsComment(['id' => $domain_id, 'comment' => $domain_comment]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _ShowModalDomain($args = []) {   
        $template_data = [];
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'ShowModalDomain');
        
        pdo::clearPdo();
        
        $time = tools::get_time();
        $template_data['expired'] = date('d.m.Y', strtotime('+1 year', $time));
        $template_data['purchased'] = date('d.m.Y', $time);
        
        $setkas = "SELECT id, syn AS name FROM setkas WHERE (no_active IS NULL OR no_active = 0) ORDER BY FIELD(syn, 'Без сетки', syn), syn ASC";
        $setkas = pdo::getCiba2Pdo()->prepare($setkas);
        $setkas->execute(array());
        $setkas = $setkas->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($setkas as $setka) {
            $template_data['setkas'][$setka['id']] = $setka['name'];     
        }
        
        $modal_collections = new enum();
        $modal_collections->addItems(
            Template::getTemplate()->render('Domains/domains_modal.html.twig', $template_data)
        );
        $this->getWrapper()->setChildren([$modal_collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _saveDomain($args) {
        $answer = $this->getDomainsRepo()->saveDomain($args);
        if (!empty($answer['code'])) {
            $this->setCode($answer['code']);
            $this->getWrapper()->setChildren([$answer['answer']]);
        }
        else {
            $this->getWrapper()->setChildren([$answer]);
        }
    }
}