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
require_once('helpers/store.php');
require_once('helpers/user.php');
require_once('helpers/order.php');
require_once('helpers/mail.php');

$stores = get_stores();
$users = get_users();

$store_list = [];

foreach ($stores as $store_id => $store) {
	if ($store['merchant_email'] != '') {
		$store_list[] = $store['name'];
	}
}

if (count($store_list) == 0) {
	return;
}

foreach ($users as $user_id => $user) {
	if (!$user['order_reminders']) {
		continue;
	}

    $has_order = false;

    foreach ($stores as $store_id => $store) {
        if (has_order_for_shop($user['email'], $store_id)) {
            $has_order = true;
            break;
        }
    }

    if ($has_order)
        continue;

	$first_name = explode(' ', $user['name'], 2)[0];

    $message = "Hallo ${first_name}! Du hast heute noch nichts bestellt. Dies kannst du mit dem Befehl 'order' nachholen. Du kannst diese Benachrichtigung in Zukunft mit 'reminders off' deaktivieren.";

    send_jabber_message($user['email'], mb_convert_encoding($message, 'iso-8859-1'));
}
