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
require_once('helpers/user.php');
require_once('helpers/order.php');
require_once('helpers/store.php');

class UserinfoController {
	public function get() {
		if (!get_user_attr(get_user_email(), 'admin')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

		$jid = $_GET['jid'];
        $email = email_from_jid($jid);

        if (!$email) {
			$params = [ 'message' => 'Ungültige JID.' ];
			return [ 'error', $params ];
        }

        $stores = get_stores();

        $current_stores = [];
        foreach ($stores as $store) {
            if (!$store['merchant_id'])
                continue;
            $current_items = [];
            $current_order = get_current_order(get_user_attr($email, 'id'));
            foreach ($current_order['items'] as $item) {
                if ($item['store_id'] == $store['id'])
                    $current_items[] = $item;
            }
            $priority = 0;
            foreach ($current_order['store_prio'] as $prio_info) {
                if ($prio_info['store_id'] == $store['id']) {
                    $priority = $prio_info['index'];
                    break;
                }
            }
            $current_stores[$store['id']] = [
                'name' => $store['name'],
                'priority' => $priority,
                'status' => $store['status_message'],
                'has_order' => has_order_for_shop($email, $store['id']),
                'current_orders' => $current_items,
                'recent_orders' => get_recent_orders(get_user_attr($email, 'id'), $store['id'])
            ];
        }

		$params = [
            'email' => $email,
            'reminders' => get_user_attr($email, 'order_reminders') != "0",
            'order_status' => get_order_status(),
            'login_token' => get_user_attr($email, 'login_token'),
            'current_stores' => $current_stores,
            'ext_info' => get_user_ext_info($email)
		];
		return [ 'user-info', $params ];
	}
}
