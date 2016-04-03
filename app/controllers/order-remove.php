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

class OrderremoveController {
	public function post() {
		verify_csrf_token();

		if (!get_order_status() && !get_user_attr(get_user_email(), 'merchant')) {
			$params = [ 'message' => 'Bestelländerungen aktuell nicht mehr möglich.' ];
			return [ 'error', $params ];
		}

		$item_id = $_REQUEST['item'];
        $item = get_item($item_id);

        if (!$item) {
            $params = [ 'message' => 'Die angegebene Item-ID ist ungültig.' ];
            return [ 'error', $params ];
        }

        $uid = $item['user_id'];

        if ($uid != get_user_id() && (!get_user_attr(get_user_email(), 'merchant') || is_direct_debit_done())) {
            $params = [ 'message' => 'Sie können diese Bestellung nicht ändern.' ];
            return [ 'error', $params ];
        }

		remove_item($uid, $item_id);

		$amount = get_max_order_amount($uid);
		set_held_amount(email_from_uid($uid), $amount);
	
        if ($uid != get_user_id()) {
		    header('Location: /app/merchant-orders');
        } else {
	    	header('Location: /app/order');
        }

		die();
	}
}
