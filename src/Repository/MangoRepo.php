<?php

namespace App\Repository;

use framework\pdo;
use framework\load;
use framework\tools;
use PDOException;



class MangoRepo {
    private static $CIBA2;
    
    private static function setCIBA2() {        
        self::$CIBA2 = true;
    }
    
    
    public static function getMangos($args = []) {
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
        
        $datepicker = array_key_exists('datepicker', $args) ? $args['datepicker'] : false;
        if ($datepicker) {
            $date_start = array_key_exists('start', $datepicker) ? $datepicker['start'] : false;
            $date_end = array_key_exists('end', $datepicker) ? $datepicker['end'] : false;
            
            $date_start = $date_start ? date("Y-m-d H:i:s", strtotime($date_start)) : date("Y-m-d 00:00:00");
            $date_end = $date_end ? date("Y-m-d H:i:s", strtotime($date_end)) : date("Y-m-d 23:59:59");
        }
        
        $dt_filt_red = isset($args['dt_filt_red']) ? $args['dt_filt_red'] : false;
        $dt_filt_yellow = isset($args['dt_filt_yellow']) ? $args['dt_filt_yellow'] : false;
        $dt_filt_blue = isset($args['dt_filt_blue']) ? $args['dt_filt_blue'] : false;
        
        $type = isset($args['type']) ? $args['type'] : -1;
        $status = isset($args['status']) ? $args['status'] : -1;
        $channel = isset($args['channel']) ? $args['channel'] : -1;
        
        $no_scenario = isset($args['no_scenario']) ? $args['no_scenario'] : false;
        
        $count_column = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                COUNT(*) AS count_rows
        ";
        
        $select = "SELECT /*+ MAX_EXECUTION_TIME(30000) */
                    man.id,
                    man.name, 
                    man.type AS type,
                    scenarios.name AS scenario,
                    IF(man.activation IS NULL, '', DATE_FORMAT(man.activation, '%d.%m.%Y')) AS activation,                                      
                    man.subsource,
                    setka_tags.setka_syn, 
                    man.region,
                    man.channel,
                    IF(count_calls.count IS NULL, 0, count_calls.count) AS count,
                    IF(direct.sold IS NULL, 0, direct.sold) + IF(resale.sold IS NULL, 0, resale.sold) + IF(resale_cashback.sold IS NULL, 0, resale_cashback.sold) AS sold,
                    IF(direct.summ IS NULL, 0, direct.summ) + IF(resale.summ IS NULL, 0, resale.summ) + IF(resale_cashback.summ IS NULL, 0, resale_cashback.summ) AS expense,                  
                    '' AS analytics,
                    man.mango_upload_id,
                    man.activation AS act,
                    man.region_id AS region_id,
                    man.source_id AS source_id,
                    man.no_active AS no_active,
                    man.channel_id                
        ";
        
        $from = "
            FROM (
                
                SELECT m.id,
                m.name,
                m.type AS type,               
                mango_uploads.activation,
                m.subsource,
                m.region,
                m.channel,
                mango_uploads.id AS mango_upload_id,
				m.region_id,
                m.source_id,
                m.no_active,
                m.channel_id
                				
                FROM
                
                (SELECT
                    mangos.id AS id,
                    IF (navy_dt_phones.id IS NOT NULL, 2, IF (multi_dt_phones.id IS NOT NULL, 1, 0)) as type,
                    mangos.name,
                    IF (parent.name IS NOT NULL, CONCAT(parent.name, ' - ', sources.name), sources.name) AS subsource,
                    regions.name AS region,
                    channels.name AS channel,
                    regions.id AS region_id,
                    sources.id AS source_id,
                    sources.no_active AS no_active,
                    channels.id AS channel_id
                FROM 
                    mangos
                INNER JOIN sources ON 
                        sources.id = mangos.source_id
                LEFT JOIN sources AS parent ON
                        sources.parent = parent.id
                LEFT JOIN regions ON 
                        sources.region_id = regions.id   
                LEFT JOIN channels ON
                        channels.id = mangos.channel_id
                LEFT JOIN navy_dt_phones ON 
                        navy_dt_phones.mango_id = mangos.id
                LEFT JOIN multi_dt_phones ON 
                        multi_dt_phones.mango_id = mangos.id                        
                        
                UNION ALL
                
                SELECT
                    mangos.id AS id,
                    3 AS type,
                    mangos.name,
                    '' AS subsource,
                    '' AS region,
                    NULL AS channel,
                    NULL AS region_id,
                    NULL AS source_id,
                    NULL AS no_active,
                    NULL AS channel_id
                FROM 
                    resale_dt_phones
                INNER JOIN mangos ON 
                        resale_dt_phones.mango_id = mangos.id
                        
                UNION ALL
                
                SELECT
                    mangos.id AS id,
                    3 AS type,
                    mangos.name,
                    '' AS subsource,
                    '' AS region,
                    NULL AS channel,
                    NULL AS region_id,
                    NULL AS source_id,
                    NULL AS no_active,
                    NULL AS channel_id
                FROM 
                    out_dt_phones
                INNER JOIN mangos ON 
                        out_dt_phones.mango_id = mangos.id
                        
                UNION ALL
                
                SELECT
                    mangos.id AS id,
                    2 AS type,
                    mangos.name,
                    '' AS subsource,
                    '' AS region,
                    NULL AS channel,
                    NULL AS region_id,
                    NULL AS source_id,
                    NULL AS no_active,
                    NULL AS channel_id
                FROM 
                    navy_dt_phones
                INNER JOIN mangos ON 
                        navy_dt_phones.mango_id = mangos.id 
                WHERE (mangos.source_id IS NULL OR mangos.source_id = 0)
                
                UNION ALL
                
                SELECT
                    mangos.id AS id,
                    1 AS type,
                    mangos.name,
                    '' AS subsource,
                    '' AS region,
                    NULL AS channel,
                    NULL AS region_id,
                    NULL AS source_id,
                    NULL AS no_active,
                    NULL AS channel_id
                FROM 
                    multi_dt_phones                    
                INNER JOIN mangos ON 
                        multi_dt_phones.mango_id = mangos.id 
                WHERE (mangos.source_id IS NULL OR mangos.source_id = 0)) AS m
                
									LEFT JOIN mango_uploads ON 
											m.name = mango_uploads.name 

            UNION
            
                SELECT NULL,
                mango_uploads.name,
                NULL,
                mango_uploads.activation,
                NULL,
                NULL,
                NULL,
                mango_uploads.id AS mango_upload_id,
                NULL,
                NULL,
                NULL,
                NULL
								
                FROM
                
                (SELECT
                    mangos.id AS id,
                    IF (navy_dt_phones.id IS NOT NULL, 2, IF (multi_dt_phones.id IS NOT NULL, 1, 0)) as type,
                    mangos.name,
                    IF (parent.name IS NOT NULL, CONCAT(parent.name, ' - ', sources.name), sources.name) AS subsource,
                    regions.name AS region,
                    channels.name AS channel,
                    regions.id AS region_id,
                    sources.id AS source_id,
                    sources.no_active AS no_active,
                    channels.id AS channel_id
                FROM 
                    mangos
                INNER JOIN sources ON 
                        sources.id = mangos.source_id
                LEFT JOIN sources AS parent ON
                        sources.parent = parent.id
                LEFT JOIN regions ON 
                        sources.region_id = regions.id
                LEFT JOIN channels ON
                        channels.id = mangos.channel_id
                LEFT JOIN navy_dt_phones ON 
                        navy_dt_phones.mango_id = mangos.id
                LEFT JOIN multi_dt_phones ON 
                        multi_dt_phones.mango_id = mangos.id    
                        
                UNION ALL
                
                SELECT
                    mangos.id AS id,
                    3 AS type,
                    mangos.name,
                    '' AS subsource,
                    '' AS region,
                    NULL AS channel,
                    NULL AS region_id,
                    NULL AS source_id,
                    NULL AS no_active,
                    NULL AS channel_id
                FROM 
                    resale_dt_phones
                INNER JOIN mangos ON 
                        resale_dt_phones.mango_id = mangos.id
                        
                UNION ALL
                
                SELECT
                    mangos.id AS id,
                    3 AS type,
                    mangos.name,
                    '' AS subsource,
                    '' AS region,
                    NULL AS channel,
                    NULL AS region_id,
                    NULL AS source_id,
                    NULL AS no_active,
                    NULL AS channel_id
                FROM 
                    out_dt_phones
                INNER JOIN mangos ON 
                        out_dt_phones.mango_id = mangos.id
                        
                UNION ALL
                
                SELECT
                    mangos.id AS id,
                    2 AS type,
                    mangos.name,
                    '' AS subsource,
                    '' AS region,
                    NULL AS channel,
                    NULL AS region_id,
                    NULL AS source_id,
                    NULL AS no_active,
                    NULL AS channel_id
                FROM 
                    navy_dt_phones
                INNER JOIN mangos ON 
                        navy_dt_phones.mango_id = mangos.id 
                WHERE (mangos.source_id IS NULL OR mangos.source_id = 0)
                
                UNION ALL
                
                SELECT
                    mangos.id AS id,
                    1 AS type,
                    mangos.name,
                    '' AS subsource,
                    '' AS region,
                    NULL AS channel,
                    NULL AS region_id,
                    NULL AS source_id,
                    NULL AS no_active,
                    NULL AS channel_id
                FROM 
                    multi_dt_phones
                INNER JOIN mangos ON 
                        multi_dt_phones.mango_id = mangos.id 
                WHERE (mangos.source_id IS NULL OR mangos.source_id = 0)) AS m
                
									RIGHT JOIN mango_uploads ON 
											m.name = mango_uploads.name WHERE NOT EXISTS (SELECT 1 FROM mango_uploads WHERE mango_uploads.name = m.name) 
            ) AS man
            
            LEFT JOIN (
                SELECT COUNT(*) AS count, calls.mango_id 
                    FROM
                        calls
                    INNER JOIN transactions ON
                        transactions.call_id = calls.id
                    WHERE
                        1
                    AND (calls.timestamp >= '$date_start' AND calls.timestamp <= '$date_end')
                GROUP BY calls.mango_id
            ) AS count_calls ON count_calls.mango_id = man.id
            
            LEFT JOIN ( 
                SELECT uis_scenarios.name, mango_upload_scenarios.mango_upload_id 
                    FROM 
                        mango_upload_scenarios
                    LEFT JOIN uis_scenarios ON
                        uis_scenarios.id = mango_upload_scenarios.uis_scenario_id
                    GROUP BY mango_upload_scenarios.mango_upload_id
                        HAVING COUNT(*) = 1
            ) AS scenarios ON scenarios.mango_upload_id = man.mango_upload_id
            
            LEFT JOIN (
                SELECT ABS(SUM(transactions.summ)) AS summ, COUNT(*) AS sold, calls.mango_id
                    FROM
                        transactions
                    INNER JOIN calls ON
                        calls.id = transactions.call_id      
                    WHERE
                        1
                    AND (transactions.timestamp >= '$date_start' AND transactions.timestamp <= '$date_end') AND transactions.summ != 0
                 GROUP BY calls.mango_id 
            ) AS direct ON direct.mango_id = man.id
            
           LEFT JOIN (
                SELECT ABS(SUM(t2.summ)) AS summ, COUNT(*) AS sold, calls.mango_id
                    FROM
                        transactions AS t1
                    INNER JOIN connectors ON 
                        connectors.a = t1.call_id
                    INNER JOIN transactions AS t2 ON
                        connectors.b_final = t2.call_id
                    INNER JOIN calls ON
                        calls.id = t1.call_id                     
                    WHERE
                        1
                    AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                 GROUP BY calls.mango_id 
            ) AS resale ON resale.mango_id = man.id 
            
           LEFT JOIN (
                SELECT ABS(SUM(t2.summ)) AS summ, COUNT(*) AS sold, calls.mango_id
                    FROM
                        transactions AS t1
                    INNER JOIN cashbacks ON 
                        cashbacks.call_id = t1.call_id    
                    INNER JOIN connectors ON 
                        connectors.a = cashbacks.resale_call_id
                    INNER JOIN transactions AS t2 ON
                        connectors.b_final = t2.call_id
                    INNER JOIN calls ON
                        calls.id = t1.call_id                     
                    WHERE
                        1
                    AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                 GROUP BY calls.mango_id 
            ) AS resale_cashback ON resale_cashback.mango_id = man.id 
            
           LEFT JOIN (
               SELECT setkas.syn AS setka_syn, source_id 
                    FROM
                        source_tags 
                    INNER JOIN sources ON 
                        sources.id = source_tags.source_id
                    INNER JOIN setkas ON 
                        setkas.id = source_tags.id_type
                    WHERE
                        1
                    AND source_tags.name_type = 'setka'
           ) AS setka_tags ON setka_tags.source_id = man.source_id
        ";
        
        $where = "
            WHERE
                1
                
        ";
        
        $filter = "";
        
        if (!empty($filters['regions'])) {
            $ids = array_map('intval', $filters['regions']);
            $filter .= " AND (`man`.`region_id` IN (" . implode(',', $ids) . ")) ";
            unset($filters['regions']);
        }
        
        $inner_join_filters = "";
        if (!empty($filters)) {
            foreach ($filters as $filt => $ids) {
                $ids = array_map('intval', $ids);
                if (!in_array($filt, ['regions'])) {
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
                        ) AS {$filt_name}_table ON {$filt_name}_table.source_id = man.source_id
                    ";
                }
            } 
        }
        
