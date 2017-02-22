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

class OrdereditController {
	public function get() {
		if (!get_order_status()) {
			$params = [ 'message' => 'Bestelländerungenaktuell nicht mehr möglich.' ];
			return [ 'error', $params ];
		}

		$item_id = $_GET['item'];
        $item = get_item($item_id);

        if ($item['user_id'] != get_user_id() && !get_user_attr(get_user_email(), 'merchant')) {
            $params = [ 'message' => 'Sie können diese Bestellung nicht ändern.' ];
            return [ 'error', $params ];
        }

		$params = [
            'email' => email_from_uid($item['user_id']),
			'item_id' => $item_id,
            'item' => $item
		];
		return [ 'order-edit', $params ];
	}

	public function post() {
        global $shop_db;

        if (!get_user_attr(get_user_email(), 'admin') || !isset($_REQUEST['email']))
    		verify_csrf_token();

		if (!get_order_status()) {
			$params = [ 'message' => 'Bestelländerungen aktuell nicht mehr möglich.' ];
			return [ 'error', $params ];
		}
		
		$old_item_id = $_REQUEST['item'];
        $old_item = get_item($old_item_id);

        if (!$old_item) {
            $params = [ 'message' => 'Die angegebene Item-ID ist ungültig.' ];
            return [ 'error', $params ];
        }

        $old_uid = $old_item['user_id'];
        $old_email = get_user_attr($old_uid, 'id');

        if ($old_uid != get_user_id() && (!get_user_attr(get_user_email(), 'merchant') || is_direct_debit_done())) {
            $params = [ 'message' => 'Sie können diese Bestellung nicht ändern.' ];
            return [ 'error', $params ];
        }

        $email = $_REQUEST['email'];

        if ($email != '') {
            $uid = get_user_attr($email, 'id');

            if (!$uid) {
                $params = [ 'message' => 'Ungültiger Benutzer.' ];
                return [ 'error', $params ];
            }
        } else {
            $uid = get_user_id();
        }

        if ($uid != get_user_id() && !get_user_attr(get_user_email(), 'merchant')) {
            $params = [ 'message' => 'Sie können keine Bestellungen für andere Benutzer anlegen.' ];
            return [ 'error', $params ];
        }

		$title = $_REQUEST['title'];
		$price = str_replace(',', '.', $_REQUEST['price']);

		if ($title == '') {
			$params = [ 'message' => 'Die Artikelbeschreibung darf nicht leer sein.' ];
			return [ 'error', $params ];
		}
		
		if (bccomp($price, 0) != 1) {
			$params = [ 'message' => 'Der Betrag muss positiv sein.' ];
			return [ 'error', $params ];
		}

        $email = email_from_uid($uid);

        $shop_db->query("BEGIN");

        remove_item($old_uid, $old_item_id);

		$amount = get_max_order_amount($old_uid);
		set_held_amount($old_email, $amount);

		$item_id = add_item($uid, $old_item['store_id'], $title, $price);
		
		$amount = get_max_order_amount($uid);
		if (!set_held_amount($email, $amount)) {
            $shop_db->query("ROLLBACK");
			
			$params = [ 'message' => 'Umsatzanfrage bei der Bank fehlgeschlagen. Bitte Kontodeckung überprüfen.' ];
			return [ 'error', $params ];
		}

        $shop_db->query("COMMIT");

        if ($uid != get_user_id()) {
		    header('Location: /app/merchant-orders');
        } else {
    		header('Location: /app/order');
        }

		die();
	}
}
