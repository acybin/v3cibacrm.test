<?php

namespace App\Repository;

use framework\pdo;
use framework\load;
use framework\tools;
use PDOException;



class KeyRepo {
    private static $CIBA2;
    
    private static function setCIBA2() {        
        self::$CIBA2 = true;
    }
    
    public static function uploadFile($args) {
        
       //print_r($_FILES);
       
       $max_size = 100 * 1024 * 1024;
       $date = date("Y-m-d-H-i-s");
        
       $file = isset($_FILES['uploaded']) ? $_FILES['uploaded'] : array();
       pdo::clearPdo();
       
       if (isset($file['error']) && isset($file['size']) && isset($file['type'])) {
            if (!$file['error']) {
                if ($file['size'] < $max_size && $file['type'] == 'application/vnd.ms-excel') {
                    $uniqid = "keys-" . $date;
                    $name = $uniqid.'.'.pathinfo($file['name'], PATHINFO_EXTENSION);
                     
                    $dir = '/upload/csv/' . $name;
                    $target_file = \DOCUMENT_ROOT . $dir;
                    
                    move_uploaded_file($file['tmp_name'], $target_file);
                    
                    $file = file_get_contents($target_file);
                    //$file = iconv('windows-1251', 'UTF-8', $file);
                    
                    $str = explode(PHP_EOL, $file);
                    unset($file);
                    
                    $i = 0;
                    $mysql = array();
                    
                    pdo::getCiba2Pdo()->query("SET NAMES 'cp1251'");
                    
                    foreach ($str as $value) {
                        $value = trim($value);
                        if ($value) {
                            
                            if ($i == 20000) {
                                
                                $sql = "
                                    INSERT INTO `key_files`
                                        (file_name, action, str)
                                    VALUES ".
                                    implode(',', $mysql);    
                                ;
                                
                                try {
                                    pdo::getCiba2Pdo()->query($sql);
                                } catch (PDOException $e) {
                                    print $e->getMessage();
                                }
                                
                                $mysql = [];
                                $i = 0;
                            }
                            
                            $mysql[] = "('$uniqid', 0, '$value')";
                            $i++;
                        }
                    }
                    
                    if ($mysql) {
                        $sql = "
                            INSERT INTO `key_files`
                                (file_name, action, str)
                            VALUES ".
                            implode(',', $mysql);    
                        ;
                        
                        try {
                            pdo::getCiba2Pdo()->query($sql);
                        } catch (PDOException $e) {
                            print $e->getMessage();
                        }
                    }
                    
                    exec("php ".\DOCUMENT_ROOT."robot2.php > /dev/null &", $output, $return_var);                    
                }
            }
       }
    }
    
