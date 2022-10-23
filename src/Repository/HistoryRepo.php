<?php

namespace App\Repository;

use framework\pdo;
use framework\load;
use framework\tools;
use PDOException;


class HistoryRepo {
    private static $CIBA2;


    private static function setCIBA2() {
        $user_id = load::get_user_id();
        $sql = "
            SELECT
                id
            FROM
                user_access
            WHERE
                page = 'history'
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


    
    public static function setHistoryTemp() {
        $data = [];
        $user_id = load::get_user_id();

        //db button
        $sql = "
            SELECT
                id
            FROM
                user_access
            WHERE
                page = 'history'
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
                page = 'history'
                AND type = 'database'
                AND user_id = $user_id
        ";
        try {
            $stmt = pdo::getPDO()->prepare($sql);
            $stmt->execute(array());
            $history_db = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($history_db) {
                if ($history_db['value'] === 'ciba2') {
                    $data['history_db'] = 'ciba2';
                }
                else if ($history_db['value'] === 'ciba3') {
                    $data['history_db'] = 'ciba3';
                }
            }
            else {
                $data['history_db'] = false;
            }
            $stmt = null;
            $history_db = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        return count($data) != 0 ? $data : [];
    }



    public static function setHistoryUserDatabase() {
        $value = false;
        $user_id = load::get_user_id();

        $sql = "
            SELECT
                value
            FROM
                user_access
            WHERE
                page = 'history'
                AND type = 'database'
                AND user_id = $user_id
        ";
        try {
            $stmt = pdo::getPDO()->prepare($sql);
            $stmt->execute(array());
            $history_db = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($history_db) {
                if ($history_db['value'] === 'ciba2') {
                    $value = 'ciba3';
                }
                else if ($history_db['value'] === 'ciba3') {
                    $value = 'ciba2';
                }
            }
            else {
                $value = false;
            }
            $stmt = null;
            $history_db = null;
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
                    page = 'history'
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



    public static function getHistoryData(array $args = []) {
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();

        $draw = isset($args['draw']) ? $args['draw'] : false;
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
        $s_mode = isset($args['s_mode']) ? $args['s_mode'] : 0;

        $save = array_key_exists('save', $args) ? $args['save'] : false;
       
        $filters = array_key_exists('filters', $args) ? $args['filters'] : [];

        $datepicker = array_key_exists('datepicker', $args) ? $args['datepicker'] : false;
        if ($datepicker) {
            $date_start = array_key_exists('start', $datepicker) ? $datepicker['start'] : false;
            $date_end = array_key_exists('end', $datepicker) ? $datepicker['end'] : false;
            if ($date_start) {
                $start_time = new \DateTime($date_start);
                $date_start = strtotime($date_start);
                $date_start = date("Y-m-d H:i:s", $date_start);
            }
            else {
                $start_time = new \DateTime(date("Y-m-d 00:00:00"));
                $date_start = date("Y-m-d 00:00:00");                
            }
            if ($date_end) {
                $end_time = new \DateTime($date_end);
                $date_end = strtotime($date_end);
                $date_end = date("Y-m-d H:i:s", $date_end);
            }
            else {
                $end_time = new \DateTime(date("Y-m-d 23:59:59"));
                $date_end = date("Y-m-d 23:59:59");
            }            
        }

        $default_sort = array_key_exists('default_sort', $args) ? $args['default_sort'] : false;

        $invalid = isset($args['invalid']) ? $args['invalid'] : false;
        $status = isset($args['status']) ? $args['status'] : false;
        $active = $args['active'] ?? -1;

        $data = [];


        $select = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                historys.id AS history_id,
                IF(sources.name IS NULL, '', CONCAT(sources.name, ' ', sources.id)) AS source,
                IF(organizations.name IS NULL, '', CONCAT(organizations.name, ' ', organizations.id)) AS organization,
                IF(address.name IS NULL, '', address.name) AS address,
                IF(workers.name IS NULL, '', workers.name) AS worker,
                IF(uis_lines.name IS NULL, '', uis_lines.name) AS uis,
                DATE_FORMAT(historys.timestamp, '%d.%m.%Y %H:%i') AS history_time,
                IF(historys.status_history_id IS NULL OR historys.status_history_id = 0, 'В процессе', IF(historys.not_active IS NULL OR historys.not_active = 0, 'Запущен', 'Остановлен')) AS history_action,
                IF(parent.name IS NULL, '', parent.name) AS parent,
                IF(historys.user_id IS NULL, 0, historys.user_id) AS user_id,
                IF(status_historys.name IS NULL, '', status_historys.name) AS history_status
        ";

        $from = "
            FROM
                historys
            LEFT JOIN status_historys ON
                historys.status_history_id = status_historys.id
            LEFT JOIN sources ON
                historys.source_id = sources.id
            LEFT JOIN uis_lines ON
                uis_lines.id = historys.uis_line_id
            LEFT JOIN organizations ON
                historys.organization_id = organizations.id
            LEFT JOIN address ON
                historys.addres_id = address.id
            LEFT JOIN users ON
                historys.user_id = users.id
            LEFT JOIN workers ON
                users.worker_id = workers.id
            LEFT JOIN regions ON
                sources.region_id = regions.id
            LEFT JOIN (
                SELECT
                    sources.id AS id,
                    sources.name AS name
                FROM
                    sources
                WHERE
                    sources.parent IS NULL OR sources.parent = 0
            ) AS parent ON parent.id = sources.parent
        ";

        $where = "
            WHERE
                1
                AND (historys.timestamp >= '$date_start' AND historys.timestamp <= '$date_end')
        ";                
        
        $search = "";
        if ($searchValue) {
            $search .= "
                AND (
                    IF(sources.name IS NULL, '', sources.name) LIKE '%{$searchValue}%'
                    OR IF(organizations.name IS NULL, '', organizations.name) LIKE '%{$searchValue}%'
                    OR IF(address.name IS NULL, '', address.name) LIKE '%{$searchValue}%'
                    OR IF(workers.name IS NULL, '', workers.name) LIKE '%{$searchValue}%'
                    OR IF(historys.status_history_id IS NULL OR historys.status_history_id = 0, 'В процессе', IF(historys.not_active IS NULL OR historys.not_active = 0, 'Запущен', 'Остановлен')) LIKE '%{$searchValue}%'
                )
            ";
        }

        
        $filter = "";
        if (!empty($filters['regions'])) {
            $ids = array_map('intval', $filters['regions']);
            $filter .= " AND (`regions`.`id` IN (" . implode(',', $ids) . ")) ";
            unset($filters['regions']);
        }
        if (!empty($filters['organizations'])) {
            $ids = array_map('intval', $filters['organizations']);
            $filter .= " AND (`organizations`.`id` IN (" . implode(',', $ids) . ")) ";
            unset($filters['organizations']);
        }

        if ($invalid == 'true') {
            $filter .= " AND status_historys.negative = 1 ";
        }

        if ($status) {
            $filter .= " AND status_historys.id = {$status} ";
        }

        if ($active != -1) {
            $filter .= " AND historys.not_active = {$active} ";
        }

        $inner_join_filters = "";
        if (!empty($filters)) {
            foreach ($filters as $filt => $ids) {
                $ids = array_map('intval', $ids);
                if (!in_array($filt, ['regions', 'organizations'])) {
                    $filt_name = mb_substr($filt, 0, -1);                    
                    $inner_join_filters .= "
                        INNER JOIN (
                            SELECT DISTINCT
                                source_id
                            FROM
                                sources
                            LEFT JOIN source_tags ON
                                sources.id = source_tags.source_id
                            WHERE
                                -- (sources.parent = 0 OR sources.parent IS NULL) AND
                                (source_tags.name_type = '{$filt_name}' AND source_tags.id_type IN (" . implode(',', $ids) . "))
                        ) AS {$filt_name}_table ON {$filt_name}_table.source_id = sources.id
                    ";
                }
            } 
        }

        $group = "
            
        ";

        $order = "";
        if ($columnIndex && $columnSortOrder) {
            if ($default_sort == '1') {
                $order .= "
                    ORDER BY
                        1 DESC
                ";
            }
            else {
                if ($columnIndex != 7) {
                    $order .= "
                        ORDER BY
                            $columnIndex $columnSortOrder
                    ";
                }
                else {
                    $order .= "
                        ORDER BY
                            DATE(historys.timestamp) $columnSortOrder
                    ";
                }
            }        
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


        $recordsTotal = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
            COUNT(historys.id) AS count_rows FROM historys
        ";
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
        
        
        $recordsFiltered = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
            COUNT(historys.id) AS count_rows
            $from
            $inner_join_filters
            $where
            $search
            $filter
        ";
        try {
            if (self::$CIBA2) {
                $recordsFiltered = pdo::prepareChangeToCiba2($recordsFiltered, self::$CIBA2);
                //print($recordsFiltered);
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
        

        //disable limit for downloading xlsx file with all data
        if ($save == '1') {
            $limit = "";
        }  

        $sql = "$select $from $inner_join_filters $where $search $filter $group $order $limit";
        try {
            if (self::$CIBA2) {
                $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                //print($sql);
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
                    if ($save == '1') {
                        if ($cell_value == '0') {
                            $cell_value = "$cell_value.0";
                        }
                    }
                    if (in_array($key, ['source', 'nls_source'])) {
                        if ($row['parent'] != '') {
                            $cell_value = "{$row['parent']}, {$cell_value}";
                        }
                    }
                    if (in_array($key, ['worker'])) {
                        if ($row['user_id'] == 0) {
                            $cell_value = 'Система';
                        }
                        if ($cell_value == '') {
                            $cell_value = 'Не найден';
                        }
                    }
                    if (!in_array($key, ['parent', 'user_id'])) {
                        $datatable[$number][] = $cell_value;
                    }                                   
                } 
                
                $datatable[$number]['DT_RowData'] = [
                    'history_status' => $data[$index]['history_status']
                ];
                unset($datatable[$number][10]);
                
                $number++;
            }
        }
        
        $data = $datatable;
        
        /*
        $sql = "
            SELECT
                historys.id,
                sources.uis_line_id
            FROM
                historys
            INNER JOIN sources ON
                historys.source_id = sources.id
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
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $update = "
                    UPDATE
                        historys
                    SET
                        historys.uis_line_id = {$row['uis_line_id']}
                    WHERE
                        historys.id = {$row['id']}
                ";
                try {
                    if (self::$CIBA2) {
                        $update = pdo::prepareChangeToCiba2($update, self::$CIBA2);
                        $q_stmt = pdo::getCiba2Pdo()->prepare($update);
                    }
                    else {
                        $q_stmt = pdo::getPDO()->prepare($update);
                    }
                    $q_stmt->execute(array());                               
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }            
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        */        


        if ($save == '1') {  
            /*                      
            $tarif_data = [];
            $sql = "
                SELECT DISTINCT
                    organizations.name AS org_name,                     
                    IF(brands.name IS NOT NULL, brands.name, IF(model_types.name IS NOT NULL, model_types.name, IF(user_tags.name IS NOT NULL, user_tags.name, 'Базовый Тариф'))) AS field,
                    tarif_orgs.tarif_a as tarif_a,  
                    tarif_orgs.tarif_b as tarif_b
                FROM
                    tarif_orgs
                LEFT JOIN organizations ON
                    tarif_orgs.organization_id = organizations.id 
                LEFT JOIN tarif_orgs AS base_tarifs ON
                    organizations.id = base_tarifs.organization_id AND base_tarifs.name_type = 'base'      
                LEFT JOIN brands ON
                    tarif_orgs.name_type = 'brand' AND tarif_orgs.id_type = brands.id
                LEFT JOIN model_types ON
                    tarif_orgs.name_type = 'model_type' AND tarif_orgs.id_type = model_types.id
                LEFT JOIN user_tags ON
                    tarif_orgs.name_type = 'user_tag' AND tarif_orgs.id_type = user_tags.id
                INNER JOIN address ON
                    tarif_orgs.organization_id = address.organization_id
                INNER JOIN navy_services ON
                    address.id = navy_services.addres_id
                WHERE
                    navy_services.region_id = 1
                    AND organizations.lid_type = 3
                ORDER BY
                    organizations.name ASC, IF(brands.name IS NOT NULL, brands.name, IF(model_types.name IS NOT NULL, model_types.name, IF(user_tags.name IS NOT NULL, user_tags.name, 'Базовый Тариф'))) ASC
            ";
            try {
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    //print($sql);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }
                $stmt->execute(array());
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $field = 'Error';
                    if (!is_null($row['brand_name'])) {
                        $field = $row['brand_name'];
                    }
                    else if (!is_null($row['model_name'])) {
                        $field = $row['model_name'];
                    }
                    else if (!is_null($row['user_tag'])) {
                        $field = $row['user_tag'];
                    }                
                    $tarif_data[] = [$row['org_name'], $row['base_tarif_a'], $row['tarif_a'], $row['base_tarif_b'], $row['tarif_b'], $field];
                }            
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            $xlsx = tools::DownloadXlsx($tarif_data, $header);
            */
            $header = ['id', 'Источник', 'Владелец', 'Филиал', 'Пользователь', 'Время', 'Действие'];
            $xlsx = tools::DownloadXlsx($data, $header);
            return array("data" => $xlsx);
        }
        else {
            return array(
                "draw" => $args['draw'] ? intval($args['draw']) : 0,
                "recordsTotal"    => $recordsTotal ? intval($recordsTotal) : 0,
                "recordsFiltered" => $recordsFiltered ? intval($recordsFiltered) : 0,
                "data"            => count($data) != 0 ? $data : []
            ); 
        } 
    }

        
}