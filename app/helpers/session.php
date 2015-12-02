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

session_start();

function is_logged_in() {
	return isset($_SESSION['email']);
}

function get_user_email() {
	return $_SESSION['email'];
}

function get_user_name() {
	return $_SESSION['name'];
}

function get_user_id() {
	return $_SESSION['uid'];
}

function get_user_token() {
	return $_SESSION['token'];
}

function verify_user() {
	if (!is_logged_in()) {
		header('Location: /app/login');
		die();
	}
}

function reset_login_token($email) {
	global $shop_db;

	$token = bin2hex(openssl_random_pseudo_bytes(16));

	$token_quoted = $shop_db->quote($token);
	$email_quoted = $shop_db->quote($email);

	$query = <<<QUERY
UPDATE `users`
SET `login_token`=${token_quoted}
WHERE `email`=${email_quoted}
QUERY;

	$shop_db->query($query);
	
	return $token;
}

function email_from_uid($uid) {
	global $shop_db;

	$uid_quoted = $shop_db->quote($uid);

	$query = <<<QUERY
SELECT `email`
FROM `users`
WHERE `id`=${uid_quoted}
QUERY;

	$row = $shop_db->query($query)->fetch();

	if ($row === false)
		return false;
	else
		return $row['email'];
}

function get_user_attr($email, $attr) {
	global $shop_db;

	$email_quoted = $shop_db->quote($email);

	$query = <<<QUERY
SELECT `$attr`
FROM `users`
WHERE `email`=${email_quoted}
QUERY;

	$row = $shop_db->query($query)->fetch();

	if ($row === false)
		return false;
	else
		return $row[$attr];
}

function find_addresses($q) {
	global $shop_db;

	$q_quoted = $shop_db->quote($q);

	$query = <<<QUERY
SELECT `email`, `name`
FROM `users`
WHERE `email` LIKE '%@%' AND (`email` LIKE CONCAT('%', ${q_quoted}, '%') OR `name` LIKE CONCAT('%', ${q_quoted}, '%'))
QUERY;

	$addresses = [];
	foreach ($shop_db->query($query) as $row) {
		$addresses[] = [
			'email' => $row['email'],
			'name' => $row['name']
		];
	}

	return $addresses;
}

function set_user_attr($email, $attr, $value) {
	global $shop_db;

	$email_quoted = $shop_db->quote($email);
	$value_quoted = $shop_db->quote($value);

	$query = <<<QUERY
UPDATE `users`
SET `$attr`=${value_quoted}
WHERE `email`=${email_quoted}
QUERY;

	$shop_db->query($query);
}

function set_user_session($email) {
	global $shop_db;

	$email_quoted = $shop_db->quote($email);

	$query = <<<QUERY
SELECT `id`, `name`, `login_token`, `email`
FROM `users`
WHERE `email`=${email_quoted}
QUERY;

	$row = $shop_db->query($query)->fetch();

	if ($row === false)
		return;

	$_SESSION['email'] = $email;
	$_SESSION['uid'] = $row['id'];
	$_SESSION['name'] = $row['name'];
	$_SESSION['token'] = $row['login_token'];
	
	setcookie('SHOPUSER', $row['email'], time() + 60 * 60 * 24 * 365);
	setcookie('SHOPTOKEN', $row['login_token'], time() + 60 * 60 * 24 * 365);
}

function send_login_token($email) {
	$token = get_user_attr($email, 'login_token');

	if ($token == '') {
		$token = reset_login_token($email);
	}

	$url = "https://" . SHOP_DOMAIN . "/app/login?account=" . urlencode($email) . "&token=" . urlencode($token);

	$message = <<<MESSAGE
Sie können sich mit folgendem Link beim Shop anmelden:

$url
MESSAGE;
	$subject = "Login beim Shop";

	$headers = [];
	$headers[] = "From: " . SHOP_BRAND . " <no-reply@" . SHOP_DOMAIN . ">";
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-type: text/plain; charset=utf-8";

	mail($email, $subject, $message, implode("\r\n", $headers));
}

if (isset($headers['HTTP_AUTHORIZATION'])) {
    $credentials = base64_decode( substr($_SERVER['HTTP_AUTHORIZATION'],6) );
    list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $credentials);
}

if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    $email = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    $upassword = get_user_attr($email, 'login_token');

    if ($password != $upassword) {
        header('WWW-Authenticate: Basic realm="' . SHOP_BRAND . '"');
        header('HTTP/1.0 401 Unauthorized');
        die();
    }

    set_user_session($email);
}
