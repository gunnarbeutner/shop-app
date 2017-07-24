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
SELECT v.`id`, v.`name`, v.`description`, v.`merchant_id`, v.`min_order_count`, v.`min_order_volume`, v.`service_charge_amount`, v.`service_charge_description`, v.`status_message`, v.`rebate_percent`, v.`rebate_user_id`, v.`tracking_id`, m.`name` AS merchant_name, m.`email` AS merchant_email
FROM `stores` v
LEFT JOIN `users` m ON m.`id`=v.`merchant_id`
WHERE `hidden`=0
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

function set_store_attr($store_id, $attr, $value) {
	global $shop_db;

	$store_quoted = $shop_db->quote($store_id);
	$value_quoted = $shop_db->quote($value);

	$query = <<<QUERY
UPDATE `stores`
SET `$attr`=${value_quoted}
WHERE `id`=${store_quoted}
QUERY;

	$shop_db->query($query);
}

function get_recent_orders($uid, $store_id) {
    global $shop_db;

    $uid_quoted = $shop_db->quote($uid);
    $store_quoted = $shop_db->quote($store_id);

    $query = <<<QUERY
SELECT oi.title, oi.price, o.date
FROM order_items oi
LEFT JOIN orders o ON o.id=oi.order_id
WHERE o.user_id = ${uid_quoted} AND oi.store_id = ${store_quoted}
ORDER BY oi.id DESC
LIMIT 5
QUERY;
	$items = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$items[] = $row;
	}
	return $items;
}

function new_store($name, $description) {
    global $shop_db;

    $name_quoted = $shop_db->quote($name);
    $description_quoted = $shop_db->quote($description);

    $query = <<<QUERY
INSERT INTO stores
(name, description)
VALUES
(${name_quoted}, ${description_quoted})
QUERY;

    $shop_db->query($query);
}
