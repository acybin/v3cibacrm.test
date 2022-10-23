<?php

namespace App\Repository;

use framework\pdo;
use framework\load;
use framework\tools;
use PDOException;



class DomainsRepo {
    private static $CIBA2;
    
    
    private static function setCIBA2() {
        $user_id = load::get_user_id();
        $sql = "
            SELECT
                id
            FROM
                user_access
            WHERE
                page = 'domains'
                AND type = 'database'
                AND value = 'ciba2'
                AND user_id = $user_id
        ";
        try {
            pdo::clearPdo();
            $stmt = pdo::getPDO()->prepare($sql);
            $stmt->execute(array());
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                self::$CIBA2 = true;
            }
            else {
                self::$CIBA2 = false;
            }
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
    }
    
    
    
    public static function setDomainsTemp() {
        $data = [];
        $user_id = load::get_user_id();
        
        //db button
        $sql = "
            SELECT
                id
            FROM
                user_access
            WHERE
                page = 'domains'
                AND type = 'button'
                AND value = 'database'
                AND user_id = $user_id
        ";
        try {
            $stmt = pdo::getPDO()->prepare($sql);
            $stmt->execute(array());
            $database = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($database) {
                $data['database'] = true;
            }
            else {
                $data['database'] = false;
            }
            $stmt = null;
            $database = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        //db button label
        $sql = "
            SELECT
                value
            FROM
                user_access
            WHERE
                page = 'domains'
                AND type = 'database'
                AND user_id = $user_id
        ";
        try {
            $stmt = pdo::getPDO()->prepare($sql);
            $stmt->execute(array());
            $domains_db = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($domains_db) {
                if ($domains_db['value'] === 'ciba2') {
                    $data['domains_db'] = 'ciba2';
                }
                else if ($domains_db['value'] === 'ciba3') {
                    $data['domains_db'] = 'ciba3';
                }
            }
            else {
                $data['domains_db'] = false;
            }
            $stmt = null;
            $domains_db = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        return count($data) != 0 ? $data : [];
    }
    
    
    
    public static function setDomainsUserDatabase() {
        $value = false;
        $user_id = load::get_user_id();
        
        $sql = "
            SELECT
                value
            FROM
                user_access
            WHERE
                page = 'domains'
                AND type = 'database'
                AND user_id = $user_id
        ";
        try {
            $stmt = pdo::getPDO()->prepare($sql);
            $stmt->execute(array());
            $domains_db = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($domains_db) {
                if ($domains_db['value'] === 'ciba2') {
                    $value = 'ciba3';
                }
                else if ($domains_db['value'] === 'ciba3') {
                    $value = 'ciba2';
                }
            }
            else {
                $value = false;
            }
            $stmt = null;
            $domains_db = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        if ($value != false) {
            $sql = "
                UPDATE
                    user_access
                SET
                    value = '$value'
                WHERE
                    page = 'domains'
                    AND type = 'database'
                    AND user_id = $user_id
            ";
            try {
                $stmt = pdo::getPDO()->prepare($sql);
                $stmt->execute(array());
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
        }
        
        return $value;
    }
    
    
    
    public static function getDomains(array $args = []) : array {
        //$draw = isset($args['draw']) ? $args['draw'] : false;
        $start = isset($args['start']) ? $args['start'] : 0;
        $rowperpage = isset($args['length']) ? $args['length'] : 10;
        $columnIndex = isset($args['order'][0]['column']) ? $args['order'][0]['column'] : -1;
        if ($columnIndex != -1) {
            $columnIndex = $columnIndex + 1;
        }
        else {
            $columnIndex = false;
        }
        $columnName = isset($args['columns'][$columnIndex]['data']) ? $args['columns'][$columnIndex]['data'] : false;
        $columnSortOrder = isset($args['order'][0]['dir']) ? $args['order'][0]['dir'] : false; // asc or desc
        
        $searchValue = isset($args['search']['value']) ? $args['search']['value'] : false;
        //$s_mode = isset($args['s_mode']) ? $args['s_mode'] : 0;
        
        $save = array_key_exists('save', $args) ? $args['save'] : false;
     
        $filters = array_key_exists('filters', $args) ? $args['filters'] : [];                
                
        $datepicker = array_key_exists('datepicker', $args) ? $args['datepicker'] : false;
        if ($datepicker) {
            $date_start = array_key_exists('start', $datepicker) ? $datepicker['start'] : false;
            $date_end = array_key_exists('end', $datepicker) ? $datepicker['end'] : false;
            
            $date_start = $date_start ? date("Y-m-d H:i:s", strtotime($date_start)) : date("Y-m-d 00:00:00");
            $date_end = $date_end ? date("Y-m-d H:i:s", strtotime($date_end)) : date("Y-m-d 23:59:59");
        }
        
        $status = $args['status'] ?? -1;
        $mirror = $args['mirror'] ?? -1;
        
        $no_site = $args['no_site'] ?? 0;
        $no_site = $no_site ? " AND (sites.count_sites IS NULL OR sites.count_sites = 0)" : "";
        
        $count_column = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                COUNT(domains.id) AS count_rows
        ";
           
        
        $select = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                domains.id AS id,
                domains.name AS name,
                IF(setkas.syn IS NULL, 'Без сетки', setkas.syn) AS setka,
                IF(DATE_FORMAT(domains.expired, '%d.%m.%Y') = '00.00.0000', '', DATE_FORMAT(domains.expired, '%d.%m.%Y')) AS expired,
                IF(domain_servers.name IS NULL, 'Не найден', domain_servers.name) AS server,  
                IF(sites.count_sites IS NULL, 0, sites.count_sites) AS domain_sites,
                domains.comment AS comment,       
                IF(domain_owners.name IS NULL, '', domain_owners.name) AS owner,                
                IF(DATE_FORMAT(domains.purchased, '%d.%m.%Y') = '00.00.0000', '', DATE_FORMAT(domains.purchased, '%d.%m.%Y')) AS purchased,                
                IF(domains.cost = 0, '', domains.cost) AS cost,
                '' AS delete_domain,
                domains.no_active AS no_active,
                domains.mirror AS mirror 
        ";
        
        
        $from = "
            FROM
                domains
            LEFT JOIN domain_servers ON
                domains.server_id = domain_servers.id
            LEFT JOIN setkas ON
                domains.setka_id = setkas.id
            LEFT JOIN domain_owners ON
                domains.domain_owner_id = domain_owners.id
            LEFT JOIN (
                SELECT
                    sites.domain_id,
                    COUNT(*) as count_sites
                FROM
                    sites
                WHERE
                    sites.domain_id IS NOT NULL
                    AND sites.domain_id != 0
                    AND (sites.no_active = 0 OR sites.no_active IS NULL)
                GROUP BY
                    sites.domain_id
            ) AS sites ON domains.id = sites.domain_id
        ";
                                
        
        $where = "
            WHERE
                1
                AND (domains.expired >= '$date_start' AND domains.expired <= '$date_end')
                {$no_site}
        "; 
        
        
        $filter = "";
        if ($searchValue) {
            $filter .= "
                AND (
                    domains.id LIKE '%{$searchValue}%'
                    OR domains.name LIKE '%{$searchValue}%'
                    OR domain_owners.name LIKE '%{$searchValue}%'
                    OR domains.comment LIKE '%{$searchValue}%'
                )
            ";
        }
        
        
        if (!empty($filters['setkas'])) {
            $ids = array_map('intval', $filters['setkas']);
            $filter .= " AND (domains.setka_id IN (" . implode(',', $ids) . ")) ";
            unset($filters['setkas']);
        }
        
        if (!empty($filters['domain_servers'])) {
            $ids = array_map('intval', $filters['domain_servers']);
            $filter .= " AND (domains.server_id IN (" . implode(',', $ids) . ")) ";
            unset($filters['domain_servers']);
        }
        
        if ($status != -1) {
            $filter .= " AND domains.no_active = {$status} ";
        }
        
        if ($mirror != -1) {
            $filter .= " AND domains.mirror = {$mirror} ";
        }
        
        
        $order = "";
        if ($columnName && $columnSortOrder) {
            if ($columnName != '') {
                if (!in_array($columnIndex, [4, 9])) {
                    $order .= "
                        ORDER BY
                            $columnIndex $columnSortOrder
                    ";
                }
                else {
                    $order .= "
                        ORDER BY
                            DATE(domains.expired) $columnSortOrder
                    ";
                }
            }
        }
        else {
            $order = "
                ORDER BY
                    1 ASC
            ";
        }
        
        
        $limit = "";
        if ($rowperpage) {
            $limit = "
                LIMIT
                    $start, $rowperpage
            ";
        }
        
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();
        
        $recordsTotal = "$count_column FROM domains";
        try {
            if (self::$CIBA2) {
                $recordsTotal = pdo::prepareChangeToCiba2($recordsTotal, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($recordsTotal);
            }
            else {
                $stmt = pdo::getPDO()->prepare($recordsTotal);
            }
            $stmt->execute(array());
            $recordsTotal = $stmt->fetch(\PDO::FETCH_ASSOC);
            $recordsTotal = $recordsTotal ? $recordsTotal['count_rows'] : false;
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        
        $recordsFiltered = "$count_column $from $where $filter";
        try {
            if (self::$CIBA2) {
                $recordsFiltered = pdo::prepareChangeToCiba2($recordsFiltered, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($recordsFiltered);
            }
            else {
                $stmt = pdo::getPDO()->prepare($recordsFiltered);
            }
            $stmt->execute(array());
            $recordsFiltered = $stmt->fetch(\PDO::FETCH_ASSOC);
            $recordsFiltered = $recordsFiltered ? $recordsFiltered['count_rows'] : false;
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        
        if ($save == '1') {
            $limit = "";
        }
        
        $data = [];
        $sql = "$select $from $where $filter $order $limit";
        //print($sql);
        try {
            if (self::$CIBA2) {
                $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($sql);
            }
            else {
                $stmt = pdo::getPDO()->prepare($sql);
            }
            $stmt->execute(array());
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        
        $datatable = [];
        $number = 0;
        if (count($data) != 0) {
            foreach ($data as $index => $row) {
                $fields = [];
                foreach ($row as $key => $cell) { 
                    $cell_value = $cell;
                    if ($save == '1') {
                        if ($cell_value == '0') {
                            //$cell_value = "$cell_value.0";
                        }
                    }
                    if ($key == 'name') {
                        if (tools::is_idn($cell_value)) {
                            $cell_value = idn_to_utf8($cell_value, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                        }
                    }
                    $datatable[$number][] = $cell_value;
                    $fields[] = $key;
                }
                
                $datatable[$number]['DT_RowData']['no_active'] = $row['no_active'];
                $datatable[$number]['DT_RowData']['mirror'] = (int) (bool) $row['mirror'];
                
                if ($save != '1') {
                    $datatable[$number]['DT_RowData']['fields'] = $fields;
                }
                
                $number++;
            }
        }        
        unset($data);        
        
        if ($save == '1') {            
            $header = ['id', 'Домен', 'Сетка', 'Истекает', 'Сервер', 'Сайты', 'Комментарий', 'Владелец', 'Куплен', 'Цена', 'Активный'];
            
            //$xlsx = tools::DownloadXlsx($datatable, $header);
            //return array("data" => $xlsx);
            
            $file_name = '/upload/csv/'.uniqid().'.csv';
            $main_file = \DOCUMENT_ROOT. $file_name;
            file_put_contents($main_file, iconv('utf-8', 'windows-1251', implode(';', $header).PHP_EOL), FILE_APPEND | LOCK_EX); 
             
            foreach ($datatable as $number => $row) {                
                unset($row[10], $row[11], $row[12]);                
                $mas_str = [];
                foreach ($row as $index => $cell) {
                    if (\is_numeric($index)) {
                        $mas_str[] = $cell;
                    }
                }
                //$mas_str[] = $row['DT_RowData']['mirror'];
                $mas_str[] = (integer) !$row['DT_RowData']['no_active'];
                file_put_contents($main_file, iconv('utf-8', 'windows-1251', implode(';', $mas_str).PHP_EOL), FILE_APPEND | LOCK_EX); 
            }
            return array("data" => $file_name);
        }
        else {
            return array(
                "draw" => $args['draw'] ? intval($args['draw']) : 0,
                "recordsTotal"    => $recordsTotal ? intval($recordsTotal) : 0,
                "recordsFiltered" => $recordsFiltered ? intval($recordsFiltered) : 0,
                "data"            => count($datatable) != 0 ? $datatable : []
            );
        }
    }



    public function saveDomainsName(array $domains_data) : string {
        $answer = $domains_data['old_domain'] ?? 'Новый домен';

        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $domain_name = $domains_data['name'] ?? false;

            if (is_numeric($domain_id) && is_string($domain_name)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $exist_domain = false;
                $sql = "SELECT id FROM domains WHERE name = ?";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute([$domain_name]);
                    $exist_domain = $stmt->fetch(\PDO::FETCH_ASSOC);
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }


                if (!$exist_domain) {
                    $sql = "
                        UPDATE
                            domains
                        SET
                            domains.name = ?
                        WHERE
                            domains.id = ?
                    ";
                    try {                    
                        if (self::$CIBA2) {
                            $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                            $stmt = pdo::getCiba2Pdo()->prepare($sql);
                        }
                        else {
                            $stmt = pdo::getPDO()->prepare($sql);
                        }
                        $stmt->execute([$domain_name, $domain_id]);
                        $stmt = null;                        
                    }
                    catch (PDOException $e) {
                        print $e->getMessage();
                    }

                    $sql = "
                        UPDATE
                            sites
                        SET
                            domain_id = ?
                        WHERE
                            name LIKE ?
                    ";
                    try {                    
                        if (self::$CIBA2) {
                            $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                            $stmt = pdo::getCiba2Pdo()->prepare($sql);
                        }
                        else {
                            $stmt = pdo::getPDO()->prepare($sql);
                        }
                        $stmt->execute([$domain_id, "%{$domain_name}"]);
                        $stmt = null;                        
                    }
                    catch (PDOException $e) {
                        print $e->getMessage();
                    }

                    $answer = $domain_name;
                }
            }
        }

        return $answer;
    }
    
    
    
    public function selectDomainsSetkas(array $domains_data) : array {
        if (count($domains_data) != 0) {
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $q = $domains_data['q'];
            $page_limit = $domains_data['page_limit'];
            $page = $domains_data['page'];
            $sira = ($page - 1) * $page_limit;
            $items = [];
            
            $result = [
                'incomplete_results' => false,
                'items' => [],
                'total' => 0
            ];
            
            if (is_string($q) && is_numeric($page_limit) && is_numeric($page) && is_numeric($sira)) {            
                $count = "
                    SELECT
                        COUNT(*) AS count
                ";
                
                $select = "
                    SELECT
                        id,
                        syn AS name
                ";
                
                $from = "
                    FROM
                        setkas
                ";
                
                $where = "
                    WHERE
                        1 AND
                        (setkas.no_active IS NULL OR setkas.no_active = 0) 
                ";
                
                if ($q != '') {
                    $where .= "
                        AND (
                            syn LIKE '%$q%'
                            OR id LIKE '%$q%'
                        )
                    ";
                }
                
                $order = "
                    ORDER BY 
                        FIELD(
                            syn,
                            'Без сетки',                       
                            syn
                        ), syn ASC
                ";
                
                $limit = "
                    LIMIT
                        $sira, $page_limit
                ";
                
                
                $sql = "$count $from $where";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $total = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $total = $total['count'];
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                    
                
                $sql = "$select $from $where $order $limit";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $items[] = $row;
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                        
                
                $result = [
                    'incomplete_results' => false,
                    'items' => $items,
                    'total' => $total
                ];
            
            }
            
            return $result;
        }
    }
    
    
    
    public static function selectCurrentSetkas(array $args) : array {  
        $option = [];
        
        if (count($args) != 0) {
            $option_data = $args['option_data'] ?? false;
                        
            if (is_string($option_data)) {            
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();

                $sql = "
                    SELECT
                        id,
                        syn
                    FROM
                        setkas
                    WHERE
                        syn = '{$option_data}'
                ";                
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $option = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($option) {
                        $option[0] = ['id' => "{$option['id']}", 'text' => "{$option['syn']}"];
                    }
                    else {
                        $option[0] = ['id' => "0", 'text' => "-Не задан-"];
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }                
            }
        }
        else {
            $option[0] = ['id' => "0", 'text' => "-Не задан-"];
        }
        
        return $option;
    }
    
    
    
    public function saveDomainsSetkas(array $domains_data) : string {
        $answer = '';
        
        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $setka_id = $domains_data['setka_id'] ?? false;
            if (is_numeric($domain_id) && is_numeric($setka_id)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    UPDATE
                        domains
                    SET
                        domains.setka_id = {$setka_id}
                    WHERE
                        domains.id = {$domain_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $sql = "
                    SELECT
                        setkas.syn
                    FROM
                        setkas
                    WHERE
                        setkas.id = {$setka_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $setka = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $setka = $setka['syn'];
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $answer = $setka;
            }
        }
        
        return $answer;
    }
    
    
    
    public function saveDomainsExpired(array $domains_data) : string {
        $answer = '';
        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $domain_expired = $domains_data['expired'] ?? false;
            if (is_numeric($domain_id) && is_string($domain_expired)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    UPDATE
                        domains
                    SET
                        domains.expired = '".date("Y-m-d H:i:s", strtotime($domain_expired))."'
                    WHERE
                        domains.id = {$domain_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                    $answer = $domain_expired;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }
        }
        return $answer;
    }
    
    
    
    public function selectDomainsNoActive(array $domains_data) : array {
        if (count($domains_data) != 0) {
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $q = $domains_data['q'];
            $page_limit = $domains_data['page_limit'];
            $page = $domains_data['page'];
            $sira = ($page - 1) * $page_limit;
            $items = [];
            
            $result = [
                'incomplete_results' => false,
                'items' => [],
                'total' => 0
            ];
            
            if (is_string($q) && is_numeric($page_limit) && is_numeric($page) && is_numeric($sira)) {
                $count = "
                    SELECT
                        COUNT(*) AS count
                ";
                
                $select = "
                    SELECT
                        status.id,
                        status.name
                ";
                
                $from = "
                    FROM (
                        SELECT '0' AS id, 'Активен' AS name
                        UNION ALL
                        SELECT '1' AS id, 'Не активен' AS name
                    ) AS status
                ";
                
                $where = "
                    WHERE
                        1
                ";
                
                if ($q != '') {
                    $where .= "
                        AND (
                            name LIKE '%$q%'
                            OR id LIKE '%$q%'
                        )
                    ";
                }
                
                $order = "
                    
                ";
                
                $limit = "
                    LIMIT
                        $sira, $page_limit
                ";
                        
                        
                $sql = "$count $from $where";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $total = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $total = $total['count'];
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                
                $sql = "$select $from $where $order $limit";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $items[] = $row;
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                
                $result = [
                    'incomplete_results' => false,
                    'items' => $items,
                    'total' => $total
                ];
                        
            }
            
            return $result;
        }
    }
    
    
    
    public static function selectCurrentNoActive(array $args) : array {
        $option = [];
        
        if (count($args) != 0) {
            $option_data = $args['option_data'] ?? false;
            
            if (is_string($option_data)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    SELECT
                        status.id,
                        status.name
                    FROM (
                        SELECT '0' AS id, 'Активен' AS name
                        UNION ALL
                        SELECT '1' AS id, 'Не активен' AS name
                    ) AS status
                    WHERE
                        status.name = '{$option_data}'
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $option = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($option) {
                        $option[0] = ['id' => "{$option['id']}", 'text' => "{$option['name']}"];
                    }
                    else {
                        $option[0] = ['id' => "-1", 'text' => "-Не задан-"];
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }
        }
        else {
            $option[0] = ['id' => "-1", 'text' => "-Не задан-"];
        }
        
        return $option;
    }
    
    
    
    public function saveDomainsNoActive(array $domains_data) : string {
        $answer = '';
        
        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $no_active = $domains_data['no_active'] ?? false;
            if (is_numeric($domain_id) && is_numeric($no_active)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    UPDATE
                        domains
                    SET
                        domains.no_active = {$no_active}
                    WHERE
                        domains.id = {$domain_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                
                $sql = "
                    SELECT
                        status.id,
                        status.name
                    FROM (
                        SELECT '0' AS id, 'Активен' AS name
                        UNION ALL
                        SELECT '1' AS id, 'Не активен' AS name
                    ) AS status
                    WHERE
                        status.id = {$no_active}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $name = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $name = $name['name'];
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $answer = $name;
            }
        }
        
        return $answer;
    }
    
    
        
    public function selectDomainsServer(array $domains_data) : array {
        if (count($domains_data) != 0) {
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $q = $domains_data['q'];
            $page_limit = $domains_data['page_limit'];
            $page = $domains_data['page'];
            $sira = ($page - 1) * $page_limit;
            $items = [];
            
            $result = [
                'incomplete_results' => false,
                'items' => [],
                'total' => 0
            ];
            
            if (is_string($q) && is_numeric($page_limit) && is_numeric($page) && is_numeric($sira)) {
                $count = "
                    SELECT
                        COUNT(*) AS count
                ";
                
                $select = "
                    SELECT
                        id,
                        name
                ";
                
                $from = "
                    FROM
                        domain_servers
                ";
                
                $where = "
                    WHERE
                        1
                ";
                
                if ($q != '') {
                    $where .= "
                        AND (
                            name LIKE '%$q%'
                            OR id LIKE '%$q%'
                        )
                    ";
                }
                
                $order = "
                    ORDER BY
                        name ASC
                ";
                
                $limit = "
                    LIMIT
                        $sira, $page_limit
                ";
                        
                        
                $sql = "$count $from $where";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $total = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $total = $total['count'];
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                
                $sql = "$select $from $where $order $limit";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $items[] = $row;
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                
                $result = [
                    'incomplete_results' => false,
                    'items' => $items,
                    'total' => $total
                ];
                        
            }
            
            return $result;
        }
    }
    
    
    
    public static function selectCurrentServer(array $args) : array {
        $option = [];
        
        if (count($args) != 0) {
            $option_data = $args['option_data'] ?? false;
            
            if (is_string($option_data)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    SELECT
                        id,
                        name
                    FROM
                        domain_servers
                    WHERE
                        name = '{$option_data}'
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $option = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($option) {
                        $option[0] = ['id' => "{$option['id']}", 'text' => "{$option['name']}"];
                    }
                    else {
                        $option[0] = ['id' => "0", 'text' => "-Не задан-"];
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }
        }
        else {
            $option[0] = ['id' => "0", 'text' => "-Не задан-"];
        }
        
        return $option;
    }
    
    
    
    public function saveDomainsServer(array $domains_data) : string {
        $answer = '';
        
        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $server_id = $domains_data['server_id'] ?? false;
            if (is_numeric($domain_id) && is_numeric($server_id)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    UPDATE
                        domains
                    SET
                        domains.server_id = {$server_id}
                    WHERE
                        domains.id = {$domain_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $sql = "
                    SELECT
                        name
                    FROM
                        domain_servers
                    WHERE
                        id = {$server_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $server = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $server = $server['name'];
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $answer = $server;
            }
        }
        
        return $answer;
    }
    
    
    
    public function selectDomainsMirror(array $domains_data) : array {
        if (count($domains_data) != 0) {
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $q = $domains_data['q'];
            $page_limit = $domains_data['page_limit'];
            $page = $domains_data['page'];
            $sira = ($page - 1) * $page_limit;
            $items = [];
            
            $result = [
                'incomplete_results' => false,
                'items' => [],
                'total' => 0
            ];
            
            if (is_string($q) && is_numeric($page_limit) && is_numeric($page) && is_numeric($sira)) {
                $count = "
                    SELECT
                        COUNT(*) AS count
                ";
                
                $select = "
                    SELECT
                        mirror.id,
                        mirror.name
                ";
                
                $from = "
                    FROM (
                        SELECT '0' AS id, 'Нет' AS name
                        UNION ALL 
                        SELECT '1' AS id, 'Да' AS name
                    ) AS mirror
                        
                ";
                
                $where = "
                    WHERE
                        1
                ";
                
                if ($q != '') {
                    $where .= "
                        AND (
                            mirror.name LIKE '%$q%'
                            OR mirror.id LIKE '%$q%'
                        )
                    ";
                }
                
                $order = "
                    ORDER BY
                        mirror.name ASC
                ";
                
                $limit = "
                    LIMIT
                        $sira, $page_limit
                ";
                        
                        
                $sql = "$count $from $where";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $total = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $total = $total['count'];
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                
                $sql = "$select $from $where $order $limit";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $items[] = $row;
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                
                $result = [
                    'incomplete_results' => false,
                    'items' => $items,
                    'total' => $total
                ];
                        
            }
            
            return $result;
        }
    }
    
    
    
    public static function selectCurrentMirror(array $args) : array {
        $option = [];
        
        if (count($args) != 0) {
            $option_data = $args['option_data'] ?? false;
            
            if (is_string($option_data)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    SELECT
                        mirror.id,
                        mirror.name
                    FROM (
                        SELECT '0' AS id, 'Нет' AS name
                        UNION ALL 
                        SELECT '1' AS id, 'Да' AS name
                    ) AS mirror
                    WHERE
                        mirror.name = '{$option_data}'
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $option = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($option) {
                        $option[0] = ['id' => "{$option['id']}", 'text' => "{$option['name']}"];
                    }
                    else {
                        $option[0] = ['id' => "0", 'text' => "-Не задан-"];
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }
        }
        else {
            $option[0] = ['id' => "0", 'text' => "-Не задан-"];
        }
        
        return $option;
    }
    
    
    
    public function saveDomainsMirror(array $domains_data) : string {
        $answer = '';
        
        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $mirror_id = $domains_data['mirror_id'] ?? false;
            if (is_numeric($domain_id) && is_numeric($mirror_id)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    UPDATE
                        domains
                    SET
                        domains.mirror = {$mirror_id}
                    WHERE
                        domains.id = {$domain_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $sql = "
                    SELECT
                        mirror.id,
                        mirror.name
                    FROM (
                        SELECT '0' AS id, 'Нет' AS name
                        UNION ALL 
                        SELECT '1' AS id, 'Да' AS name
                    ) AS mirror
                    WHERE
                        mirror.id = {$mirror_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $mirror = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $mirror = $mirror['name'];
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $answer = $mirror;
            }
        }
        
        return $answer;
    }
    
    
    
    public function selectDomainsOwner(array $domains_data) : array {
        if (count($domains_data) != 0) {
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $q = $domains_data['q'];
            $page_limit = $domains_data['page_limit'];
            $page = $domains_data['page'];
            $sira = ($page - 1) * $page_limit;
            $items = [];
            
            $result = [
                'incomplete_results' => false,
                'items' => [],
                'total' => 0
            ];
            
            if (is_string($q) && is_numeric($page_limit) && is_numeric($page) && is_numeric($sira)) {
                $count = "
                    SELECT
                        COUNT(*) AS count
                ";
                
                $select = "
                    SELECT
                        id,
                        name
                ";
                
                $from = "
                    FROM
                        domain_owners                    
                ";
                
                $where = "
                    WHERE
                        1
                ";
                
                if ($q != '') {
                    $where .= "
                        AND (
                            name LIKE '%$q%'
                            OR id LIKE '%$q%'
                        )
                    ";
                }
                
                $order = "
                    ORDER BY
                        name ASC
                ";
                
                $limit = "
                    LIMIT
                        $sira, $page_limit
                ";
                        
                        
                $sql = "$count $from $where";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $total = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $total = $total['count'];
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                
                $sql = "$select $from $where $order $limit";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                        $items[] = $row;
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                        
                        
                $result = [
                    'incomplete_results' => false,
                    'items' => $items,
                    'total' => $total
                ];
                        
            }
            
            return $result;
        }
    }
    
    
    
    public static function selectCurrentOwner(array $args) : array {
        $option = [];
        
        if (count($args) != 0) {
            $option_data = $args['option_data'] ?? false;
            
            if (is_string($option_data)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    SELECT
                        id,
                        name
                    FROM
                        domain_owners
                    WHERE
                        name = '{$option_data}'
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $option = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($option) {
                        $option[0] = ['id' => "{$option['id']}", 'text' => "{$option['name']}"];
                    }
                    else {
                        $option[0] = ['id' => "0", 'text' => "-Не задан-"];
                    }
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }
        }
        else {
            $option[0] = ['id' => "0", 'text' => "-Не задан-"];
        }
        
        return $option;
    }
    
    
    
    public function saveDomainsOwner(array $domains_data) : string {
        $answer = '';
        
        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $owner_id = $domains_data['owner_id'] ?? false;
            if (is_numeric($domain_id) && is_numeric($owner_id)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    UPDATE
                        domains
                    SET
                        domain_owner_id = {$owner_id}
                    WHERE
                        id = {$domain_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $sql = "
                    SELECT
                        id,
                        name
                    FROM
                        domain_owners
                    WHERE
                        id = {$owner_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $owner = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $owner = $owner['name'];
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $answer = $owner;
            }
        }
        
        return $answer;
    }
    
    
    
    public function saveDomainsPurchased(array $domains_data) : string {
        $answer = '';
        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $domain_purchased = $domains_data['purchased'] ?? false;
            if (is_numeric($domain_id) && is_string($domain_purchased)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    UPDATE
                        domains
                    SET
                        domains.purchased = '".date("Y-m-d H:i:s", strtotime($domain_purchased))."'
                    WHERE
                        domains.id = {$domain_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                    $answer = $domain_purchased;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }
        }
        return $answer;
    }
    
    
    
    public function saveDomainsCost(array $domains_data) : string {
        $answer = '';
        if (count($domains_data) != 0) {
            $domain_id = $domains_data['id'] ?? false;
            $domain_cost = $domains_data['cost'] ?? false;
            if (is_numeric($domain_id) && is_numeric($domain_cost)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    UPDATE
                        domains
                    SET
                        domains.cost = '{$domain_cost}'
                    WHERE
                        domains.id = {$domain_id}
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                    $answer = $domain_cost;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }
        }
        return $answer;
    }
    
    
    
    public static function addNewDomain() {
        $new_domain_id = false;
        
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();
        
        $id = 0;
        $sql = "SELECT MAX(id) AS id FROM domains";
        try {
            if (self::$CIBA2) {
                $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($sql);
            }
            else {
                $stmt = pdo::getPDO()->prepare($sql);
            }
            $stmt->execute(array());
            $id = $stmt->fetch(\PDO::FETCH_ASSOC);
            $id = $id['id'];
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        $sql = "
            INSERT INTO domains
                (name, setka_id, expired, purchased)
            VALUES
                ('Новый домен {$id}', 29, '".date("Y-m-d")."', '".date("Y-m-d")."')
        ";
        try {
            if (self::$CIBA2) {
                $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($sql);
                $stmt->execute(array());
                $new_domain_id = pdo::getCiba2Pdo()->lastInsertId();
            }
            else {
                $stmt = pdo::getPDO()->prepare($sql);
                $stmt->execute(array());
                $new_domain_id = pdo::getPDO()->lastInsertId();
            }
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        return $new_domain_id ? $new_domain_id : false;
    }
    
    
    
    public static function deleteDomain(string $domain_name = '') : string {        
        if ($domain_name != '') {
            $answer = '';
            
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
                 
            $sql = "UPDATE domains SET no_active = 1 WHERE name = '{$domain_name}'";
            try {
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }
                $stmt->execute();
                $stmt = null;
                $answer = $domain_name;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }                                                                
        }
        
        return $answer;
    }
    
    
    
    public static function enableDomain(string $domain_name = '') : string {
        if ($domain_name != '') {
            $answer = '';
            
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $sql = "UPDATE domains SET no_active = 0 WHERE name = '{$domain_name}'";
            try {
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }
                $stmt->execute();
                $stmt = null;
                $answer = $domain_name;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
        }
        
        return $answer;
    }
    
    
    
    public static function getDomainSites(array $args = []) : array {
        $start = isset($args['start']) ? $args['start'] : 0;
        $rowperpage = isset($args['length']) ? $args['length'] : 25;
        if ($rowperpage == -1) {
            $rowperpage = 10;
        }
        $columnIndex = isset($args['order'][0]['column']) ? $args['order'][0]['column'] : -1;
        if ($columnIndex != -1) {
            $columnIndex = $columnIndex + 1;
        }
        else {
            $columnIndex = false;
        }
        $columnName = isset($args['columns'][$columnIndex]['data']) ? $args['columns'][$columnIndex]['data'] : false;
        $columnSortOrder = isset($args['order'][0]['dir']) ? $args['order'][0]['dir'] : false; // asc or desc
        
        $searchValue = isset($args['search']['value']) ? $args['search']['value'] : false;
        
        $domain_id = $args['domain_id'] ?? 0;
        
        
        $count_column = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                COUNT(sites.id) AS count_rows
        ";
        
        
        $select = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                sites.id AS id,
                sites.name AS name
        ";
        
        
        $from = "
            FROM
                sites
        ";
        
        
        $where = "
            WHERE
                1
                AND domain_id = {$domain_id}
                AND (sites.no_active = 0 OR sites.no_active IS NULL)
        ";
        
        
        $filter = "";
        if ($searchValue) {
            $filter .= "
                AND (
                    sites.id LIKE '%{$searchValue}%'
                    OR sites.name LIKE '%{$searchValue}%'
                )
            ";
        }               
        
        
        $order = "";

        $order .= "
            ORDER BY
                $columnIndex $columnSortOrder
        ";

                
        $limit = "";
        if ($rowperpage) {
            $limit = "
                LIMIT
                    $start, $rowperpage
            ";
        }
        
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();
        
        $recordsTotal = "$count_column $from $where";
        try {
            if (self::$CIBA2) {
                $recordsTotal = pdo::prepareChangeToCiba2($recordsTotal, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($recordsTotal);
            }
            else {
                $stmt = pdo::getPDO()->prepare($recordsTotal);
            }
            $stmt->execute(array());
            $recordsTotal = $stmt->fetch(\PDO::FETCH_ASSOC);
            $recordsTotal = $recordsTotal ? $recordsTotal['count_rows'] : false;
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        
        $recordsFiltered = "$count_column $from $where $filter";
        try {
            if (self::$CIBA2) {
                $recordsFiltered = pdo::prepareChangeToCiba2($recordsFiltered, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($recordsFiltered);
            }
            else {
                $stmt = pdo::getPDO()->prepare($recordsFiltered);
            }
            $stmt->execute(array());
            $recordsFiltered = $stmt->fetch(\PDO::FETCH_ASSOC);
            $recordsFiltered = $recordsFiltered ? $recordsFiltered['count_rows'] : false;
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        $data = [];
        
        $sql = "$select $from $where $filter $order $limit";
        try {
            if (self::$CIBA2) {
                $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($sql);
            }
            else {
                $stmt = pdo::getPDO()->prepare($sql);
            }
            $stmt->execute(array());
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        
        $datatable = [];
        $number = 0;
        if (count($data) != 0) {
            foreach ($data as $index => $row) {
                foreach ($row as $key => $cell) {
                    $cell_value = $cell;
                    if ($key == 'name') {
                        if (tools::is_idn($cell_value)) {
                            $cell_value = idn_to_utf8($cell_value, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                        }
                    }
                    $datatable[$number][] = $cell_value;
                }
                $number++;
            }
        }
        unset($data);
        
                
        return array(
            "draw" => $args['draw'] ? intval($args['draw']) : 0,
            "recordsTotal"    => $recordsTotal ? intval($recordsTotal) : 0,
            "recordsFiltered" => $recordsFiltered ? intval($recordsFiltered) : 0,
            "data"            => count($datatable) != 0 ? $datatable : []
        );        
    }
    
    public function saveDomainsComment($source_data) {
        $source_data = is_array($source_data) ? $source_data : [];
        if (count($source_data) != 0) {
            if (is_numeric($source_data['id']) && isset($source_data['comment'])) {
                $sql = "
                    UPDATE
                        domains
                    SET
                        domains.comment = '{$source_data['comment']}'
                    WHERE
                        domains.id = {$source_data['id']}
                ";
                try {
                    self::setCIBA2();
                    if (self::$CIBA2) pdo::clearPdo();
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(array());
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                return $source_data['comment'];
            }
        }        
    }
    
    public function saveDomain(array $domains_data) {
        
        if (!empty($domains_data['name'])) {
        
            $name = $domains_data['name'];
            $setka_id = $domains_data['setka_id'] ?? 29;
            
            $time = tools::get_time(); 
            
            $expired = !empty($domains_data['expired']) ? date('Y-m-d', strtotime($domains_data['expired'])) : date('Y-m-d', strtotime('+1 year', $time));
            $purchased = !empty($domains_data['purchased']) ? date('Y-m-d', strtotime($domains_data['purchased'])) : date('Y-m-d', $time);
                         
            $domain_id = 0;
            
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $sql = "
                SELECT id FROM domains WHERE name=:name 
            ";
            
            try {
                $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($sql);
                $stmt->execute(array('name' => $name));
                $domain_id = $stmt->fetch(\PDO::FETCH_COLUMN);
                
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            
            if ($domain_id) {
                return 
                    ['code' => 'error',
                     'answer' => 'Такой домен уже есть!'
                    ];   
            }
            
            if (!$domain_id) {
                $sql = "
                    INSERT INTO domains
                        (name, setka_id, expired, purchased)
                    VALUES
                        (?, ?, ?, ?)
                ";
                
                try {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    $stmt->execute(array($name, $setka_id, $expired, $purchased));  
                    $domain_id = pdo::getCiba2Pdo()->lastInsertId();
                                      
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }      
            } 
            
            return $domain_id;
                        
        } 
        else {
            return 
                ['code' => 'error',
                 'answer' => 'Введите название!'
                ]; 
        }
        
        return $new_source_id;
    } 
    
}