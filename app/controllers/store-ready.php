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

require_once('helpers/session.php');
require_once('helpers/store.php');
require_once('helpers/user.php');
require_once('helpers/order.php');
require_once('helpers/csrf.php');

class StorereadyController {
	public function post() {
		if (!get_user_attr(get_user_email(), 'merchant')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

        if (get_order_status()) {
			$params = [ 'message' => 'Bestellungen sind noch nicht abgeschlossen.' ];
            return [ 'error', $params ];
        }

        $user_email = $_REQUEST['user'];
        $user_id = get_user_attr($user_email, 'id');

        $stores = get_stores();

        $user_stores = [];

        foreach ($stores as $store_id => $store) {
            if ($store['merchant_id'] == $user_id) {
                $user_stores[] = $store_id;
            }
        }

        $merchant_order = get_current_merchant_order();

        $target_stores = [];

        foreach ($merchant_order as $item) {
            if (in_array($item['store_id'], $user_stores) && !in_array($item['store_id'], $target_stores)) {
                $target_stores[] = $item['store_id'];
            }
        }

        $text = "Essen ist da! :)";

        foreach ($target_stores as $store_id) {
            if ($stores[$store_id]['status_message'] == $text) {
                continue;
            }

    		set_store_attr($store_id, 'status_message', $text);

            foreach (get_users() as $user_id => $user) {
                if (!has_order_for_shop($user['email'], $store_id))
                    continue;
 
                send_jabber_message($user['email'], mb_convert_encoding("Der Status für " . get_stores()[$store_id]['name'] . " hat sich geändert: " . $text, 'iso-8859-1'));
            }
        }

		header('Location: /app/stores');
		die();
	}
}

