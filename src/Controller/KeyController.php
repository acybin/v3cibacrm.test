<?php

namespace App\Controller;

use App\Repository\KeyRepo;
use App\Repository\DefaultRepo;
use App\Twig\Template;
use framework\ajax;
use framework\enum;
use framework\pdo;
use framework\tools;
use PDOException;

class KeyController extends ajax\ajax {
    
    private $defaultRepoInstance;
    private $keyRepoInstance;
    
    
    public function __construct() {
        parent::__construct('Controller', 'KeyController');
    }
    
    
    private function getDefaultRepo() {
        if (!$this->defaultRepoInstance) {
            $this->defaultRepoInstance = new DefaultRepo();
        }
        return $this->defaultRepoInstance;
    }
    
    
    private function getKeyRepo() {
        if (!$this->keyRepoInstance) {
            $this->keyRepoInstance = new KeyRepo();
        }
        return $this->keyRepoInstance;
    }
    
    /**
     * @access [5]
     */
    protected function _showKeys($args = []) {       
        $template_data = [];
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showKeys');
        
        pdo::clearPdo();
        $sql = "SELECT COUNT(*) AS count, SUM(action) AS action, file_name FROM key_robots GROUP BY file_name";
        $counts = pdo::getCiba2Pdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);       
        
        foreach ($counts as $count) {
            if ($count['count'] == $count['action'])   
                $template_data['files'][] = $count['file_name'];
            else
                $template_data['not_ready'][] = ['file_name' => $count['file_name'], 'procent' => round($count['action'] / $count['count'] * 100)];
        }
        
        $sql = "SELECT COUNT(*) AS count, SUM(action) AS action, file_name FROM key_files GROUP BY file_name";
        $counts = pdo::getCiba2Pdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($counts as $count) {
            if ($count['count'] == $count['action'])
                $template_data['key_files_ready'][] = ['file_name' => $count['file_name']];  
            else 
                $template_data['key_files'][] = ['file_name' => $count['file_name'], 'procent' => round($count['action'] / $count['count'] * 100)];
        }
        
        $keys_collections = new enum();
        $keys_collections->addItems(
            Template::getTemplate()->render('Keys/keys.html.twig', $template_data)
        );
        $this->getWrapper()->setChildren([$keys_collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _refreshKeys($args = []) {
        if ($args['s_mode']) {
            $keys = $this->getKeyRepo()->getKeys($args);
            $this->getWrapper()->setChildren([$keys]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _downloadKeys($args = []) {
        $keys = $this->getKeyRepo()->downloadKeys($args);
        $this->getWrapper()->setChildren([$keys]);
    }
    
    /**
     * @access [5]
     */
    protected function _parseStr($args = []) {
        $str = $args['str'] ?? [];
        $keys = $this->getKeyRepo()->parseStr($str);
        $this->getWrapper()->setChildren([$keys]);
    }
    
    /**
     * @access [5]
     */
    protected function _uploadFile($args = []) {
        $keys = $this->getKeyRepo()->uploadFile($args);
        $this->getWrapper()->setChildren([$keys]);
    }
    
    /**
     * @access [5]
     */
    protected function _addNewKey() {
        $answer = $this->getKeyRepo()->addNewKey();
        $this->getWrapper()->setChildren([$answer]);
    }
    
    /**
     * @access [5]
     */
    protected function _showName($args = []) {
        $key_name = $args['key_name'] ?? false;
        if (is_string($key_name)) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showName');
            $template_data['key_name'] = $key_name;
            $keys_collections = new enum();
            $keys_collections->addItems(
                Template::getTemplate()->render('Keys/keys_name.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$keys_collections]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _saveName($args = []) {
        $key_id = $args['key_id'] ?? false;
        $key_name = $args['key_name'] ?? false;  
        if (is_numeric($key_id) && is_string($key_name)) {
            $answer = $this->getKeyRepo()->saveName(['id' => $key_id, 'name' => $key_name]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _showMarker($args = []) {
        $key_marker = $args['key_marker'] ?? false;
        if (is_string($key_marker)) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showMarker');
            $template_data['key_marker'] = $key_marker;
            $keys_collections = new enum();
            $keys_collections->addItems(
                Template::getTemplate()->render('Keys/key_marker.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$keys_collections]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _saveMarker($args = []) {
        $key_id = $args['key_id'] ?? false;
        $key_marker = $args['key_marker'] ?? false;  
        if (is_numeric($key_id) && is_string($key_marker)) {
            $answer = $this->getKeyRepo()->saveMarker(['id' => $key_id, 'marker' => $key_marker]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
        /**
     * @access [5]
     */
    protected function _showKeysTags($args = []) {
        $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showKeysTags');
        $keys_tags_collections = new enum();
        $keys_tags_collections->addItems(
            Template::getTemplate()->render('Keys/keys_tags.html.twig')
        );
        $this->getWrapper()->setChildren([$keys_tags_collections]);
    }
    
    /**
     * @access [5]
     */
    protected function _selectKeysTags($args = []) {
        $q = $args['q'] ?? '';
        $page_limit = $args['page_limit'] ?? 10;
        $page = $args['page'] ?? 1;
        $keys_data = [
            'q' => $q,
            'page_limit' => $page_limit,
            'page' => $page
        ];
        $answer = $this->getKeyRepo()->selectKeysTags($keys_data);
        $this->getWrapper()->setChildren([$answer]);
    }
    
    /**
     * @access [5]
     */
    protected function _getKeysTags($args = []) {
        $answer = $this->getKeyRepo()->getKeysTags($args);
        $this->getWrapper()->setChildren([$answer]);        
    }
    
    /**
     * @access [5]
     */
    protected function _saveKeysTags($args = []) {
        $key_id = $args['key_id'] ?? false;
        $key_tags = $args['tags'] ?? false;
        if (is_numeric($key_id)) {
            $answer = $this->getKeyRepo()->saveKeysTags(['id' => $key_id, 'tags' => $key_tags]);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _deleteKey($args) {
        $key_id = isset($args['key_id']) ? (int) $args['key_id'] : false;
        if (is_numeric($key_id)) {           
            $answer = $this->getKeyRepo()->deleteKey($key_id); 
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _enableKey($args) {
        $key_id = isset($args['key_id']) ? (int) $args['key_id'] : false;
        if (is_numeric($key_id)) {           
            $answer = $this->getKeyRepo()->enableKey($key_id);
            $this->getWrapper()->setChildren([$answer]);
        }
    }
    
    /**
     * @access [5]
     */
    protected function _showCampaigns($args = []) {
        if (is_numeric($args['key_id'])) {
            $this->getWrapper()->getAttributes()->addAttr('data-mode', 'showCampaigns');
            $campaigns = $this->getKeyRepo()->getCampaigns($args['key_id']);
            $template_data['campaigns'] = $campaigns;
            
            $keys_collections = new enum();
            $keys_collections->addItems(
                Template::getTemplate()->render('Keys/keys_campaigns.html.twig', $template_data)
            );
            $this->getWrapper()->setChildren([$keys_collections]);
        }
    }
    
}