    public static function parseStr($mas_str) {
        
        pdo::clearPdo();
        
        if (!$mas_str) return;
        
        $sql = "SELECT LOWER(name), id FROM `brands` 
                      WHERE `organization_id` IS NULL AND (`ru_name` IS NOT NULL AND `ru_name` != '')";
        $brands = pdo::getCiba2Pdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
                
        $sql = "SELECT LOWER(name), id FROM `model_types` 
                        WHERE `organization_id` IS NULL";
        $model_types = pdo::getCiba2Pdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $sql = "SELECT LOWER(name), id FROM `offers` 
                        WHERE `no_active` IS NULL OR `no_active` = 0";
        $offers = pdo::getCiba2Pdo()->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $deleted_id = [];
        $insert_tags = [];
        $insert = [];
        
        foreach ($mas_str as $str) {
        
            $cols = explode(';', $str);
            foreach ($cols as $key => $col) {
                $cols[$key] = trim($col);
                if ($cols[$key] == 'null') $cols[$key] = '';
            }
            
            $id = $cols[0];
            
            $keyword = $cols[1];
            
            $keyword = preg_replace('/-\w+/u', '', $keyword);
            $keyword = str_replace(['+', '[', ']', '"', '!'], '', mb_strtolower(tools::cut_empty($keyword)));
            $keyword = preg_replace('@\x{FFFD}@u', '', $keyword); 
            
            $tags = $cols[2];
            $marker = $cols[3]; 
            
            if (!$id) {
                try {
                    $sql = "INSERT IGNORE INTO `keys` (`name`, `marker`) VALUES ('".$keyword."','".$marker."')";
                    //echo $sql;
                    pdo::getCiba2Pdo()->query($sql);
                    $id = pdo::getCiba2Pdo()->lastInsertId(); 
               } catch (PDOException $e) {
                    print $e->getMessage();
               }                       
            }
            else {                
                
                /*try {
                    $sql = "SELECT id FROM key_to_campaigns WHERE key_id = {$id}";
                    $key_to_campaign = pdo::getCiba2Pdo()->query($sql)->fetchColumn();
                } catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                /*$key_to_campaign = true;
                
                if ($key_to_campaign) {                    
                    try {
                        $sql = "INSERT IGNORE INTO `keys` (`id`, `marker`) VALUES (".$id.",'".$marker."')
                                        ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `marker` = VALUES(`marker`)";  
                        //echo $sql;                                                              
                        pdo::getCiba2Pdo()->query($sql);
                        $id = pdo::getCiba2Pdo()->lastInsertId();
                    } catch (PDOException $e) {
                        print $e->getMessage();
                    }   
                }
                else {
                    try {
                        $sql = "INSERT IGNORE INTO `keys` (`id`, `name`, `marker`) VALUES (".$id.",'".$keyword."','".$marker."')
                                        ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `name` = VALUES(`name`), `marker` = VALUES(`marker`)";
                        //echo $sql;                                                                
                        pdo::getCiba2Pdo()->query($sql);
                        $id = pdo::getCiba2Pdo()->lastInsertId(); 
                    } catch (PDOException $e) {
                        print $e->getMessage();
                    }    
                }*/
                
                $insert[] = "(".$id.",'".$marker."')";    
            }
            
            if ($id) {
                
                $deleted_id[] = $id;
                
                if ($tags) {
                    $tags = explode(',', $tags);
        
                    foreach ($tags as $tag) {
                        
                        $tag = trim(mb_strtolower($tag));
                        
                        if ($brands[$tag]) {
                            $insert_tags[] = "(".$id.",".$brands[$tag].",'brand')";   
                        }
                        
                        if ($model_types[$tag]) {
                            $insert_tags[] = "(".$id.",".$model_types[$tag].",'model_type')";   
                        }
                        
                        if ($offers[$tag]) {
                            $insert_tags[] = "(".$id.",".$offers[$tag].",'offer')";   
                        }
                    }
               }                
            }
        }
        
        if ($deleted_id) {
            try {
                $sql = "DELETE FROM key_tags WHERE key_id IN (".implode(',', $deleted_id).")";
                pdo::getCiba2Pdo()->query($sql);
            } catch (PDOException $e) {
                print $e->getMessage();
            }
        }
        
        if ($insert_tags) {
            try {
                $sql = "INSERT IGNORE INTO `key_tags` (`key_id`, `id_type`, `name_type`) VALUES ".implode(',', $insert_tags);
                pdo::getCiba2Pdo()->query($sql);
             } catch (PDOException $e) {
                print $e->getMessage();
            }
        }
        
        if ($insert) {
            try {
                $sql = "INSERT IGNORE INTO `keys` (`id`, `marker`) VALUES ".implode(',', $insert). "
                            ON DUPLICATE KEY UPDATE `marker` = VALUES(`marker`)";
                pdo::getCiba2Pdo()->query($sql);
            } catch (PDOException $e) {
                print $e->getMessage();
            }
        }              
    }
    
