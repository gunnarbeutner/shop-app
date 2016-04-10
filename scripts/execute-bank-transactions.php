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

set_order_status(false);

$order = get_current_merchant_order();

$stores = get_stores();

$transfer_items = [];
$rebates = [];

foreach (get_users() as $user_id => $user) {
	$amount = 0;
	$user_items = [];
	
	foreach ($order as $item) {
		if ($item['user_email'] != $user['email'] || $item['direct_debit_done']) {
			continue;
		}

		$item_price = $item['price'];
        $item_fee = bcmul(get_store_fee_multiplier($item['store_id'], false), $item['price'], 10);
        $item_rebate = bcmul(get_store_rebate_multiplier($item['store_id']), bcadd($item_price, $item_fee, 10), 10);

        $amount = bcadd($amount, $item_price, 10);

        set_order_item_attr($item['id'], 'fee', $item_fee);
        $amount = bcadd($amount, $item_fee, 10);

        set_order_item_attr($item['id'], 'rebate', $item_rebate);
        $amount = bcadd($amount, $item_rebate, 10);

        set_order_item_attr($item['id'], 'merchant_id', $stores[$item['store_id']]['merchant_id']);

        if (bccomp($item_rebate, '0') != 0) {
            $rebate_user = $stores[$item['store_id']]['rebate_user_id'];
            if (!array_key_exists($rebate_user, $rebates)) {
                $rebates[$rebate_user] = '0';
            }
            $rebates[$rebate_user] = bcadd($rebates[$rebate_user], $item_rebate, 10);
        }

		$user_items[] = $item;
	}

    $amount = bcadd($amount, '0');

	if (bccomp($amount, '0') != 0) {
		$item_names = [];
		foreach ($user_items as $item) {
			$item_names[] = $item['title'];
		}
		
		$from = $user['email'];
		$tx_reference = "Mittagsbestellung (" . implode('; ', $item_names) . ")";
		
		set_held_amount($from, 0);

		echo "<- ${from} - ${amount} - ${tx_reference}\n";
		$status = execute_direct_debit($from, $amount, $tx_reference);
	} else {
		$status = true;
	}

	if ($status && count($user_items) > 0) {
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

foreach ($rebates as $rebate_user => $rebate_amount) {
    $amount = bcmul('-1', $rebate_amount);
    $from = email_from_uid($rebate_user);
    $tx_reference = 'Rabatte für Mittagsbestellungen';

    echo "<- ${from} - ${amount} - ${tx_reference}\n";
    execute_direct_debit($from, $amount, $tx_reference, true);
}

foreach ($stores as $store_id => $store) {
	$amount = 0;
	
	foreach ($transfer_items as $item) {
		if ($item['store_id'] != $store_id) {
			continue;
		}
		
		$amount = bcadd($amount, $item['price'], 10);
        $amount = bcadd($amount, bcmul(get_store_fee_multiplier($store_id, false), $item['price'], 10), 10);
	}

    $amount = bcadd($amount, '0');
	
	if (bccomp($amount, '0') == 0)
			continue;

	$to = $store['merchant_email'];
	$tx_reference = "Mittagsbestellungen bei ${store['name']}";

	echo "-> ${to} - ${amount} - ${tx_reference}\n";
	execute_transfer($to, $amount, $tx_reference);
}
