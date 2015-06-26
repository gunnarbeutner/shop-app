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
require_once('helpers/order.php');

class OrderController {
	public function get() {
		verify_user();

		$merchant_order = get_current_merchant_order();
		
		$best_store = null;
		
		foreach ($merchant_order as $item) {
			if ($item['user_email'] == get_user_email()) {
				$best_store = $item['store_id'];
				break;
			}
		}
		
		$params = [
			'stores' => get_stores(),
			'order' => get_current_order(get_user_id()),
			'votes' => get_primary_votes(),
			'order_status' => get_order_status(),
			'store_status' => get_store_status(),
			'best_store' => $best_store
		];
		return [ 'order', $params ];
	}
}
