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
require_once('helpers/mail.php');

$stores = get_stores();
$users = get_users();

$store_list = [];

foreach ($stores as $store_id => $store) {
	if ($store['merchant_email'] != '') {
		$store_list[] = "* " . $store['name'];
	}
}

if (count($store_list) == 0) {
	return;
}

$store_list_text = implode("\n", $store_list);

foreach ($users as $user_id => $user) {
	$user_escaped = $shop_db->quote($user_id);

	$query = <<<QUERY
SELECT COUNT(oi.`id`) as cnt
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id` = oi.`order_id`
WHERE o.`user_id` = ${user_escaped}
QUERY;
	$cnt = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC)['cnt'];

	if ($cnt == 0) {
		continue;
	}
	
	if (!$user['promotional_mails']) {
		continue;
	}

	$first_name = explode(' ', $user['name'], 2)[0];
	$shop_brand = SHOP_BRAND;
	$shop_url = 'https://' . SHOP_DOMAIN . '/';

	$subject = "Mittagsbestellung";
	$message = <<<MESSAGE
Hallo ${first_name},

Heute im Angebot gibt es:

${store_list_text}

Bestellungen können wie immer unter ${shop_url} aufgegeben werden.

Gruß
${shop_brand}
MESSAGE;

	app_mail($user['email'], $subject, $message);
}
