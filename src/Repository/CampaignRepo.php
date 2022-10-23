<?php

namespace App\Repository;

use framework\pdo;
use framework\load;
use framework\tools;
use PDOException;



class CampaignRepo {
    private static $CIBA2;
    
    private static function setCIBA2() {        
        self::$CIBA2 = true;
    }
    
    
    public static function getCampaigns($args = []) {
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
        
        $active = isset($args['active']) ? $args['active'] : 0;
        $suffics = isset($args['suffics']) ? $args['suffics'] : -1;
        
        $no_partner = isset($args['no_partner']) ? $args['no_partner'] : false;
        
        $count_column = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                COUNT(*) AS count_rows
        ";
        
        $select = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                    camp.table_id,
                    camp.id,
                    camp.name,
                    '' AS tags,
                    camp.state,
                    camp.subsource AS source_name,
                    camp.account,
                    IF (camp.count_keys IS NULL, 0, camp.count_keys) AS count_keys,
                    camp.suffics,
                    camp.active,
                    camp.source_id,
                    camp.region_id,
                    camp.account_id,
                    campaign_tags.campaign_tag                        
        ";
        
        $filter_tags = "";
        $tag_join = "LEFT JOIN";
                
        if ($no_partner) {
            $filter_tags .= " 
                WHERE (
                   campaign_tags.color = 0 AND campaign_tags.action = 1
                )  
            ";
            $tag_join = "INNER JOIN";
        }
        
        $from = "            
              FROM (
                
                SELECT direct AS id,
                    campaigns.name,
                    state,
                    IF (parent.name IS NOT NULL, CONCAT(parent.name, ' - ', sources.name), sources.name) AS subsource,
                    0 AS suffics,
                    IF (campaigns.state = 'ON', 0, IF (campaigns.state = 'ARCHIVED', 2, 1)) AS active,
                    sources.id AS source_id,
                    sources.region_id,
                    campaigns.id AS table_id,
                    ya_webmasters.name AS account,
                    ya_webmasters.id AS account_id,
                    key_table.count_keys AS count_keys
                FROM
                    campaigns
                LEFT JOIN sources ON 
                        sources.id = campaigns.source_id
                LEFT JOIN sources AS parent ON
                        sources.parent = parent.id
                LEFT JOIN tokens ON
                        campaigns.token_id = tokens.id
                LEFT JOIN ya_webmasters ON
                        tokens.ya_webmaster_id = ya_webmasters.id
                
                LEFT JOIN (
                    SELECT
                        key_to_campaigns.campaign_id,
                        COUNT(*) AS count_keys
                    FROM
                        key_to_campaigns
                    GROUP BY
                        key_to_campaigns.campaign_id
                ) AS key_table ON key_table.campaign_id = campaigns.id                
                        
                UNION ALL
                
                SELECT adword AS id,
                    ad_campaigns.name,
                    state,
                    IF (parent.name IS NOT NULL, CONCAT(parent.name, ' - ', sources.name), sources.name) AS subsource,
                    1 AS suffics,
                    IF (ad_campaigns.state = 'ENABLED', 0, 1) AS active,
                    sources.id AS source_id,
                    sources.region_id,
                    ad_campaigns.id AS table_id,
                    '' AS account,
                    0 AS account_id,
                    0 AS count_keys
                FROM
                    ad_campaigns
                LEFT JOIN sources ON 
                        sources.id = ad_campaigns.source_id
                LEFT JOIN sources AS parent ON
                        sources.parent = parent.id
                                          
            ) AS camp
                
            $tag_join (
		          SELECT 
                    GROUP_CONCAT(CONCAT(name_type , '-', id_type, '-', color, '-', action)) AS campaign_tag, 
                    campaign_id,
                    suffics
                  FROM 
                    campaign_tags $filter_tags
                  GROUP BY campaign_id, suffics
            ) AS campaign_tags ON campaign_tags.campaign_id = camp.table_id AND campaign_tags.suffics = camp.suffics 
        ";
        
        $where = "
            WHERE
                1
                
        ";
        
        $filter = "";
        
        if (!empty($filters['regions'])) {
            $ids = array_map('intval', $filters['regions']);
            $filter .= " AND (`camp`.`region_id` IN (" . implode(',', $ids) . ")) ";
            unset($filters['regions']);
        }
        
        if (!empty($filters['ya_webmasters'])) {
            $ids = array_map('intval', $filters['ya_webmasters']);
            $filter .= " AND (`camp`.`account_id` IN (" . implode(',', $ids) . ")) ";
            unset($filters['ya_webmasters']);
        }
        
        $inner_join_filters = "";
        if (!empty($filters)) {
            foreach ($filters as $filt => $ids) {
                $ids = array_map('intval', $ids);
                if (!in_array($filt, ['regions', 'ya_webmasters'])) {
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
                                (source_tags.name_type = '{$filt_name}' AND source_tags.id_type IN (" . implode(',', $ids) . "))
                        ) AS {$filt_name}_table ON {$filt_name}_table.source_id = camp.source_id
                    ";
                }
            } 
        }
        
