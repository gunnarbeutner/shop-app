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
require_once('helpers/session.php');

function get_users() {
	global $shop_db;

	$query = <<<QUERY
SELECT `id`, `name`, `email`, `promotional_mails`, `held_amount`
FROM `users`
QUERY;
	$users = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$users[$row['id']] = $row;
	}
	return $users;
}

function set_held_amount($email, $amount) {
	$fields = [
		'email' => $email,
		'tag' => 'lunch-shop',
		'amount' => $amount
	];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, SHOP_BANK_URL . '/app/preauth');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(($fields)));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, SHOP_BANK_USER . ':' . SHOP_BANK_PASSWORD);
	curl_exec($ch);
	
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	$ok = ($status >= 200 && $status <= 299);
	
	if ($ok) {
		set_user_attr($email, 'held_amount', $amount);
	}
	
	return $ok;
}

function execute_direct_debit($from, $amount, $reference) {
	$fields = [
		'from' => $from,
		'amount' => $amount,
		'reference' => $reference
	];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, SHOP_BANK_URL . '/app/direct-debit');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, SHOP_BANK_USER . ':' . SHOP_BANK_PASSWORD);
	curl_exec($ch);
	
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	return ($status >= 200 && $status <= 299);
}

function execute_transfer($to, $amount, $reference) {
	$fields = [
		'to' => $to,
		'amount' => $amount,
		'reference' => $reference,
		'tan' => '973842'
	];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, SHOP_BANK_URL . '/app/transfer');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, SHOP_BANK_USER . ':' . SHOP_BANK_PASSWORD);
	curl_exec($ch);
	
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	return ($status >= 200 && $status <= 299);
}

function get_user_ext_info($email) {
	$fields = [
		'email' => $email
	];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, SHOP_BANK_URL . '/app/user-info?' . http_build_query($fields));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, SHOP_BANK_USER . ':' . SHOP_BANK_PASSWORD);
	$json = curl_exec($ch);
	
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	if ($status < 200 || $status > 299) {
		return false;
	}

	return json_decode($json, true);
}
