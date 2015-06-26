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
require_once('helpers/order.php');

class OrderaddController {
	public function post() {
		verify_csrf_token();

		if (!get_order_status()) {
			$params = [ 'message' => 'Bestelländerungen aktuell nicht mehr möglich.' ];
			return [ 'error', $params ];
		}
		
		$store_id = $_POST['store'];
		$title = $_POST['title'];
		$price = str_replace(',', '.', $_POST['price']);

		if ($title == '') {
			$params = [ 'message' => 'Die Artikelbeschreibung darf nicht leer sein.' ];
			return [ 'error', $params ];
		}
		
		if (bccomp($price, 0) != 1) {
			$params = [ 'message' => 'Der Betrag muss positiv sein.' ];
			return [ 'error', $params ];
		}
		
		$item_id = add_item(get_user_id(), $store_id, $title, $price);
		
		$amount = get_max_order_amount(get_user_id());
		if (!set_held_amount(get_user_email(), $amount)) {
			remove_item(get_user_id(), $item_id);
			
			$params = [ 'message' => 'Umsatzanfrage bei der Bank fehlgeschlagen. Bitte Kontodeckung überprüfen.' ];
			return [ 'error', $params ];
		}

		header('Location: /app/order');
		die();
	}
}