        if ($active != -1) {
             $filter .= "
                AND (
                    camp.active = {$active}
                )
            ";  
        }
        
        if ($suffics != -1) {
             $filter .= "
                AND (
                    camp.suffics = {$suffics}
                )
            ";  
        }
        
        if ($searchValue) {
            $filter .= "
                AND (
                    camp.id LIKE '%{$searchValue}%'
                    OR camp.subsource LIKE '%{$searchValue}%'
                    OR camp.name LIKE '%$searchValue%'
                    OR camp.table_id LIKE '%{$searchValue}%'
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
            $limit = "";
        }
        
        $sql = "$select $from $inner_join_filters $where $filter $order $limit";

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
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $table_id = $row['suffics'] . '-' . $row['id'];
                //$row['tags'] = [];
                $data[$table_id] = $row;
                
                if ($row['campaign_tag']) {
                    $explode = explode(',', $row['campaign_tag']);
                    foreach ($explode as $expl) {                    
                        $expl = explode('-', $expl);
                        $tag_table = $expl[0];
                        $tag_id = $expl[1];
                     
                        $tables[$table_id][$tag_table][] = [$tag_id, $expl[2], $expl[3]];
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
                    $t_tables[$key][$id[0]] = $id[0];
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
                foreach ($table_id as $key => $table) {
                    /*$ids = [];
                    foreach ($table as $id) {
                        $ids[] = $id;
                    }
                    if (count($ids) != 0) {
                        $names = [];
                        $tag_ids = [];
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
                                    $names[] = $row[$field];
                                    $tag_ids[] = $row['id'];
                                }
                                $stmt = null;
                            }
                            catch (PDOException $e) {
                                print $e->getMessage();
                            }
                            if (count($names) != 0) {
                                $tables[$index][$key]['tags'] = array_combine($tag_ids, $names);
                                $names = implode(',', $names);
                                $data[$index]['tags'] .= "$names,";
                            }
                        }                        
                    }*/
                    foreach ($table as $id) {
                       $data[$index]['tags'] .= "{$t_tables[$key][$id[0]]},";
                       $tables[$index][$key]['tags'][$id[0]] = [$t_tables[$key][$id[0]], $id[1], $id[2]];
                    }
                }
                $data[$index]['tags'] = trim($data[$index]['tags'], ',');
            }
        }
        
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
                
                $datatable[$number]['DT_RowData']['fields'] = $fields; 
                $datatable[$number]['DT_RowData']['nls_source_id'] = $row['nls_source_id'];
                $datatable[$number]['DT_RowData']['suffics'] = $row['suffics'];
                $datatable[$number]['DT_RowData']['active'] = $row['active'];
                $datatable[$number]['DT_RowData']['data-id'] = $row['table_id'];
                
                if (!empty($tables[$index])) {
                    foreach($tables[$index] as $tag_table => $values) {
                        $datatable[$number]['DT_RowData']['tags'][$tag_table] = $tables[$index][$tag_table]['tags'];
                    }            
                }
                else {
                    $datatable[$number]['DT_RowData']['tags'] = [];
                }
                
                unset($datatable[$number][9], $datatable[$number][10], $datatable[$number][11], $datatable[$number][12], $datatable[$number][13]);
                
                $number++;
            }
        } 
                
        unset($data);
        
        if ($save == '1') {
            $file_name = '/upload/csv/'.uniqid().'.csv';
            $main_file = \DOCUMENT_ROOT. $file_name;
            $str = '';
            $header = ['tid', 'id', 'Название', 'Теги', 'Статус', 'Источник', 'Аккаунт', 'Ключи', 'Канал'];
            $str .= implode(';', $header).PHP_EOL; 
            foreach ($datatable as $number => $row) {
                $mas_str = [];
                foreach ($row as $index => $cell) {
                    if (\is_numeric($index)) {
                        
                        if ($index == 8) {
                            if (!$cell)
                                $cell = 'YD';
                            else
                                $cell = 'GA';                            
                        }                            
                                
                        $mas_str[] = $cell;
                    }
                }
                $str .= implode(';', $mas_str).PHP_EOL; 
            }
            file_put_contents($main_file, iconv('utf-8', 'windows-1251', $str));
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
    
    public static function getLidRequests($args = []) {
        
        $select = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                    regions.name AS region_name,
                    '' AS tags,
                    IF (parent_offers.id, parent_offers.name, offers.name) AS parent_offer_name,
                    IF (parent_offers.id, offers.name, '') AS offer_name,
                    lid_requests.name_type,
                    lid_requests.id_type,
                    lid_requests.id
        ";
        
        $from =  "FROM 
                    lid_requests
                  INNER JOIN regions ON
                    lid_requests.region_id = regions.id
                  INNER JOIN offers ON
                    lid_requests.offer_id = offers.id
                  LEFT JOIN offers parent_offers ON
                    offers.parent = parent_offers.id 
                  ";
                  
        $where = "
            WHERE
                1                
        ";
        
        $filter = "";   
        
        $filter .= "
                AND (
                    lid_requests.action = 0
                )
            ";        
        
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();
        
        $sql = "$select $from $where $filter";

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
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $table_id = $row['id'];
                //$row['tags'] = [];
                $data[$table_id] = $row;
                
                $tables[$table_id][$row['name_type']][] = $row['id_type'];               
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
        
        $a = json_decode(tools::request_api(['op' => 'v3', 'args' => ['mode' => 'decode', 'array' => $t_tables, 'to_tag' => 1]]), true);
        $t_tables = $a['answer'];
        
        /*foreach ($t_tables as $key => $ids) {
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
        }*/

        if (count($tables) != 0) {
            foreach ($tables as $index => $table_id) {
                foreach ($table_id as $key => $table) {
                    foreach ($table as $id) {
                       $data[$index]['tags'] .= "{$t_tables[$key][$id]},";
                    }
                }
                $data[$index]['tags'] = trim($data[$index]['tags'], ',');
            }
        }

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
                
                unset($datatable[$number][4], $datatable[$number][5], $datatable[$number][6]);
                
                $number++;
            }
        }           
                
        $file_name = '/upload/csv/'.uniqid().'.csv';
        $main_file = \DOCUMENT_ROOT. $file_name;
        $str = '';
        $header = ['Регион', 'Тег', 'Оффер', 'Подоффер'];
        $str .= implode(';', $header).PHP_EOL; 
        foreach ($datatable as $number => $row) {
            $mas_str = [];
            foreach ($row as $index => $cell) {
                $mas_str[] = $cell;
            }
            $str .= implode(';', $mas_str).PHP_EOL; 
        }
        file_put_contents($main_file, iconv('utf-8', 'windows-1251', $str));
        return array("data" => $file_name);
    }
    
    public function selectCampaignsTags($campaigns_data) {
        $campaigns_data = is_array($campaigns_data) ? $campaigns_data : [];

        if (count($campaigns_data) != 0) {

            $q = $campaigns_data['q'];
            $page_limit = $campaigns_data['page_limit'];
            $page = $campaigns_data['page']; 
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
    
    public static function getCampaignsTags($args) {
        self::setCIBA2();
        if (self::$CIBA2) pdo::clearPdo();

        $tags = [];
        $campaign_id = isset($args['campaign_id']) ? (int) $args['campaign_id'] : false;
        $suffics = isset($args['suffics']) ? (int) $args['suffics'] : false;
        
        $all_tags = isset($args['all_tags']) ? $args['all_tags'] : false;
        if (is_numeric($campaign_id)) {
            if ($campaign_id != 0) {
                $table_tags = [];
                $sql = "
                    SELECT
                        id_type,
                        name_type
                    FROM
                        campaign_tags
                    WHERE
                        campaign_id = $campaign_id AND
                        suffics = $suffics
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
    
    public function saveCampaignsTags(array $campaigns_data) : string {
        $answer = '';
        if (count($campaigns_data) != 0) {
            $campaign_id = $campaigns_data['id'] ?? false;
            $suffics = $campaigns_data['suffics'] ?? 0;
            $campaign_tags = $campaigns_data['tags'] ?? false;
            
            if (is_numeric($campaign_id)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                $sql = "
                    DELETE FROM
                        campaign_tags
                    WHERE
                        campaign_id = {$campaign_id} AND suffics = {$suffics}
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
                
                if ($campaign_tags) {
                    $sql = "INSERT INTO campaign_tags (name_type, id_type, campaign_id, suffics) VALUES ";
                    foreach ($campaign_tags as $tag) {
                        $table_to_id = explode('-', $tag);                    
                        $name_type = $table_to_id[0];
                        $id_type = $table_to_id[1];                    
                        $sql .= "('{$name_type}', {$id_type}, {$campaign_id}, {$suffics}),";
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
    
    public function selectSourceName(array $source_data) : array {
        if (count($source_data) != 0) {
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $q = $source_data['q'];
            $page_limit = $source_data['page_limit'];
            $page = $source_data['page'];
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
                        sources.id,
                        IF (parent.name IS NOT NULL, CONCAT(parent.name, ' - ', sources.name), sources.name) AS name
                ";
                
                $from = "
                    FROM
                        sources
                    LEFT JOIN sources AS parent ON
                        sources.parent = parent.id
                ";
                
                $where = "
                    WHERE
                        1
                ";
                
                if ($q != '') {
                    $where .= "
                        AND (
                            sources.name LIKE '%$q%'
                            OR sources.id LIKE '%$q%'
                        )
                    ";
                }
                
                $order = "
                    ORDER BY
                        sources.name ASC
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
                        $row['name'] = $row['name'] . ' ' .$row['id'];
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
    
    public static function selectCurrentSource(array $args) : array {
        $option = [];
        
        if (count($args) != 0) {
            $option_data = $args['option_data'] ?? false;
            
            $option_data = explode(' - ', $option_data);
            
            if (is_string($option_data)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                if (!empty($option_data[1])) {
                    $sql = "
                        SELECT
                            sources.id,
                            CONCAT(parent.name, ' - ', sources.name) AS name,
                        FROM
                            sources
                        LEFT JOIN sources AS parent ON
                            sources.parent = parent.id 
                        WHERE
                            sources.name = '{$option_data[1]} AND parent.name = '{$option_data[0]}'
                    ";
                } else {
                    $sql = "
                        SELECT
                            id,
                            name
                        FROM
                            sources
                        WHERE
                            name = '{$option_data[0]}'
                    ";
                }
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
    
     public function saveSourceName(array $source_data) : string {
        $answer = '';
        
        if (count($source_data) != 0) {
            
            $campaign_id = $source_data['id'] ?? false;
            $source_id = $source_data['source_id'] ?? false;
            $suffics = $source_data['suffics'] ?? false;
            
            if (is_numeric($campaign_id) && is_numeric($source_id)) {
                self::setCIBA2();
                if (self::$CIBA2) pdo::clearPdo();
                
                if ($suffics) 
                    $table = 'ad_campaigns';
                else
                    $table = 'campaigns';
                    
                $sql = "
                    UPDATE
                        {$table}
                    SET
                        source_id = {$source_id}
                    WHERE
                        id = {$campaign_id}
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
                        IF (parent.name IS NOT NULL, CONCAT(parent.name, ' - ', sources.name), sources.name) AS name
                    FROM
                        sources
                    LEFT JOIN sources AS parent ON
                        sources.parent = parent.id
                    WHERE
                        sources.id = {$source_id}
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
                    $source = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $source = $source['name'];
                }
                catch (PDOException $e) {
                    print $e->getMessage();
                }
                
                $answer = $source;
            }
        }
        
        return $answer;
    }
    
   public static function getKeys($campaign_id = false) {
        $data = [];
        if (is_numeric($campaign_id)) {
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            $sql = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                        keys.id,
                        keys.name, 
                        '' AS tags,
                        keys.marker,           
                        key_tags.name_type AS tag_table,
                        key_tags.id_type AS tag_id
                        
                    FROM 
                        `keys`                
                    LEFT JOIN key_tags ON
                        keys.id = key_tags.key_id
                        
                    LEFT JOIN key_to_campaigns ON
                        key_to_campaigns.key_id = keys.id    
                        
                    WHERE
                        key_to_campaigns.campaign_id = {$campaign_id}
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
                                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {                    
                    $table_id = $row['id'];
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