<?php

namespace App\Repository;

use framework\pdo;
use framework\load;
use framework\tools;
use PDOException;



class DashboardRepo {
    private static $CIBA2;
    private static $_order_column;
    private static $_order_dir;
    
    private static function setCIBA2() {        
        self::$CIBA2 = true;
    }
    
    public static function getOffers($filters = [], $parent = 0, $no_lid = 0, $no_partner = 0)
    {
        $arr_data = [];
        $offers_ids = [];
        
        $parent_name = '';
        
        if ($parent) {
            
            $sql = "SELECT offers.name as name, offers.id as id, 1 as children
                        FROM offers 
 							    WHERE offers.parent=:parent AND (offers.no_active is null OR offers.no_active = 0)
   									    ORDER BY offers.sort";
                                        
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
                
                $stmt->execute(['parent' => $parent]);
                                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $arr_data[$row['id']] = ['name' => $row['name'], 'id' => $row['id'], 'children' => $row['children'] - 1, 
                                        'count' => 0, 'expense' => 0, 'partner' => 0, 'balance' => 0, 'procent' => 0, 'sold' => 0, 'flag' => '', 'deal' => 0,
                                            'prev' => ['count' => 0, 'expense' => 0, 'sold' => 0]];  
                    $offers_ids[] = $row['id'];
                }
                    
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            
            $sql = "SELECT name FROM offers WHERE id=:id";
            
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
                
                $stmt->execute(['id' => $parent]);
                                
                $parent_name = $stmt->fetch(\PDO::FETCH_COLUMN);
                    
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
        }
        else {
            
            $sql = "SELECT IF(p.name is not null, p.name, offers.name) as name, IF(p.id is not null, p.id, offers.id) as id, count(*) as children
                        FROM offers 
    						LEFT JOIN offers p ON p.id = offers.parent
    							WHERE (offers.no_active is null OR offers.no_active = 0)
    								GROUP BY IF(p.id is not null, p.id, offers.id)
    									ORDER BY offers.sort";
                                        
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
                    $arr_data[$row['id']] = ['name' => $row['name'], 'id' => $row['id'], 'children' => $row['children'] - 1, 
                                        'count' => 0, 'expense' => 0, 'partner' => 0, 'balance' => 0, 'procent' => 0, 'sold' => 0, 'flag' => '', 'deal' => 0,
                                            'prev' => ['count' => 0, 'expense' => 0, 'sold' => 0]];  
                    $offers_ids[] = $row['id'];
                }
                    
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
            
        }
        
        list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y"));
        
        $filter = "";
        $filter_partner = "";
                
        if (!empty($filters['regions'])) {
            $ids = array_map('intval', $filters['regions']);
            
            $filter .= " AND (partner_orders.region_id IN (" . implode(',', $ids) . ")) ";
            $filter_partner .= " AND (navy_services.region_id IN (" . implode(',', $ids) . ")) ";
            
            unset($filters['regions']);
        }
        
        if ($parent) {
            $select_name = "offers.name";
            $select_id = "offers.id";
        } 
        else {
            $select_name = "IF(offers.parent = 0, offers.name, parents.name)";
            $select_id = "IF(offers.parent = 0, offers.id, offers.parent)";
        }        
        
        for ($step = 1; $step <= 2; $step++)
        { 
            $date_start = date('Y-m-d H:i:s', mktime(0, 0, 0, $n, $j - 30 * $step, $Y));
            $date_end = date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j - 30 * ($step - 1) - 1, $Y));       
            
            $sql = "SELECT $select_name AS name, 
                    $select_id AS id,
                   COUNT(*) AS count,
                   SUM(IF(direct.summ IS NULL, 0, direct.summ) + IF(resale.summ IS NULL, 0, resale.summ) + IF(resale_cashback.summ IS NULL, 0, resale_cashback.summ)) AS expense,
                   SUM(IF(direct.sold IS NULL, 0, direct.sold) + IF(resale.sold IS NULL, 0, resale.sold) + IF(resale_cashback.sold IS NULL, 0, resale_cashback.sold)) AS sold
                FROM
                    calls
                INNER JOIN transactions ON
                    transactions.call_id = calls.id
                INNER JOIN partner_orders ON
                    partner_orders.call_id = calls.id 
                LEFT JOIN offers ON
                    partner_orders.offer_id = offers.id
                LEFT JOIN offers parents ON
                    parents.id = offers.parent
                INNER JOIN sources ON
                        sources.id = calls.source_id 
                            
                LEFT JOIN (
                        SELECT 1 AS summ, ABS(transactions.summ) AS sold, transactions.call_id
                            FROM
                                transactions
                            INNER JOIN calls ON
                                calls.id = transactions.call_id      
                            WHERE
                                1
                            AND (transactions.timestamp >= '$date_start' AND transactions.timestamp <= '$date_end') AND transactions.summ != 0
                       
                    ) AS direct ON direct.call_id = calls.id
                    
               LEFT JOIN (
                    SELECT 1 AS summ, ABS(t2.summ) AS sold, t1.call_id
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
                        SELECT 1 AS summ, ABS(t2.summ) AS sold, t1.call_id
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
                AND (offers.id IN (".implode(',', $offers_ids).") OR offers.parent IN (".implode(',', $offers_ids)."))
                AND (calls.timestamp >= '$date_start' AND calls.timestamp <= '$date_end')
                AND (sources.id != 51961)
                $filter
                    GROUP BY $select_id"; 
                    
            //echo $sql;
                    
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
                    if ($step == 2)
                    {
                        $arr_data[$row['id']]['prev']['count'] = $row['count'];
                        $arr_data[$row['id']]['prev']['expense'] = $row['expense'];
                        $arr_data[$row['id']]['prev']['sold'] = $row['expense'] ? round($row['sold'] / $row['expense']) : 0;
                    }                    
                    else
                    {
                        $arr_data[$row['id']]['count'] = $row['count'];
                        $arr_data[$row['id']]['expense'] = $row['expense'];
                        $arr_data[$row['id']]['sold'] = $row['expense'] ? round($row['sold'] / $row['expense']) : 0;
                        
                        $arr_data[$row['id']]['procent'] = $row['count'] ? round($row['expense'] / $row['count'], 4) * 100 : 0;
                    }
                }
                
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();   
            }
        }
        
        $sql = "SELECT offer_id, COUNT(id) as partner, SUM(summ) as balance, SUM(deal) as deal
                                FROM
            	(SELECT DISTINCT $select_id AS offer_id, organizations.id, IF(organizations.deal, 1, NULL) AS deal,
                                                IF(count_transactions.summ < 0, 0, count_transactions.summ) as summ
            				FROM offer_to_navy_services 
            					INNER JOIN navy_services ON offer_to_navy_services.navy_service_id = navy_services.id
            					INNER JOIN address ON address.id = navy_services.addres_id	
            					INNER JOIN organizations ON organizations.id = address.organization_id
                                INNER JOIN offers ON offer_to_navy_services.offer_id = offers.id
            				    LEFT JOIN count_transactions ON count_transactions.organization_id = organizations.id
            							WHERE (navy_services.no_active IS NULL OR navy_services.no_active = 0) 
                                                    AND organizations.lid_type = 3 AND (organizations.no_active = 0) 
                                                        AND (organizations.name != '')
                                                            AND (offers.id IN (".implode(',', $offers_ids).") OR offers.parent IN (".implode(',', $offers_ids)."))
                                                            $filter_partner) as a
                                            GROUP BY offer_id"; 
        
        //echo $sql;
        
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
                $arr_data[$row['offer_id']]['partner'] = $row['partner'];
                $arr_data[$row['offer_id']]['balance'] = $row['balance'];
                $arr_data[$row['offer_id']]['deal'] = (integer) $row['deal'];
            }
            
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();   
        }
        
        
        foreach ($arr_data as $offer_id => $row)
        {        
            $count_to_buy = $row['sold'] ? ($row['balance'] / $row['sold']) : 0;
            $count_to_sell = round($row['count'] * 0.6);
                
            $flag = '';
                                    
            if ($count_to_buy < $count_to_sell)  {               
                $flag = 'МП';
            }
                    
            if ($count_to_buy > $count_to_sell) {
                $flag = 'МЛ';
            }
            
            $arr_data[$offer_id]['flag'] = $flag;
            
            if ($no_partner || $no_lid) {
                
                $pass = false;
                if ($no_partner && $flag == 'МП') $pass = true;
                if ($no_lid && $flag == 'МЛ') $pass = true;
                        
                if (!$pass) unset($arr_data[$offer_id]);
            }
        }
        
        uasort($arr_data, array('App\Repository\DashboardRepo', 'cmp_balance')); 
        
        return ['offers' => $arr_data, 'parent' => $parent, 'parent_name' => $parent_name];   
    }
    
    public static function getSetkas($datepicker = false, $channels = [], $prev = true, $interval_value = 1, $order_dir = 1, $order_column = 1, $procent = 0) {
        
      $date_start = array_key_exists('start', $datepicker) ? $datepicker['start'] : false;
      $date_end = array_key_exists('end', $datepicker) ? $datepicker['end'] : false;
    
      $date_start_timestamp = strtotime($date_start);
      $date_end_timestamp = strtotime($date_end);
    
      $date_start = $date_start ? date("Y-m-d H:i:s", $date_start_timestamp) : date("Y-m-d 00:00:00");
      $date_end = $date_end ? date("Y-m-d H:i:s", $date_end_timestamp) : date("Y-m-d 23:59:59");
      
      $day = 24 * 60 * 60;
      $interval = ($date_end_timestamp + 1 - $date_start_timestamp) / $day; 
    
      $calc_prev = [];
      if ($prev) {
        $calc_prev = self::getSetkas(['start' => date("Y-m-d H:i:s", $date_start_timestamp - $interval * $day * $interval_value), 'end' => date("Y-m-d H:i:s", $date_end_timestamp - $interval * $day * $interval_value)], $channels, false, $interval_value);
      }
      
      $filter_channel = "";
      $filter_t1_channel = "";
      $filter_partner_channel = "";
      
      $without = false;
      if (in_array('Без канала', $channels)) $without = true;
      
      if ($channels)  {
        $filter_channel = " AND (transactions.channel_id IN (SELECT id FROM channels WHERE name IN ('" . implode('\',\'', $channels) . "'))";
        if ($without) $filter_channel .= " OR transactions.channel_id = 0 OR transactions.channel_id IS NULL";
        $filter_channel .= ")";
        
        $filter_t1_channel = " AND (t1.channel_id IN (SELECT id FROM channels WHERE name IN ('" . implode('\',\'', $channels) . "'))";
        if ($without) $filter_t1_channel .= " OR t1.channel_id = 0 OR t1.channel_id IS NULL";
        $filter_t1_channel .= ")";
        
        $filter_partner_channel = " AND (partner_apps.channel_id IN (SELECT id FROM channels WHERE name IN ('" . implode('\',\'', $channels) . "'))";
        if ($without) $filter_partner_channel .= " OR partner_apps.channel_id = 0 OR partner_apps.channel_id IS NULL";
        $filter_partner_channel .= ")";
      }
      
      if (in_array('YD', $channels) || !$channels) 
        $stats = "SUM(cost)";
      else
        $stats = "0";
        
      if (in_array('GA', $channels) || !$channels) 
        $ad_stats = "SUM(cost)";
      else
        $ad_stats = "0";
        
      $arr_data = ['setkas' => [], 'count' => 0, 'sold' => 0, 'expense' => 0, 'cost' => 0];
      
      $sql = "SELECT 
                SUM(IF(count_calls.count IS NULL, 0, count_calls.count)) AS count,
                SUM(IF(direct.summ IS NULL, 0, direct.summ) + IF(resale.summ IS NULL, 0, resale.summ) + IF(resale_cashback.summ IS NULL, 0, resale_cashback.summ)) AS expense,
                SUM(IF(direct.sold IS NULL, 0, direct.sold) + IF(resale.sold IS NULL, 0, resale.sold) + IF(resale_cashback.sold IS NULL, 0, resale_cashback.sold)) AS sold,
                SUM(IF(stats.cost IS NULL, 0, stats.cost) + 
                        IF(ad_stats.cost IS NULL, 0, ad_stats.cost) + 
                            IF(partner_apps.cost IS NULL, 0, partner_apps.cost)) AS cost,
                setkas.syn AS name,
                setkas.id AS id
                           
                FROM sources
                
                LEFT JOIN (
                    SELECT COUNT(*) AS count, calls.source_id
                        FROM
                            calls
                        INNER JOIN transactions ON
                            transactions.call_id = calls.id
                        WHERE
                            1
                        AND (calls.timestamp >= '$date_start' AND calls.timestamp <= '$date_end')
                        $filter_channel
                    GROUP BY calls.source_id
                ) AS count_calls ON count_calls.source_id = sources.id
                
                LEFT JOIN (
                    SELECT ABS(SUM(transactions.summ)) AS summ, COUNT(*) AS sold, calls.source_id
                        FROM
                            transactions
                        INNER JOIN calls ON
                            calls.id = transactions.call_id     
                        WHERE
                            1
                        AND (transactions.timestamp >= '$date_start' AND transactions.timestamp <= '$date_end') AND transactions.summ != 0
                        $filter_channel
                     GROUP BY calls.source_id
                ) AS direct ON direct.source_id = sources.id
                
               LEFT JOIN (
                    SELECT ABS(SUM(t2.summ)) AS summ, COUNT(*) AS sold, calls.source_id
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
                        $filter_t1_channel
                     GROUP BY calls.source_id
                ) AS resale ON resale.source_id = sources.id
                
                LEFT JOIN (
                    SELECT ABS(SUM(t2.summ)) AS summ, COUNT(*) AS sold, calls.source_id
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
                        $filter_t1_channel
                     GROUP BY calls.source_id
                ) AS resale_cashback ON resale_cashback.source_id = sources.id
                
                LEFT JOIN (
                    SELECT $stats AS cost, stats.source_id 
                        FROM
                            stats
                        WHERE
                        1
                        AND (stats.d_date >= '$date_start' AND stats.d_date <= '$date_end')
                    GROUP BY stats.source_id 
                ) AS stats ON stats.source_id = sources.id
                
                LEFT JOIN (
                    SELECT $ad_stats AS cost, ad_stats.source_id 
                        FROM
                            ad_stats
                        WHERE
                        1
                        AND (ad_stats.d_date >= '$date_start' AND ad_stats.d_date <= '$date_end')
                    GROUP BY ad_stats.source_id 
                ) AS ad_stats ON ad_stats.source_id = sources.id
                
                LEFT JOIN (
                    SELECT SUM(price) AS cost, partner_apps.source_id 
                        FROM
                            partner_apps
                        WHERE
                        1
                        AND (partner_apps.date_create >= '$date_start' AND partner_apps.date_create <= '$date_end')
                        $filter_partner_channel
                    GROUP BY partner_apps.source_id 
                ) AS partner_apps ON partner_apps.source_id = sources.id
                
                INNER JOIN (
                    SELECT DISTINCT
                        source_id, source_tags.id_type
                    FROM
                        sources
                    LEFT JOIN source_tags ON
                        sources.id = source_tags.source_id
                    WHERE
                        (source_tags.name_type = 'setka')
                ) AS setka_table ON setka_table.source_id = sources.id
                
                INNER JOIN setkas ON 
                    setkas.id = setka_table.id_type
                    
                GROUP BY setka_table.id_type";
        
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
                
                $arr_data['setkas'][$row['id']] = 
                             [
                               'name' => $row['name'], 
                               'count' => $row['count'], 
                               'sold' => $row['sold'], 
                               'procent' => $row['count'] ? round($row['sold'] / $row['count'], 4) * 100 : 0,
                               'merge' => $row['expense'] - $row['cost'],
                             ];
                             
                $arr_data['count'] += $row['count'];
                $arr_data['sold'] += $row['sold'];
                $arr_data['expense'] += $row['expense'];
                $arr_data['cost'] += $row['cost'];     
            }
                
            $stmt = null;
        }
        catch (PDOException $e) {
            print $e->getMessage();
        }                
        
        self::$_order_dir = $order_dir;
        self::$_order_column = $order_column;
        
        uasort($arr_data['setkas'], array('App\Repository\DashboardRepo', 'cmp_obj_key'));     
        
        $arr_data['prev'] = $calc_prev;
        $arr_data['interval_value'] = $interval_value;
        
        $arr_data['procent'] = $arr_data['count'] ? round($arr_data['sold'] / $arr_data['count'], 4) * 100 : 0;
        $arr_data['merge'] = $arr_data['expense'] - $arr_data['cost'];
        
        $arr_data['order_dir'] = $order_dir;
        $arr_data['order_column'] = $order_column;
        
        $arr_data['datepicker'] = $date_start ? date("d.m.Y", strtotime($date_start)) : date("Y-m-d 00:00:00");
        $arr_data['datepicker'] .= "-";
        $arr_data['datepicker'] .= $date_end ? date("d.m.Y", strtotime($date_end)) : date("Y-m-d 23:59:59");
        
        $arr_data['ths'] = ['Сетка', 'Лиды', 'Продано', 'Процент', 'Маржа'];
        $arr_data['pr'] = $procent;
          
        return $arr_data;
    }
    
    public static function cmp_balance($a, $b)
    {
        if ($a['balance'] == $b['balance']) {
            return 0;
        }
        
        return ($a['balance'] > $b['balance']) ? -1 : 1;
    }
    
    public static function cmp_obj_key($a, $b)
    {
        $th = ['name', 'count', 'sold', 'procent', 'merge'];
        $order_dir = self::$_order_dir;
        $order_column = $th[self::$_order_column];
        
        if (is_numeric($a[$order_column]))
        {
            if ($a[$order_column] == $b[$order_column]) return 0;
            
            $test = ($a[$order_column] < $b[$order_column]) ? -1 : 1;
            if ($order_dir) $test = ($test == -1) ? 1 : -1;
            
            return $test;
        }
        
        $test = strnatcmp($a[$order_column], $b[$order_column]);
        if ($order_dir) $test = ($test == -1) ? 1 : -1;
        return $test;

    }
    
    private static function _calcDaily($type, $date_start, $date_end) {

      $arr_data = [];
      
      $translate_types = [
        'SUM(IF(count_calls.count IS NULL, 0, count_calls.count)) AS count', 
        
        'SUM(IF(direct.sold IS NULL, 0, direct.sold) + IF(resale.sold IS NULL, 0, resale.sold) + IF(resale_cashback.sold IS NULL, 0, resale_cashback.sold)) AS count',
        
        'SUM(IF(direct.sold IS NULL, 0, direct.sold) + IF(resale.sold IS NULL, 0, resale.sold) + IF(resale_cashback.sold IS NULL, 0, resale_cashback.sold)) AS count, 
        SUM(IF(direct.summ IS NULL, 0, direct.summ) + IF(resale.summ IS NULL, 0, resale.summ) + IF(resale_cashback.summ IS NULL, 0, resale_cashback.summ)) AS expense,
        SUM(IF(stats.cost IS NULL, 0, stats.cost) + 
                        IF(ad_stats.cost IS NULL, 0, ad_stats.cost) + 
                            IF(partner_apps.cost IS NULL, 0, partner_apps.cost)) AS cost'                         
                     ];
    
      $date_start_timestamp = strtotime($date_start);
      $date_end_timestamp = strtotime($date_end);    
    
      $day = 24 * 60 * 60;
      $interval = ($date_end_timestamp + 1 - $date_start_timestamp) / $day;   
      
      foreach ([['SEO'], ['YD'], ['Без канала'], ['GA', 'VK', 'MT'], []] as $key_channel => $channels)
      {
          $filter_channel = "";
          $filter_t1_channel = "";
          $filter_partner_channel = "";
          
          $without = false;
          if (in_array('Без канала', $channels)) $without = true;
          
          if ($channels)  {
            $filter_channel = " AND (transactions.channel_id IN (SELECT id FROM channels WHERE name IN ('" . implode('\',\'', $channels) . "'))";
            if ($without) $filter_channel .= " OR transactions.channel_id = 0 OR transactions.channel_id IS NULL";
            $filter_channel .= ")";
            
            $filter_t1_channel = " AND (t1.channel_id IN (SELECT id FROM channels WHERE name IN ('" . implode('\',\'', $channels) . "'))";
            if ($without) $filter_t1_channel .= " OR t1.channel_id = 0 OR t1.channel_id IS NULL";
            $filter_t1_channel .= ")";
            
            $filter_partner_channel = " AND (partner_apps.channel_id IN (SELECT id FROM channels WHERE name IN ('" . implode('\',\'', $channels) . "'))";
            if ($without) $filter_partner_channel .= " OR partner_apps.channel_id = 0 OR partner_apps.channel_id IS NULL";
            $filter_partner_channel .= ")";
          }
          
          if (in_array('YD', $channels) || !$channels) 
            $stats = "SUM(cost)";
          else
            $stats = "0";
            
          if (in_array('GA', $channels) || !$channels) 
            $ad_stats = "SUM(cost)";
          else
            $ad_stats = "0";
          
          $sql = "SELECT 
                    $translate_types[$type]                    
                               
                    FROM sources
                    
                    LEFT JOIN (
                        SELECT COUNT(*) AS count, calls.source_id
                            FROM
                                calls
                            INNER JOIN transactions ON
                                transactions.call_id = calls.id
                            WHERE
                                1
                            AND (calls.timestamp >= '$date_start' AND calls.timestamp <= '$date_end')
                            $filter_channel
                        GROUP BY calls.source_id
                    ) AS count_calls ON count_calls.source_id = sources.id
                    
                    LEFT JOIN (
                        SELECT ABS(SUM(transactions.summ)) AS summ, COUNT(*) AS sold, calls.source_id
                            FROM
                                transactions
                            INNER JOIN calls ON
                                calls.id = transactions.call_id     
                            WHERE
                                1
                            AND (transactions.timestamp >= '$date_start' AND transactions.timestamp <= '$date_end') AND transactions.summ != 0
                            $filter_channel
                         GROUP BY calls.source_id
                    ) AS direct ON direct.source_id = sources.id
                    
                   LEFT JOIN (
                        SELECT ABS(SUM(t2.summ)) AS summ, COUNT(*) AS sold, calls.source_id
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
                            $filter_t1_channel
                         GROUP BY calls.source_id
                    ) AS resale ON resale.source_id = sources.id
                    
                    LEFT JOIN (
                        SELECT ABS(SUM(t2.summ)) AS summ, COUNT(*) AS sold, calls.source_id
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
                            $filter_t1_channel
                         GROUP BY calls.source_id
                    ) AS resale_cashback ON resale_cashback.source_id = sources.id
                    
                    LEFT JOIN (
                        SELECT $stats AS cost, stats.source_id 
                            FROM
                                stats
                            WHERE
                            1
                            AND (stats.d_date >= '$date_start' AND stats.d_date <= '$date_end')
                        GROUP BY stats.source_id 
                    ) AS stats ON stats.source_id = sources.id
                    
                    LEFT JOIN (
                        SELECT $ad_stats AS cost, ad_stats.source_id 
                            FROM
                                ad_stats
                            WHERE
                            1
                            AND (ad_stats.d_date >= '$date_start' AND ad_stats.d_date <= '$date_end')
                        GROUP BY ad_stats.source_id 
                    ) AS ad_stats ON ad_stats.source_id = sources.id
                    
                    LEFT JOIN (
                        SELECT SUM(price) AS cost, partner_apps.source_id 
                            FROM
                                partner_apps
                            WHERE
                            1
                            AND (partner_apps.date_create >= '$date_start' AND partner_apps.date_create <= '$date_end')
                            $filter_partner_channel
                        GROUP BY partner_apps.source_id 
                    ) AS partner_apps ON partner_apps.source_id = sources.id
                    
                    INNER JOIN (
                        SELECT DISTINCT
                            source_id, source_tags.id_type
                        FROM
                            sources
                        LEFT JOIN source_tags ON
                            sources.id = source_tags.source_id
                        WHERE
                            (source_tags.name_type = 'setka')
                    ) AS setka_table ON setka_table.source_id = sources.id";
            
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
                    
                    if ($type == 2)
                    {
                        if ($key_channel == 4)
                        {
                            $merge = ($row['expense'] - $row['cost']);
                            $count = [round($merge / $interval), $row['count'] ? round($merge / $row['count']) : 0];
                        }
                        else
                        {
                            $merge = ($row['expense'] - $row['cost']);
                            $count = round($merge / $interval);
                        }
                    }
                    else
                        $count = round($row['count'] / $interval);
                        
                    $arr_data[] = $count;
                   
                }
                    
                $stmt = null;
            }
            catch (PDOException $e) {
                print $e->getMessage();
            }
        } 
        
        return $arr_data;              
    }
    
    public static function getDaily($type = 0, $clear = 0) {

      $arr_data = [];
      $arr_data['type'] = $type;
      
      list($h, $i, $s, $n, $j, $Y) = array(date("H"),date("i"),date("s"),date("n"),date("j"),date("Y")); 
                       
      $translate_ths = ['лиды', 'продано', 'маржа'];      
      $months = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
      
      $st = 0;
      
      for ($step = 6; $step >= 0; $step--)
      {
          if ($step != 0)
          {
              $date_start = date('Y-m-d H:i:s', mktime(0, 0, 0, $n - $step, 1, $Y));
              $date_end = date('Y-m-d H:i:s', mktime(23, 59, 59, $n - $step, date('t', strtotime($date_start)), $Y));    
          }
          else
          {
              $date_start = date('Y-m-d H:i:s', mktime(0, 0, 0, $n, 1, $Y));
              
              if ($j > 1)
              {
                  $date_end = date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j - 1, $Y));    
              }
              else
              {
                  $date_end = date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $j, $Y));
              }
           }
                      
           $date_start_timestamp = strtotime($date_start);
           $month_number = date('n', $date_start_timestamp) - 1;
           $year_number = date('Y', $date_start_timestamp);
            
           $arr_data['data'][$st] = [];
           $arr_data['data'][$st][] = $months[$month_number];
           
           if (($step == 0) || ($step == 1 && $j == 1))
           {
               $mas = self::_calcDaily($type, $date_start, $date_end);
           }
           else
           {
               $file_name = \DOCUMENT_ROOT.'/upload/cache/m-' . $year_number . '-' . ($month_number + 1) . '-'. $type;

               if ($clear || !file_exists($file_name))
               {
                  file_put_contents($file_name, json_encode(self::_calcDaily($type, $date_start, $date_end)));
               }
               
               $mas = json_decode(file_get_contents($file_name), true);
           }
           
           foreach ($mas as $m)
           {
                if (is_array($m))
                {
                    foreach ($m as $mm)
                    {
                        $arr_data['data'][$st][] = $mm;
                        $arr_data['class'][$st][] = 'bold'; 
                    }
                }
                else
                {
                    $arr_data['data'][$st][] = $m;
                    $arr_data['class'][$st][] = 'bold'; 
                }
           }
           
           $st++;           
        }
        
        for ($d = 1; $d < $j; $d++)
        {
            $timestamp = mktime(0, 0, 0, $n, $d, $Y);
            $date_start = date('Y-m-d H:i:s', $timestamp);
            
            $date_end = date('Y-m-d H:i:s', mktime(23, 59, 59, $n, $d, $Y));
            
            $arr_data['data'][$st] = [];
            $arr_data['data'][$st][] = date('d.m.y', $timestamp);
            
            if ($d == ($j - 1))
            {
                $mas = self::_calcDaily($type, $date_start, $date_end);
            }
            else
            {
                $file_name = \DOCUMENT_ROOT.'/upload/cache/d-' . $Y . '-' . $n . '-'. $d . '-'. $type;

                if ($clear || !file_exists($file_name))
                {
                   file_put_contents($file_name, json_encode(self::_calcDaily($type, $date_start, $date_end)));
                }
               
                $mas = json_decode(file_get_contents($file_name), true);
            }
           
            foreach ($mas as $m)
            {
                if (is_array($m))
                {
                    foreach ($m as $mm)
                        $arr_data['data'][$st][] = $mm;
                }
                else
                    $arr_data['data'][$st][] = $m;
            }
            
            $N = date('N', $timestamp);
             
            if ($N == 6 || $N == 7)
            {
                $arr_data['class'][$st][0] = 'red';
            }
            
            $st++;
        }
        
        $flip_row = [];
        
        foreach ($arr_data['data'] as $row => $cols)
        {
            foreach ($cols as $col => $cell)
            {
                if ($col) $flip_row[$col][$row] = $cell;
            }
        }
        
        foreach ($flip_row as $col => $rows)
        {
            $min_row = min($rows);
            $max_row = max($rows);
            
            foreach ($rows as $row => $cell)
            {
                $p = $max_row - $min_row;
                $diff = $cell - $min_row;
                
                $arr_data['bck'][$row][$col] = tools::percentageToHsl2($diff  / $p); //, 0, 120);    
            }
        }
        
        //print_r($arr_data);
          
        return $arr_data;
    }                  
}
    
    