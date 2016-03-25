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
require_once('helpers/store.php');
require_once('helpers/order.php');

class MerchantstatusController {
	public function post() {
		verify_csrf_token();

		if (!get_user_attr(get_user_email(), 'merchant')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}
		
		$store_id = $_POST['store'];
		$status = $_POST['status'];

		if ($status == '1') {
			$merchant = get_user_id();
		} else {
			$merchant = null;
		}
		
		if ($merchant === null && count_orders($store_id) > 0) {
			$params = [ 'message' => 'Anbieter kann nicht geändert werden. Für den Laden gibt es bereits Bestellungen.' ];
			return [ 'error', $params ];
		}
		
		set_merchant($store_id, $merchant);

		header('Location: /app/stores');
		die();
	}
}