    public static function downloadKeys($args) {
        
        $searchValue = isset($args['search']['value']) ? $args['search']['value'] : false;
        $no_tags = isset($args['no_tags']) ? $args['no_tags'] : false;
        $date = date("Y-m-d-H-i-s");
        
        $count_column = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                COUNT(*) AS count_rows
        ";
        
        $from = "
            FROM 
                `keys`
            
            LEFT JOIN (
		          SELECT 
                    GROUP_CONCAT(CONCAT(name_type , '-', id_type)) AS key_tag, 
                    key_id 
                  FROM 
                    key_tags 
                  GROUP BY key_id
            ) AS key_tags ON key_tags.key_id = keys.id  
            
            ";
        
        $where = "
            WHERE
                1
                
        ";
        
        $filter = "";
        $inner_join_filters = "";
        
        if ($searchValue) {
            $filter .= "
                AND (
                    keys.id LIKE '%{$searchValue}%'
                    OR keys.name LIKE '%$searchValue%'
                )
            ";
        }
        
        if ($no_tags) {
            $filter .= "
                AND (
                    key_tags.key_tag IS NULL
                )
            ";
        }
        
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();
        
        $recordsFiltered = "$count_column $from $inner_join_filters $where $filter";
        
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
        
        $file = "keys-" . $date;
        $j = 0;
        
        if ($recordsFiltered) {            
            for ($i = 0; $i <= $recordsFiltered; $i += 20000) {
                $args['file'] = $file;
                $args['start'] = $i;
                $args['length'] = 20000;
                
                $params = str_replace('\\u','\\\\u',json_encode($args));
                
                $j = $j % 10;
                
                $sql = "
                    INSERT INTO `key_robots`
                        (file_name, action, pid, params)
                    VALUES
                        ('$file', 0, $j, '$params')
                ";
                
                pdo::getCiba2Pdo()->query($sql);
                
                $j++;
                
                //self::getKeys($args);  
            }
            
            //for ($j = 0; $j < 10; $j++) {
                //exec("php ".\DOCUMENT_ROOT."robot.php pid=$j > /dev/null &", $output, $return_var);
            //}
            
            exec("php ".\DOCUMENT_ROOT."robot.php > /dev/null &", $output, $return_var);            
        }
        
        return array("data" => '/upload/csv/'.$file.'.csv'); 
    }
    
    
    public static function getKeys($args = []) {
        $filters = array_key_exists('filters', $args) ? $args['filters'] : [];  
        
        $draw = isset($args['draw']) ? $args['draw'] : false; //number of table's refreshes
        $start = isset($args['start']) ? $args['start'] : 0;              
        $rowperpage = isset($args['length']) ? $args['length'] : 50;               
        $columnIndex = isset($args['order'][0]['column']) ? $args['order'][0]['column'] : false;        
        if ($columnIndex != -1) {
            $columnIndex = $columnIndex + 1;
        }
        else {
            $columnIndex = false;
        }
        
        $columnName = isset($args['columns'][$columnIndex]['data']) ? $args['columns'][$columnIndex]['data'] : false;
        $columnSortOrder = isset($args['order'][0]['dir']) ? $args['order'][0]['dir'] : false; // asc or desc
        $searchValue = isset($args['search']['value']) ? $args['search']['value'] : false;
        
        $save = array_key_exists('save', $args) ? $args['save'] : false; 
        
        $file = isset($args['file']) ? $args['file'] : uniqid();
        
        $no_tags = isset($args['no_tags']) ? $args['no_tags'] : false;
        
        $count_column = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                COUNT(*) AS count_rows
        ";
        
        $sql = "SET SESSION group_concat_max_len = 1000000;";
        pdo::getCiba2Pdo()->query($sql);
        
        $select = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                    keys.id,
                    keys.name, 
                    '' AS tags,
                    keys.marker,
                    IF(campaigns_table.count_campaigns IS NULL, 0, campaigns_table.count_campaigns) AS campaigns,
                    '' AS delete_key,
                    key_tags.key_tag,
                    keys.no_active AS no_active                         
        ";
        
        $from = "
            FROM 
                `keys`
                                
            LEFT JOIN (
		          SELECT 
                    GROUP_CONCAT(CONCAT(name_type , '-', id_type)) AS key_tag, 
                    key_id 
                  FROM 
                    key_tags 
                  GROUP BY key_id
            ) AS key_tags ON key_tags.key_id = keys.id  
                
            LEFT JOIN (
                SELECT
                    key_to_campaigns.key_id,
                    COUNT(*) AS count_campaigns
                FROM
                    key_to_campaigns
                GROUP BY
                    key_to_campaigns.key_id
            ) AS campaigns_table ON campaigns_table.key_id = keys.id
                 
        ";
        
        /*if ($save) {
            
            $from .= "
                 LEFT JOIN (
                    SELECT
                        key_to_campaigns.key_id,
                        GROUP_CONCAT(campaigns.name) AS names
                    FROM
                        key_to_campaigns
                    LEFT JOIN campaigns ON 
                        campaigns.id = key_to_campaigns.campaign_id 
                    GROUP BY
                        key_to_campaigns.key_id
                ) AS campaign_names ON campaign_names.key_id = keys.id
            ";
            
           $select .= ",campaign_names.names"; 
            
        }*/
        
        $where = "
            WHERE
                1
                
        ";
        
        $filter = "";
        $inner_join_filters = "";
        
        if ($searchValue) {
            $filter .= "
                AND (
                    keys.id LIKE '%{$searchValue}%'
                    OR keys.name LIKE '%$searchValue%'
                )
            ";
        }
        
        if ($no_tags) {
            $filter .= "
                AND (
                    key_tags.key_tag IS NULL
                )
            ";
        }
        
        $order = "";
        if ($columnIndex && $columnSortOrder) {           
            $order .= "
                ORDER BY
                    $columnIndex $columnSortOrder
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
        
        $recordsTotal = "$count_column $from";
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
        
        
        $recordsFiltered = "$count_column $from $inner_join_filters $where $filter";
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
            //$limit = "";
        }
        
        $sql = "$select $from $where $inner_join_filters $filter $order $limit";

        try {
            if (self::$CIBA2) {
                $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($sql);
            }
            else {
                $stmt = pdo::getPDO()->prepare($sql);
            }
            $stmt->execute(array());
            
            //echo $sql;
            
            $tables = [];
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $table_id = $row['id'];
                //$row['tags'] = [];
                $data[$table_id] = $row;
                
                if ($row['key_tag']) {
                    $explode = explode(',', $row['key_tag']);
                    foreach ($explode as $expl) {                    
                        $expl = explode('-', $expl);
                        $tag_table = $expl[0];
                        $tag_id = $expl[1];
                     
                        $tables[$table_id][$tag_table][] = $tag_id;
                    }
                }
            }
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        $t_tables = [];
        foreach ($tables as $index => $table_id) {
            foreach ($table_id as $key => $table) {
                foreach ($table as $id) {
                    $t_tables[$key][$id] = $id;
                }
            }
        }
        
        foreach ($t_tables as $key => $ids) {
            if ($key == 'setka') {
                $field = 'syn';
            }
            else {
                $field = 'name';
            }            
            $sql = "
                SELECT
                    id,
                    $field
                FROM
                    {$key}s
                WHERE
                    id IN (" . implode(',', $ids) . ")
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
                    $t_tables[$key][$row['id']] = $row[$field];
                }
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }   
        }
        
        if (count($tables) != 0) {
            foreach ($tables as $index => $table_id) {
                
                foreach (array(['brand'], ['model_type', 'offer']) as $key_array) {
                    
                    $pass_key = false;
                    
                    foreach ($key_array as $key) {
                        
                        if (isset($table_id[$key])) {                                                         
                            foreach ($table_id[$key] as $id) {
                               $data[$index]['tags'] .= "{$t_tables[$key][$id]},";
                               $tables[$index][$key]['tags'][$id] = $t_tables[$key][$id];
                            }
                            
                            $pass_key = true;
                        }
                    }
                    
                    if (!$pass_key) {    
                        $data[$index]['tags'] .= "NULL,";  
                    }
                }
                
                $data[$index]['tags'] = trim($data[$index]['tags'], ',');
            }
        }
        
        //echo count($data);    
        
        $datatable = [];
        $number = 0;
        
        if (count($data) != 0) {
            foreach ($data as $index => $row) {
                $fields = [];
                foreach ($row as $key => $cell) { 
                    $cell_value = $cell;
                    $datatable[$number][] = $cell_value;
                    $fields[] = $key;
                }
                
                $row['campaigns'] = trim($row['campaigns'], ',');
                
                $datatable[$number]['DT_RowData']['fields'] = $fields; 
                $datatable[$number]['DT_RowData']['no_active'] = $row['no_active'];
                $datatable[$number]['DT_RowData']['data-id'] = $row['id'];
                
                if (!empty($tables[$index])) {
                    foreach($tables[$index] as $tag_table => $values) {
                        $datatable[$number]['DT_RowData']['tags'][$tag_table] = $tables[$index][$tag_table]['tags'];
                    }            
                }
                else {
                    $datatable[$number]['DT_RowData']['tags'] = [];
                }
                
                
                
                $number++;
            }
        }        
        unset($data);
        
        if ($save == '1') {
            $file_name = '/upload/csv/'.$file.'.csv';
            $main_file = \DOCUMENT_ROOT. $file_name;
            $str = '';
            //$header = ['id', 'name', 'tags', 'marker', 'campaigns'];
            //$str .= implode(';', $header).PHP_EOL; 
            foreach ($datatable as $number => $row) {
                
                unset($row[4], $row[5], $row[6], $row[7]);
                                
                $mas_str = [];
                foreach ($row as $index => $cell) {
                    if (\is_numeric($index)) {
                        $mas_str[] = $cell;
                    }
                }
                $str .= implode(';', $mas_str).PHP_EOL; 
            }
            file_put_contents($main_file, iconv('utf-8', 'windows-1251', $str), FILE_APPEND | LOCK_EX);
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
    
    public static function addNewKey() {
        $new_key_id = false;
        
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();
        
        $sql = "
            INSERT INTO `keys`
                (name)
            VALUES
                ('Новый ключ')
        ";
        try {
            if (self::$CIBA2) {
                $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                $stmt = pdo::getCiba2Pdo()->prepare($sql);
                $stmt->execute(array());
                $new_key_id = pdo::getCiba2Pdo()->lastInsertId();
            }
            else {
                $stmt = pdo::getPDO()->prepare($sql);
                $stmt->execute(array());
                $new_key_id = pdo::getPDO()->lastInsertId();
            }
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }
        
        return $new_key_id ? $new_key_id : false;
    }
    
    public function saveName($data) {
        $data = is_array($data) ? $data : [];
        if (count($data) != 0) {
            if (is_numeric($data['id']) && isset($data['name'])) {
                $sql = "
                    UPDATE
                        `keys`
                    SET
                        keys.name = '{$data['name']}'
                    WHERE
                        keys.id = {$data['id']}
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
                return $data['name'];
            }
        }        
    }
    
    public function saveMarker($data) {
        $data = is_array($data) ? $data : [];
        if (count($data) != 0) {
            if (is_numeric($data['id']) && isset($data['marker'])) {
                $sql = "
                    UPDATE
                        `keys`
                    SET
                        keys.marker = '{$data['marker']}'
                    WHERE
                        keys.id = {$data['id']}
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
                return $data['marker'];
            }
        }        
    }
    
    public function selectKeysTags($keys_data) {
        $keys_data = is_array($keys_data) ? $keys_data : [];

        if (count($keys_data) != 0) {

            $q = $keys_data['q'];
            $page_limit = $keys_data['page_limit'];
            $page = $keys_data['page']; 
            $sira = ($page-1) * $page_limit;

            $items = [];
            $total = 0;
            $where = '';

            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();

            $tables = [];
            $sql = "
                SELECT
                    id,
                    name,
                    rus
                FROM
                    source_tag_vars
                ORDER BY
                    id
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
                    $tables[$row['id']] = ['name' => $row['name'], 'rus' => $row['rus']];
                }                    
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }

            $tags = [];
            $field = "";
            $sql = "";
            foreach ($tables as $id => $table) {
                if ($table['name'] == 'setka') {
                    $field = 'syn';
                }
                else {
                    $field = 'name';
                }
                $sql .= "
                    SELECT   
                        $id AS table_id,
                        '{$table['rus']}' AS tag,
                        id,
                        $field AS name,
                        '{$table['name']}' AS tag_name
                    FROM
                        {$table['name']}s
                ";
                if ($q) {
                    $sql .= "
                        WHERE
                            $field LIKE '%$q%'
                    ";
                }
                else {
                    $sql .= "
                        WHERE
                            1
                    ";
                }                    
                if ($table['name'] === 'worker') {
                    $sql .= "
                        AND $field LIKE '%ебмастер%'
                    ";
                }
                if (self::$CIBA2 && $table['name'] === 'model_type') {
                    $sql .= "                        
                        AND organization_id IS NULL
                    ";
                }
                if (self::$CIBA2 && $table['name'] === 'brand') {
                    $sql .= "                        
                        AND organization_id IS NULL
                        AND (ru_name IS NOT NULL AND ru_name != '')
                        AND id NOT IN (2276)
                    ";
                }
                $sql .= "
                    UNION ALL
                ";
            }
            // to clean union
            $sql .= "
                SELECT
                    -1 AS table_id, 
                    -1 AS tag,
                    -1 AS id,
                    -1 AS name,
                    -1 AS tag_name
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
                    $tags[] = $row;
                }          
                unset($tags[count($tags)-1]);
                $count = count($tags);
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            
            $total = count($tags);
            $tags = array_slice($tags, $sira, $page_limit);

            $i = 0;
            $swap = false;
            foreach ($tags as $tag) {
                if ($swap != $tag['table_id']) {                        
                    $items[$i] = ['id' => "{$tag['tag_name']}", 'text' => $tag['tag'], 'children' => []];
                    $items[$i]['children'][] = ['id' => "{$tag['tag_name']}-{$tag['id']}", 'text' => $tag['name']];
                    $swap = $tag['table_id'];                        
                    $i++;
                }
                else {
                    $items[$i-1]['children'][] = ['id' => "{$tag['tag_name']}-{$tag['id']}", 'text' => $tag['name']];
                }             
            }
            
            $result = [
                'incomplete_results' => false,
                'items' => $items,
                'total' => $total  
            ];

            return $result;              
        }
    }
    
    public static function getKeysTags($args) {
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();

        $tags = [];
        $key_id = isset($args['key_id']) ? (int) $args['key_id'] : false;
        
        $all_tags = isset($args['all_tags']) ? $args['all_tags'] : false;
        if (is_numeric($key_id)) {
            if ($key_id != 0) {
                $table_tags = [];
                $sql = "
                    SELECT
                        id_type,
                        name_type
                    FROM
                        key_tags
                    WHERE
                        key_id = $key_id
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
                        $table_tags[$row['name_type']][] = $row['id_type'];
                    }                    
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
            }
            else {
                $table_tags = $all_tags;
            }

            $field = "";
            $sql = "";
            foreach ($table_tags as $table => $ids) {
                if ($table == 'setka') {
                    $field = 'syn';
                }
                else {
                    $field = 'name';
                }
                $sql .= "
                    SELECT   
                        id,
                        $field AS name,
                        '$table' AS table_name
                    FROM
                        {$table}s
                    WHERE
                        id IN (" . implode(',', $ids) . ")

                    UNION ALL

                ";
            }
            $sql .= "
                SELECT
                    -1 AS id,
                    -1 AS name,
                    -1 AS table_name
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
                    $tags[] = ['id' => "{$row['table_name']}-{$row['id']}", 'text' => $row['name']];
                }          
                unset($tags[count($tags)-1]);
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }

        }
        return $tags;
    }
    
    public function saveKeysTags(array $keys_data) : string {
        $answer = '';
        if (count($keys_data) != 0) {
            $key_id = $keys_data['id'] ?? false;
            $key_tags = $keys_data['tags'] ?? false;
            
            if (is_numeric($key_id)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    DELETE FROM
                        key_tags
                    WHERE
                       key_id = {$key_id}
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
                
                if ($key_tags) {
                    $sql = "INSERT INTO key_tags (name_type, id_type, key_id) VALUES ";
                    foreach ($key_tags as $tag) {
                        $table_to_id = explode('-', $tag);                    
                        $name_type = $table_to_id[0];
                        $id_type = $table_to_id[1];                    
                        $sql .= "('{$name_type}', {$id_type}, {$key_id}),";
                    }
                    $sql = trim($sql, ',');
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
                }
                
                $answer = 'success';
            }
        }
        return $answer;
    }
    
     public static function deleteKey($key_id = false) {        
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();
        $answer = 'error';

        if (is_numeric($key_id)) {
          
            $campaign_ids = false;

            $sql = "
                SELECT 
                    key_to_campaigns.id AS id
                FROM
                    key_to_campaigns
                WHERE
                    key_to_campaigns.key_id=:key_id 
            ";
            try {
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }
                $stmt->execute(['key_id' => $key_id]);                    
                $campaign_ids = $stmt->fetch(\PDO::FETCH_COLUMN);
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }

            if (!$campaign_ids) {
                $sql = "DELETE FROM `keys` WHERE id=:id";

                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(['id' => $key_id]);
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $sql = "DELETE FROM `key_tags` WHERE key_id=:key_id";

                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(['key_id' => $key_id]);
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $answer = 'Успешно: ключ удален!';                                                                               
            }
            else {
                $sql = "
                    UPDATE
                        `keys`
                    SET
                        keys.no_active = 1
                    WHERE
                        id=:id
                ";
                try {
                    if (self::$CIBA2) {
                        $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                        $stmt = pdo::getCiba2Pdo()->prepare($sql);
                    }
                    else {
                        $stmt = pdo::getPDO()->prepare($sql);
                    }
                    $stmt->execute(['id' => $key_id]);
                    $stmt = null;
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $answer = 'Успешно: ключ деактивирован из-за связки с кампанией!';
            }
                                                            
        }

        return $answer;
    }
    
    public static function enableKey($key_id = false) {
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();
        $answer = 'error';

        if (is_numeric($key_id)) {
            
            $sql = "
                UPDATE
                    `keys`
                SET
                    keys.no_active = 0
                WHERE
                    id=:id
            ";
            try {
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }
                $stmt->execute(['id' => $key_id]);
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }

            $answer = 'Успешно: запись активирована!';
        }

        return $answer;
    }
    
    public static function getCampaigns($key_id = false) {
        $data = [];
        if (is_numeric($key_id)) {
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $sql = "
                
                SELECT /*+ MAX_EXECUTION_TIME(30000) */
                    camp.id,
                    camp.name,
                    '' AS tags,
                    camp.state,
                    camp.subsource,
                    camp.suffics,
                    camp.active,
                    camp.source_id,
                    camp.region_id,
                    camp.table_id,
                    campaign_tags.name_type AS tag_table,
                    campaign_tags.id_type AS tag_id                    
                    
                FROM (
                
                SELECT direct AS id,
                    campaigns.name,
                    state,
                    IF (parent.name IS NOT NULL, CONCAT(parent.name, ' - ', sources.name), sources.name) AS subsource,
                    0 AS suffics,
                    IF (campaigns.state = 'ON', 0, 1) AS active,
                    sources.id AS source_id,
                    sources.region_id,
                    campaigns.id AS table_id
                FROM
                    campaigns
                LEFT JOIN sources ON 
                        sources.id = campaigns.source_id
                LEFT JOIN sources AS parent ON
                        sources.parent = parent.id
                LEFT JOIN key_to_campaigns ON
                        key_to_campaigns.campaign_id = campaigns.id 
                WHERE
                    (key_to_campaigns.key_id = {$key_id}) 
            ) AS camp
            
            LEFT JOIN campaign_tags ON
                camp.table_id = campaign_tags.campaign_id AND camp.suffics = campaign_tags.suffics
            
            ORDER BY 
                camp.active ASC
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
                
                $tables = [];
                $subname_field = self::$CIBA2 ? 'subnls_source' : 'subsource';
                                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    
                    $table_id = $row['suffics'] . '-' . $row['id'];
                    $row['subsource'] = $row[$subname_field];
                    $row['tags'] = [];
                    $data[$table_id] = $row;
                    if ($row['tag_table'] != '' && !is_null($row['tag_table'])) {
                         $tables[$table_id][$row['tag_table']][] = $row['tag_id'];
                    }
                                        
                }
                
                $stmt = null;

                foreach ($tables as $index => $table_id) {
                    foreach ($table_id as $key => $table) {
                        $ids = [];
                        foreach ($table as $id) {
                            $ids[] = $id;
                        }
                        if (count($ids) != 0) {
                            $names = [];
                            if ($key == 'setka') {
                                $field = 'syn';
                            }
                            else {
                                $field = 'name';
                            }
                            if (!is_null($key) && $key != '') {
                                $sql = "
                                    SELECT
                                        id,
                                        $field
                                    FROM
                                        {$key}s
                                    WHERE
                                        id IN (" . implode(',', $ids) . ")
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
                                        $data[$index]['tags'][] = ['tag_id' => $row['id'], 'tag_name' => $key, 'text' => $row[$field]];
                                    }
                                    $stmt = null;
                                }
                                catch (PDOException $e) {
                                    print $e->getMessage();
                                }
                            }                        
                        }
                    }
                }
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
        }
        return count($data) != 0 ? $data : [];
    }  
}