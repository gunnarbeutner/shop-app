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

require_once('helpers/csrf.php');
require_once('helpers/session.php');

class LoginController {
	public function get() {
		if (is_logged_in()) {
			header('Location: /app/order');
			die();
		}

		$auth_ok = null;
		
		if (isset($_COOKIE['SHOPUSER']) && $_COOKIE['SHOPUSER'] != '' && isset($_COOKIE['SHOPTOKEN']) && $_COOKIE['SHOPTOKEN'] != '') {
			$email = $_COOKIE['SHOPUSER'];
			$utoken = $_COOKIE['SHOPTOKEN'];
			$token = get_user_attr($email, 'login_token');
			if ($token != '' && $token == $utoken) {
				$auth_ok = true;
			}
		}
		
		if (isset($_GET['account']) && isset($_GET['token'])) {
			$email = $_GET['account'];
			$utoken = $_GET['token'];

			$token = get_user_attr($email, 'login_token');

			if ($token != '' && $token == $utoken) {
				$auth_ok = true;
			} else {
				$auth_ok = false;
			}
		}
		
		if ($auth_ok) {
			set_user_session($email);
			header('Location: /app/order');
			die();
		} else if ($auth_ok === false) {
			$params = [ 'message' => 'Das angegebene Token ist ungültig.' ];
			return [ 'error', $params ];
		}

		return [ 'login', null ];
	}

	public function post() {
		verify_csrf_token();

		$email = $_POST['account'];

		if (get_user_attr($email, 'id') === false) {
			$params = [ 'message' => 'Für die angegebene E-Mailadresse gibt es aktuell keinen Account.' ];
			return [ 'error', $params ];
		}
		
		send_login_token($email);

		return [ 'login-mail', null ];
	}
}
