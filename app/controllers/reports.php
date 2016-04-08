<?php

/*
 * Shop
 * Copyright (C) 2015 Gunnar Beutner
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
 */

require_once('helpers/session.php');
require_once('helpers/store.php');

class ReportsController {
    static $reports = [
      'numordersbystores' => 'Anzahl Bestellungen, gruppiert nach Läden',
      'numordersbyusers' => 'Anzahl Bestellungen, gruppiert nach Kunden',
      'numordersbymerchants' => 'Anzahl Bestellungen, gruppiert nach Händlern',
      'numordersbyday' => 'Anzahl Bestellungen, gruppiert nach Wochentag',
      'revenuebystores' => 'Umsatz in Euro, gruppiert nach Läden',
      'revenuebymerchants' => 'Umsatz in Euro, gruppiert nach Händlern',
      'revenuebyday' => 'Umsatz in Euro, gruppiert nach Wochentag',
      'feesbystores' => 'Lieferpauschale/Trinkgeld in Euro, gruppiert nach Läden'
    ];

    public static function _conv_result($item) {
        return [ 'x' => $item['x'], 'y' => (double)$item['y'] ];
    }

	public function get() {
        global $shop_db;

		verify_user();
		
		if (!get_user_attr(get_user_email(), 'merchant')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

        $data = null;

        $report = $_REQUEST['report'];
        $date_begin = $_REQUEST['date-begin'];
        $date_end = $_REQUEST['date-end'];

        $mindate = $shop_db->query("SELECT MIN(date) AS mindate FROM orders")->fetch(PDO::FETCH_ASSOC)['mindate'];

        if ($date_begin == '')
            $date_begin = $mindate;

        if ($date_end == '')
            $date_end = date('Y-m-d');

        $date_begin_quoted = $shop_db->quote($date_begin);
        $date_end_quoted = $shop_db->quote($date_end);

        if ($report != '') {
            if (!array_key_exists($report, ReportsController::$reports)) {
                $params = [ 'message' => 'Ungültige Report-ID.' ];
                return [ 'error', $params ];
            }

            if ($report == 'numordersbystores') {
                $query = <<<SQL
SELECT s.`name` AS `x`, COUNT(oi.`id`) as `y`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `stores` s ON s.`id`=oi.`store_id`
WHERE oi.`direct_debit_done` = 1
AND o.`date` BETWEEN ${date_begin_quoted} AND ${date_end_quoted}
GROUP BY oi.`store_id`
ORDER BY `y` DESC
SQL;
            } else if ($report == 'numordersbyusers') {
                $query = <<<SQL
SELECT u.`name` AS `x`, COUNT(oi.`id`) as `y`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `users` u ON u.`id`=o.`user_id`
WHERE oi.`direct_debit_done` = 1
AND o.`date` BETWEEN ${date_begin_quoted} AND ${date_end_quoted}
GROUP BY o.`user_id`
ORDER BY `y` DESC
LIMIT 20
SQL;
            } else if ($report == 'numordersbymerchants') {
                $query = <<<SQL
SELECT u.`name` AS `x`, COUNT(oi.`id`) as `y`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `users` u ON u.`id`=oi.`merchant_id`
WHERE oi.`direct_debit_done` = 1
AND o.`date` BETWEEN ${date_begin_quoted} AND ${date_end_quoted}
GROUP BY oi.`merchant_id`
ORDER BY `y` DESC
LIMIT 20
SQL;
            } else if ($report == 'numordersbyday') {
                $query = <<<SQL
SELECT ELT(WEEKDAY(o.`date`)+1, 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag') AS `x`, COUNT(oi.`id`) as `y`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
WHERE oi.`direct_debit_done` = 1
AND o.`date` BETWEEN ${date_begin_quoted} AND ${date_end_quoted}
GROUP BY WEEKDAY(o.`date`)
ORDER BY WEEKDAY(o.`date`) ASC
SQL;
            } else if ($report == 'revenuebystores') {
                $query = <<<SQL
SELECT s.`name` AS `x`, SUM(oi.`price` + oi.`fee`) as `y`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `stores` s ON s.`id`=oi.`store_id`
WHERE oi.`direct_debit_done` = 1
AND o.`date` BETWEEN ${date_begin_quoted} AND ${date_end_quoted}
GROUP BY oi.`store_id`
ORDER BY `y` DESC
SQL;
            } else if ($report == 'revenuebymerchants') {
                $query = <<<SQL
SELECT u.`name` AS `x`, SUM(oi.`price` + oi.`fee`) as `y`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `users` u ON u.`id`=oi.`merchant_id`
WHERE oi.`direct_debit_done` = 1
AND o.`date` BETWEEN ${date_begin_quoted} AND ${date_end_quoted}
GROUP BY oi.`merchant_id`
ORDER BY `y` DESC
LIMIT 20
SQL;
            } else if ($report == 'revenuebyday') {
                $query = <<<SQL
SELECT ELT(WEEKDAY(o.`date`)+1, 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag') AS `x`, SUM(oi.`price` + oi.`fee`) as `y`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
WHERE oi.`direct_debit_done` = 1
AND o.`date` BETWEEN ${date_begin_quoted} AND ${date_end_quoted}
GROUP BY WEEKDAY(o.`date`)
ORDER BY WEEKDAY(o.`date`) ASC
SQL;
            } else if ($report == 'feesbystores') {
                $query = <<<SQL
SELECT s.`name` AS `x`, SUM(oi.`fee`) as `y`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `stores` s ON s.`id`=oi.`store_id`
WHERE oi.`direct_debit_done` = 1
AND o.`date` BETWEEN ${date_begin_quoted} AND ${date_end_quoted}
GROUP BY oi.`store_id`
HAVING `y` > 0
ORDER BY `y` DESC
LIMIT 20
SQL;
            }

            $data = array_map('ReportsController::_conv_result', $shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC));
        }

        $params = [
            'mindate' => $mindate,
            'date-begin' => $date_begin,
            'date-end' => $date_end,
            'reports' => ReportsController::$reports,
            'id' => $report,
            'data' => $data
        ];

		return [ 'reports', $params ];
	}
}

