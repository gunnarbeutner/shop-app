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

require_once('helpers/db.php');

function get_stores() {
	global $shop_db;

	$query = <<<QUERY
SELECT v.`id`, v.`name`, v.`description`, v.`merchant_id`, v.`min_order_count`, v.`min_order_volume`, m.`name` AS merchant_name, m.`email` AS merchant_email
FROM `stores` v
LEFT JOIN `users` m ON m.`id`=v.`merchant_id`
ORDER BY v.`id` ASC
QUERY;
	$stores = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$stores[$row['id']] = $row;
	}
	return $stores;
}

function set_merchant($store_id, $merchant_id) {
	global $shop_db;
	
	$store_quoted = $shop_db->quote($store_id);
	
	if ($merchant_id === null) {
		$merchant_quoted = 'null';
	} else {
		$merchant_quoted = $shop_db->quote($merchant_id);
	}
	
	$query = <<<QUERY
UPDATE `stores`
SET `merchant_id`=${merchant_quoted}
WHERE `id`=${store_quoted}
QUERY;
	$shop_db->query($query);
}