        if ($type != -1) {
             $filter .= "
                AND (
                    man.type = {$type}
                )
            ";  
        }
        
        if ($status != -1) {
            $filter .= 
                " AND (
                    man.no_active = {$status} 
                )
            ";
        }
        
        if ($channel != -1) {
            if ($channel == 0) {
                $filter .= 
                    " AND (
                        man.channel_id IS NULL
                    )
                ";
            }
            else {
                $filter .= 
                    " AND (
                        man.channel_id = {$channel} 
                    )
                ";
            } 
        }
        
        if ($searchValue) {
            $filter .= "
                AND (
                    man.id LIKE '%{$searchValue}%'
                    OR man.subsource LIKE '%{$searchValue}%'
                    OR man.name LIKE '%$searchValue%'
                )
            ";
        }
        
        $filter_or = "";
        
        if ($dt_filt_red) {
            $filter_or .= " 
                OR (
                    man.mango_upload_id IS NULL
                )  
            ";
        }
        
        if ($dt_filt_yellow) {
            $filter_or .= " 
                OR (
                    man.id IS NULL
                )  
            ";
        }
        
        if ($dt_filt_blue) {
            $filter_or .= " 
                OR (
                   (count_calls.count IS NULL OR count_calls.count = 0) AND (man.mango_upload_id IS NOT NULL)
                )  
            ";
        }
        
        if ($no_scenario) {
            $filter_or .= " 
                OR (
                   scenarios.name IS NULL
                )  
            ";
        }
        
        if ($filter_or) $filter .= " AND (0 " . $filter_or . ")";
        
        $order = "";
        if ($columnIndex && $columnSortOrder) {
            if ($columnIndex != 5) {
                $order .= "
                    ORDER BY
                        $columnIndex $columnSortOrder
                ";
            }
            else {
                $order .= "
                    ORDER BY
                        DATE(act) $columnSortOrder
                ";
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
        
        $data = [];
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
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }  
        
        $datatable = [];
        $number = 0;
        $accord_types = ['Статика', 'Динамика', 'Нави', 'Служебный'];
        
        if (count($data) != 0) {
            foreach ($data as $index => $row) {
                $fields = [];
                foreach ($row as $key => $cell) { 
                    $cell_value = $cell;
                    $datatable[$number][] = $cell_value;
                    $fields[] = $key;
                }
                
                $error_value = '';
                $error = 0;
                
                if (!$row['id'] && !$error) {
                    $error_value = 'Есть в UIS, но нет в БД';
                    $error = 1;
                }
                
                if (!$row['mango_upload_id'] && !$error) {
                    $error_value = 'Есть в БД, но нет в UIS';
                    $error = 2;
                }
                    
                if (!$row['count'] && !$error) {
                    $error_value = 'Нет лидов в периоде';
                    $error = 3;
                }
                
                $datatable[$number][2] = $accord_types[$datatable[$number][2]] ?? '';
                    
                foreach ([0 => '0',  2 => 'Нет в БД', 3 => 'Не определен', 4 => 'Нет в UIS', 5 => 'Нет источника', 6 => 'Нет', 7 => 'Нет источника', 8 => 'Нет'] as $key => $val)  {
                    if (!$datatable[$number][$key]) {
                        if ($save != '1')
                            $datatable[$number][$key] = '<span class="disabled">' . $val . '</span>';
                        else
                            $datatable[$number][$key] = $val;
                    }
                    else {
                        if ($key == 5 && $datatable[$number][17]) {
                            if ($save != '1')
                                $datatable[$number][$key] = '<span class="disabled">' . $datatable[$number][$key] . '</span>';
                            else
                                $datatable[$number][$key] = $val;
                        }
                    }    
                }
                
                unset($datatable[$number][13], $datatable[$number][14], $datatable[$number][15], $datatable[$number][16], $datatable[$number][17], $datatable[$number][18]);
                
                $datatable[$number]['DT_RowData']['fields'] = $fields; 
                $datatable[$number]['DT_RowData']['error_value'] = $error_value;
                $datatable[$number]['DT_RowData']['error'] = $error;
                $datatable[$number]['DT_RowData']['data-id'] = $row['id']; 
                
                $number++;
            }
        }        
        unset($data);
        
        if ($save == '1') {
            $file_name = '/upload/csv/'.uniqid().'.csv';
            $main_file = \DOCUMENT_ROOT. $file_name;
            $str = '';
            $header = ['id', 'Номер', 'Тип', 'Сценарий', 'Дата активации', 'Источник', 'Сетка', 'Регион', 'Канал', 'Лидов', 'Продано', 'Доход'];
            $str .= implode(';', $header).PHP_EOL; 
            foreach ($datatable as $number => $row) {
                $mas_str = [];
                foreach ($row as $index => $cell) {
                    if (\is_numeric($index)) {
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
    
    public static function getChart($mango_id = false, $datepicker = false, $tags = []) {
         if (is_numeric($mango_id)) {
            $arr_data = [];
            
            $date_start = array_key_exists('start', $datepicker) ? $datepicker['start'] : false;
            $date_end = array_key_exists('end', $datepicker) ? $datepicker['end'] : false;
            
            $date_start = $date_start ? date("Y-m-d H:i:s", strtotime($date_start)) : date("Y-m-d 00:00:00");
            $date_end = $date_end ? date("Y-m-d H:i:s", strtotime($date_end)) : date("Y-m-d 23:59:59");
            
            $arg_tags = $tags;
            
            $t_arg_tags = [];
            foreach ($tags as $tag) {
                $array = explode('-', $tag);
                $t_arg_tags[$array[0]][] = $array[1];
            }
            
            $tags = $t_arg_tags;
            
            $filter_place = "";
            $filter_nyk = "";
            
            if (isset($tags['place'])) {
                $filter_place = " AND (partner_orders.place_id IN (".implode(',', $tags['place']).")";
                if (in_array(0, $tags['place'])) $filter_place .= " OR partner_orders.place_id IS NULL";
                $filter_place .= ")";
                unset($tags['place']);
            }  
            
            if (isset($tags['new_nyk'])) {
                $filter_nyk = " AND (partner_orders.new_nyk_id IN (".implode(',', $tags['new_nyk']).")";
                if (in_array(0, $tags['new_nyk'])) $filter_nyk .= " OR partner_orders.new_nyk_id IS NULL";
                $filter_nyk .= ")";
                unset($tags['new_nyk']);
            }   
            
            $sql = "SELECT 
                        mangos.name, 
                        IF (navy_dt_phones.id IS NOT NULL, 'Нави', IF (multi_dt_phones.id IS NOT NULL, 'Динамика', IF (resale_dt_phones.id IS NOT NULL OR out_dt_phones.id IS NOT NULL, 'Служебный', 'Статика'))) as type,
                        scenarios.name AS scenario,                         
                        IF (mango_uploads.activation IS NULL, '', DATE_FORMAT(mango_uploads.activation, '%d.%m.%Y')) AS activation,
                        IF (parent.name IS NOT NULL, CONCAT(parent.name, ' - ', sources.name), sources.name) AS subsource,                       
                        regions.name AS regions,
                        channels.name AS channels,                            
                        IF(count_calls.count IS NULL, 0, count_calls.count) AS count,
                        IF(direct.summ IS NULL, 0, direct.summ) + IF(resale.summ IS NULL, 0, resale.summ) + IF(resale_cashback.summ IS NULL, 0, resale_cashback.summ) AS expense,
                        IF(direct.sold IS NULL, 0, direct.sold) + IF(resale.sold IS NULL, 0, resale.sold) + IF(resale_cashback.sold IS NULL, 0, resale_cashback.sold) AS sold
                                                
                        FROM mangos 
                        
                        LEFT JOIN mango_uploads ON 
				                mangos.name = mango_uploads.name
                        INNER JOIN sources ON 
                                sources.id = mangos.source_id
                        LEFT JOIN sources AS parent ON
                                sources.parent = parent.id
                        LEFT JOIN regions ON 
                                sources.region_id = regions.id   
                        LEFT JOIN channels ON
                                channels.id = mangos.channel_id
                        LEFT JOIN resale_dt_phones ON 
                                resale_dt_phones.mango_id = mangos.id
                        LEFT JOIN out_dt_phones ON 
                                out_dt_phones.mango_id = mangos.id
                        LEFT JOIN navy_dt_phones ON 
                                navy_dt_phones.mango_id = mangos.id
                        LEFT JOIN multi_dt_phones ON 
                            multi_dt_phones.mango_id = mangos.id         
                        
                        LEFT JOIN ( 
                            SELECT uis_scenarios.name, mango_upload_scenarios.mango_upload_id 
                                FROM 
                                    mango_upload_scenarios
                                LEFT JOIN uis_scenarios ON
                                    uis_scenarios.id = mango_upload_scenarios.uis_scenario_id
                                GROUP BY mango_upload_scenarios.mango_upload_id
                                    HAVING COUNT(*) = 1
                        ) AS scenarios ON scenarios.mango_upload_id = mango_uploads.id
                        
                        LEFT JOIN (
                            SELECT COUNT(*) AS count, calls.mango_id 
                                FROM
                                    calls
                                INNER JOIN transactions ON
                                    transactions.call_id = calls.id
                                WHERE
                                    1
                                AND (calls.timestamp >= '$date_start' AND calls.timestamp <= '$date_end')
                            GROUP BY calls.mango_id
                        ) AS count_calls ON count_calls.mango_id = mangos.id
                        
                        LEFT JOIN (
                            SELECT ABS(SUM(transactions.summ)) AS summ, COUNT(*) AS sold, calls.mango_id
                                FROM
                                    transactions
                                INNER JOIN calls ON
                                    calls.id = transactions.call_id      
                                WHERE
                                    1
                                AND (transactions.timestamp >= '$date_start' AND transactions.timestamp <= '$date_end') AND transactions.summ != 0
                             GROUP BY calls.mango_id 
                        ) AS direct ON direct.mango_id = mangos.id
                        
                       LEFT JOIN (
                            SELECT ABS(SUM(t2.summ)) AS summ, COUNT(*) AS sold, calls.mango_id
                                FROM
                                    transactions AS t1
                                INNER JOIN connectors ON 
                                    connectors.a = t1.call_id
                                INNER JOIN transactions AS t2 ON
                                    connectors.b_final = t2.call_id
                                INNER JOIN calls ON
                                    calls.id = t1.call_id                     
                                WHERE
                                    1
                                AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                             GROUP BY calls.mango_id 
                        ) AS resale ON resale.mango_id = mangos.id
                        
                        LEFT JOIN (
                            SELECT ABS(SUM(t2.summ)) AS summ, COUNT(*) AS sold, calls.mango_id
                                FROM
                                    transactions AS t1
                                INNER JOIN cashbacks ON 
                                    cashbacks.call_id = t1.call_id    
                                INNER JOIN connectors ON 
                                    connectors.a = cashbacks.resale_call_id
                                INNER JOIN transactions AS t2 ON
                                    connectors.b_final = t2.call_id
                                INNER JOIN calls ON
                                    calls.id = t1.call_id                     
                                WHERE
                                    1
                                AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                             GROUP BY calls.mango_id 
                        ) AS resale_cashback ON resale_cashback.mango_id = mangos.id
                        
                        WHERE mangos.id = {$mango_id}";
            
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            try {            
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }
                
                $stmt->execute();
                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    foreach (['type' => 'Нет в БД', 'scenario' => 'Не определен', 'activation' => 'Нет в UIS', 'subnls_source' => 'Нет источника', 'regions' => 'Нет источника', 'channels' => 'Нет'] as $key => $val)  {
                        if (!$row[$key]) {
                            $row[$key] = $val;
                        }
                    }        
                    $arr_data = $row;
                }
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            
            $arr_data['procent'] = $arr_data['count'] ? round($arr_data['sold'] / $arr_data['count'], 4) * 100 . '%' : '0%';
            
            $arr_data['datepicker'] = $date_start ? date("d.m.Y", strtotime($date_start)) : date("Y-m-d 00:00:00");
            $arr_data['datepicker'] .= "-";
            $arr_data['datepicker'] .= $date_end ? date("d.m.Y", strtotime($date_end)) : date("Y-m-d 23:59:59");
            
            $arr_data['id'] = $mango_id;
            
            $arr_data['places'] = [];
            $arr_data['new_nyks'] = [];
            $arr_data['tags'] = [];
            
            $sql = "SELECT IF(places.id IS NULL, 'Неизвестно', places.name) AS name, 
                       COUNT(*) AS count,
                       SUM(IF(direct.summ IS NULL, 0, direct.summ) + IF(resale.summ IS NULL, 0, resale.summ) + IF(resale_cashback.summ IS NULL, 0, resale_cashback.summ)) AS expense,
                       CONCAT('place-', IF(places.id IS NULL, 0, places.id)) AS tag,
                       partner_orders.tag_text AS tag_text
                    FROM
                        calls
                    INNER JOIN transactions ON
                        transactions.call_id = calls.id
                    INNER JOIN partner_orders ON
                        partner_orders.call_id = calls.id 
                    LEFT JOIN places ON
                        partner_orders.place_id = places.id 
                    LEFT JOIN regions ON
                        places.region_id = regions.id 
                        
                    LEFT JOIN (
                            SELECT 1 AS summ, transactions.call_id
                                FROM
                                    transactions
                                INNER JOIN calls ON
                                    calls.id = transactions.call_id      
                                WHERE
                                    1
                                AND (transactions.timestamp >= '$date_start' AND transactions.timestamp <= '$date_end') AND transactions.summ != 0
                           
                        ) AS direct ON direct.call_id = calls.id
                        
                   LEFT JOIN (
                        SELECT 1 AS summ, t1.call_id
                            FROM
                                transactions AS t1
                            INNER JOIN connectors ON 
                                connectors.a = t1.call_id
                            INNER JOIN transactions AS t2 ON
                                connectors.b_final = t2.call_id
                            INNER JOIN calls ON
                                calls.id = t1.call_id                     
                            WHERE
                                1
                            AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                        
                        ) AS resale ON resale.call_id = calls.id
                        
                   LEFT JOIN (
                        SELECT 1 AS summ, t1.call_id
                            FROM
                                transactions AS t1
                            INNER JOIN cashbacks ON 
                                cashbacks.call_id = t1.call_id    
                            INNER JOIN connectors ON 
                                connectors.a = cashbacks.resale_call_id
                            INNER JOIN transactions AS t2 ON
                                connectors.b_final = t2.call_id
                            INNER JOIN calls ON
                                calls.id = t1.call_id                     
                            WHERE
                                1
                            AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                        
                        ) AS resale_cashback ON resale_cashback.call_id = calls.id
                          
                    WHERE
                        1
                    AND (calls.timestamp >= '$date_start' AND calls.timestamp <= '$date_end')
                    AND (calls.mango_id = {$mango_id})
                    $filter_place
                    $filter_nyk
                GROUP BY IF (partner_orders.place_id IS NULL, 0, partner_orders.place_id), partner_orders.tag_text";
                
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            try {            
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }                                
                
                $stmt->execute();
                
                $i = 0;
                $other = ['name' => 0, 'count' => 0, 'procent' => 0, 'tag' => [], 'active' => false];
                
                $t_value = [];
                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                   $key = explode(',', $row['tag_text']);
                   
                   $t_arg_keys = [];                   
                   foreach ($key as $key_tag) {
                      $array = explode('-', $key_tag);
                      if (count($array) != 2) continue; 
                      $t_arg_keys[$array[0]][] = $array[1];
                   }
                   
                   $pass = false;
                   if ($tags) {
                    
                        $count_tags = count($tags);
                        $count_tag = 0;
                        
                        foreach ($tags as $tag_id => $tag1) {
                            foreach ($tag1 as $tag_value) {
                                if (isset($t_arg_keys[$tag_id]) && in_array($tag_value, $t_arg_keys[$tag_id])) {
                                    $count_tag++;
                                    break;
                                }  
                            }
                        }
                        
                        if ($count_tag == $count_tags) $pass = true;
                   }
                   else
                      $pass = true;
                   
                    if (!$pass) continue;
                    
                    if (!isset($t_value[$row['name']]['count'])) $t_value[$row['name']]['count'] = 0;
                    if (!isset($t_value[$row['name']]['expense'])) $t_value[$row['name']]['expense'] = 0;
                    
                    $t_value[$row['name']]['name'] = $row['name'];
                    $t_value[$row['name']]['count'] += $row['count'];
                    $t_value[$row['name']]['expense'] += $row['expense'];  
                    $t_value[$row['name']]['tag'] = $row['tag'];                                      
               }
               
               uasort($t_value, array('App\Repository\MangoRepo', 'cmp_obj_key'));     
                
               foreach ($t_value as $row) {                     
                    if ($i >= 10) {
                        $other['name']++;
                        $other['count'] += $row['count'];
                        $other['procent'] += $row['expense'];
                        $other['tag'][] = $row['tag'];
                    }  
                    else {
                        $arr_data['places'][] = ['name' => $row['name'], 'count' => $row['count'], 'procent' => $row['expense'], 'tag' => $row['tag'], 
                                        'active' => in_array($row['tag'], $arg_tags)];
                    }
                        
                    $i++;   
                }
                
                if ($other['name']) {
                     $arr_data['places'][] = ['name' => 'Другие ('. $other['name'] . ')', 'count' => $other['count'], 'procent' => $other['procent'], 'tag' => implode(',', $other['tag']),
                                                'active' => !((bool) array_diff($other['tag'], $arg_tags))];   
                } 
                      
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            
            $sql = "SELECT IF(new_nyks.id IS NULL, 'неизвестно', new_nyks.name) AS name,  
                       COUNT(*) AS count,
                       SUM(IF(direct.summ IS NULL, 0, direct.summ) + IF(resale.summ IS NULL, 0, resale.summ) + IF(resale_cashback.summ IS NULL, 0, resale_cashback.summ)) AS expense,
                       CONCAT('new_nyk-', IF(new_nyks.id IS NULL, 0, new_nyks.id)) AS tag,
                       partner_orders.tag_text AS tag_text
                    FROM
                        calls
                    INNER JOIN transactions ON
                        transactions.call_id = calls.id
                    INNER JOIN partner_orders ON
                        partner_orders.call_id = calls.id 
                    LEFT JOIN new_nyks ON
                        partner_orders.new_nyk_id = new_nyks.id
                        
                    LEFT JOIN (
                            SELECT 1 AS summ, transactions.call_id
                                FROM
                                    transactions
                                INNER JOIN calls ON
                                    calls.id = transactions.call_id      
                                WHERE
                                    1
                                AND (transactions.timestamp >= '$date_start' AND transactions.timestamp <= '$date_end') AND transactions.summ != 0
                           
                        ) AS direct ON direct.call_id = calls.id
                        
                   LEFT JOIN (
                        SELECT 1 AS summ, t1.call_id
                            FROM
                                transactions AS t1
                            INNER JOIN connectors ON 
                                connectors.a = t1.call_id
                            INNER JOIN transactions AS t2 ON
                                connectors.b_final = t2.call_id
                            INNER JOIN calls ON
                                calls.id = t1.call_id                     
                            WHERE
                                1
                            AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                        
                        ) AS resale ON resale.call_id = calls.id
                        
                   LEFT JOIN (
                        SELECT 1 AS summ, t1.call_id
                            FROM
                                transactions AS t1
                            INNER JOIN cashbacks ON 
                                cashbacks.call_id = t1.call_id    
                            INNER JOIN connectors ON 
                                connectors.a = cashbacks.resale_call_id
                            INNER JOIN transactions AS t2 ON
                                connectors.b_final = t2.call_id
                            INNER JOIN calls ON
                                calls.id = t1.call_id                     
                            WHERE
                                1
                            AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                        
                        ) AS resale_cashback ON resale_cashback.call_id = calls.id
                           
                    WHERE
                        1
                    AND (calls.timestamp >= '$date_start' AND calls.timestamp <= '$date_end')
                    AND (calls.mango_id = {$mango_id})
                    $filter_place
                    $filter_nyk
                GROUP BY IF (partner_orders.new_nyk_id IS NULL, 0, partner_orders.new_nyk_id), partner_orders.tag_text";
                
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            //echo $sql;
            
            try {            
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }
                
                $stmt->execute();
                
                $t_value = [];
                                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                   $key = explode(',', $row['tag_text']);
                   
                   $t_arg_keys = [];                   
                   foreach ($key as $key_tag) {
                      $array = explode('-', $key_tag);
                      if (count($array) != 2) continue; 
                      $t_arg_keys[$array[0]][] = $array[1];
                   }
                   
                   $pass = false;
                   if ($tags) {
                    
                        $count_tags = count($tags);
                        $count_tag = 0;
                        
                        foreach ($tags as $tag_id => $tag1) {
                            foreach ($tag1 as $tag_value) {
                                if (isset($t_arg_keys[$tag_id]) && in_array($tag_value, $t_arg_keys[$tag_id])) {
                                    $count_tag++;
                                    break;
                                }  
                            }
                        }
                        
                        if ($count_tag == $count_tags) $pass = true;
                   }
                   else
                      $pass = true;
                   
                    if (!$pass) continue;
                    
                    if (!isset($t_value[$row['name']]['count'])) $t_value[$row['name']]['count'] = 0;
                    if (!isset($t_value[$row['name']]['expense'])) $t_value[$row['name']]['expense'] = 0;
                    
                    $t_value[$row['name']]['name'] = $row['name'];
                    $t_value[$row['name']]['count'] += $row['count'];
                    $t_value[$row['name']]['expense'] += $row['expense'];  
                    $t_value[$row['name']]['tag'] = $row['tag'];                                      
               }
               
               uasort($t_value, array('App\Repository\MangoRepo', 'cmp_obj_key')); 
               
               foreach ($t_value as $row) {   
                    $arr_data['new_nyks'][] = ['name' => $row['name'], 'count' => $row['count'], 'procent' => $row['expense'], 'tag' => $row['tag'], 'active' => in_array($row['tag'], $arg_tags)];
               } 
                    
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            
            $sql = "SELECT partner_orders.tag_text, 
                       COUNT(*) AS count,
                       SUM(IF(direct.summ IS NULL, 0, direct.summ) + IF(resale.summ IS NULL, 0, resale.summ) + IF(resale_cashback.summ IS NULL, 0, resale_cashback.summ)) AS expense
                    FROM
                        calls
                    INNER JOIN transactions ON
                        transactions.call_id = calls.id
                    INNER JOIN partner_orders ON
                        partner_orders.call_id = calls.id
                        
                    LEFT JOIN (
                            SELECT 1 AS summ, transactions.call_id
                                FROM
                                    transactions
                                INNER JOIN calls ON
                                    calls.id = transactions.call_id      
                                WHERE
                                    1
                                AND (transactions.timestamp >= '$date_start' AND transactions.timestamp <= '$date_end') AND transactions.summ != 0
                             
                        ) AS direct ON direct.call_id = calls.id
                        
                   LEFT JOIN (
                        SELECT 1 AS summ, t1.call_id
                            FROM
                                transactions AS t1
                            INNER JOIN connectors ON 
                                connectors.a = t1.call_id
                            INNER JOIN transactions AS t2 ON
                                connectors.b_final = t2.call_id
                            INNER JOIN calls ON
                                calls.id = t1.call_id                     
                            WHERE
                                1
                            AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                        
                    ) AS resale ON resale.call_id = calls.id
                    
                    LEFT JOIN (
                        SELECT 1 AS summ, t1.call_id
                            FROM
                                transactions AS t1
                            INNER JOIN cashbacks ON 
                                cashbacks.call_id = t1.call_id    
                            INNER JOIN connectors ON 
                                connectors.a = cashbacks.resale_call_id
                            INNER JOIN transactions AS t2 ON
                                connectors.b_final = t2.call_id
                            INNER JOIN calls ON
                                calls.id = t1.call_id                     
                            WHERE
                                1
                            AND (t1.timestamp >= '$date_start' AND t1.timestamp <= '$date_end') AND t2.summ != 0
                        
                    ) AS resale_cashback ON resale_cashback.call_id = calls.id
                     
                    WHERE
                        1
                    AND (calls.timestamp >= '$date_start' AND calls.timestamp <= '$date_end')
                    AND (calls.mango_id = {$mango_id})
                    $filter_place
                    $filter_nyk
                GROUP BY partner_orders.tag_text";
                
            self::setCIBA2();
            if (self::$CIBA2) pdo::clearPdo();
            
            try {            
                if (self::$CIBA2) {
                    $sql = pdo::prepareChangeToCiba2($sql, self::$CIBA2);
                    $stmt = pdo::getCiba2Pdo()->prepare($sql);
                }
                else {
                    $stmt = pdo::getPDO()->prepare($sql);
                }
                
                $stmt->execute();
                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $arr_data['tags'][$row['tag_text']] = [$row['count'], $row['expense']];    
                }
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            
            $sql = "SELECT DISTINCT tag_tables.name 
                                FROM tag_tables 
                                    INNER JOIN offer_to_tag_tables ON offer_to_tag_tables.tag_table_id = tag_tables.id
                                    INNER JOIN offers ON offer_to_tag_tables.offer_id = offers.id
                                        ORDER BY offers.sort ASC, offers.id ASC, tag_tables.sort ASC, tag_tables.id ASC";
            $tag_tables = pdo::getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
            
            $t_collapse_model = [];
            
            foreach ($arr_data['tags'] as $tag => $count) {
               $key = explode(',', $tag);
               
               $t_arg_keys = [];
               foreach ($key as $key_tag) {
                  $array = explode('-', $key_tag);
                  if (count($array) != 2) continue; 
                  $t_arg_keys[$array[0]][] = $array[1];
               }
               
               $pass = false;
               if ($tags) {
                
                    $count_tags = count($tags);
                    $count_tag = 0;
                    
                    foreach ($tags as $tag_id => $tag1) {
                        foreach ($tag1 as $tag_value) {
                            if (isset($t_arg_keys[$tag_id]) && in_array($tag_value, $t_arg_keys[$tag_id])) {
                                $count_tag++;
                                break;
                            }  
                        }
                    }
                    
                    if ($count_tag == $count_tags) $pass = true;
               }
               else
                  $pass = true;
               
               if (!$pass) continue;
                              
               foreach ($key as $value) {
                   $array = explode('-', $value);
                   if (count($array) != 2) continue; 
                   
                   $pos = array_search($array[0], $tag_tables);
                   if ($pos !== false) {
                        if (!isset($t_collapse_model[$pos .'-' . $array[0]][$array[1]])) $t_collapse_model[$pos .'-' . $array[0]][$array[1]] = [];
                        $t_collapse_model[$pos . '-' . $array[0]][$array[1]][] = $count; 
                   }                  
               }
            }
            
            //print_r($t_collapse_model);
            
            foreach ($t_collapse_model as $key => $value) {
                //arsort($value);
                
                $tag_table = explode('-', $key);
                $tag_table = $tag_table[1];
                
                $t_value = [];
                foreach ($value as $k => $val) {
                    foreach ($val as $v) {
                        if (!isset($t_value[$k][0])) $t_value[$k][0] = 0;
                        if (!isset($t_value[$k][1])) $t_value[$k][1] = 0;
                        
                        $t_value[$k][0] += $v[0];
                        $t_value[$k][1] += $v[1];
                        
                        $t_value[$k][2] = $tag_table. '-' . $k;
                    }
                }
                
                uasort($t_value, array('App\Repository\MangoRepo', 'cmp_obj'));
                    
                $t_collapse_model[$key] = $t_value;
            }            

            ksort($t_collapse_model, SORT_NATURAL);
            
            $t_sort = [];
            foreach ($t_collapse_model as $tag_table => $value) {
                $tag_table = explode('-', $tag_table);
                $tag_table = $tag_table[1];
                $t_sort[$tag_table] = $value;
            }
            
            $a = json_decode(tools::request_api(['op' => 'v3', 'args' => ['mode' => 'decode', 'array' => $t_sort]]), true);
            $t_collapse_model = $a['answer'];
            
            $t_tags = [];
            
            foreach ($t_collapse_model as $tag_table => $value) {
                
                $all = 0;
               
                foreach ($value as $name => $count) {
                    
                    $t_tags[$tag_table][$name]['name'] = $name;
                    $t_tags[$tag_table][$name]['count'] = $count[0];
                    $t_tags[$tag_table][$name]['procent'] = $count[1];
                    $t_tags[$tag_table][$name]['tag'] = $count[2];
                }
            }
            
            $tagss = [];
            
            foreach ($t_tags as $t_key => $t_tag) {
            
                $i = 0;
                $other = ['name' => 0, 'count' => 0, 'procent' => 0, 'tag' => [], 'active' => false];
                
                foreach ($t_tag as $row) {
                    
                    if ($i >= 10) {
                        $other['name']++;
                        $other['count'] += $row['count'];
                        $other['procent'] += $row['procent'];
                        $other['tag'][] = $row['tag'];
                    }  
                    else {
                        $tagss[$t_key][] = ['name' => $row['name'], 'count' => $row['count'], 'procent' => $row['procent'], 'tag' => $row['tag'], 'active' => in_array($row['tag'], $arg_tags)];
                    }
                        
                    $i++;   
                }
                
                if ($other['name']) {
                     $tagss[$t_key][] = ['name' => 'другие ('. $other['name'] . ')', 'count' => $other['count'], 'procent' => $other['procent'], 'tag' => implode(',', $other['tag']),
                                                'active' => !((bool) array_diff($other['tag'], $arg_tags))];   
                }
            }
            
            $arr_data['tags'] = $tagss;
            
            return $arr_data;
         }
    }
    
    public static function cmp_obj($a, $b)
    {
        if ($a[0] == $b[0]) {
            return 0;
        }
        return ($a[0] > $b[0]) ? -1 : 1;
    }
    
    public static function cmp_obj_key($a, $b)
    {
        if ($a['count'] == $b['count']) {
            return 0;
        }
        return ($a['count'] > $b['count']) ? -1 : 1;
    }    
}