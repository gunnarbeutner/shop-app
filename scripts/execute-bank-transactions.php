#!/usr/bin/env php
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

require_once(__DIR__ . '/../config.php');
require_once('helpers/order.php');
require_once('helpers/db.php');
require_once('helpers/store.php');

$order = get_current_merchant_order();

$transfer_items = [];

foreach (get_users() as $user_id => $user) {
	$amount = 0;
	$user_items = [];
	
	foreach ($order as $item) {
		if ($item['user_email'] != $user['email'] || $item['direct_debit_done']) {
			continue;
		}
		
		$amount = bcadd($amount, $item['price']);
		$user_items[] = $item;
	}
	
	if (bccomp($amount, '0') == 0)
			continue;
	
	$item_names = [];
	foreach ($user_items as $item) {
		$item_names[] = $item['title'];
	}
	
	$from = $user['email'];
	$tx_reference = "Mittagsbestellung (" . implode('; ', $item_names) . ")";
	
	set_held_amount($from, 0);

	echo "<- ${from} - ${amount} - ${tx_reference}\n";
	$status = execute_direct_debit($from, $amount, $tx_reference);
	
	if ($status) {
		$order_quoted = $shop_db->quote($user_items[0]['order_id']);
		$query = <<<QUERY
UPDATE `order_items`
SET `direct_debit_done`=1
WHERE `order_id` = ${order_quoted}
QUERY;
		$shop_db->query($query);
		
		$transfer_items = array_merge($transfer_items, $user_items);
	} else {
		set_held_amount($from, $amount);
	}
}

foreach (get_stores() as $store_id => $store) {
	$amount = 0;
	
	foreach ($transfer_items as $item) {
		if ($item['store_id'] != $store_id) {
			continue;
		}
		
		$amount = bcadd($amount, $item['price']);
	}
	
	if (bccomp($amount, '0') == 0)
			continue;

	$to = $store['merchant_email'];
	$tx_reference = "Mittagsbestellungen bei ${store['name']}";

	echo "-> ${to} - ${amount} - ${tx_reference}\n";
	execute_transfer($to, $amount, $tx_reference);
}
