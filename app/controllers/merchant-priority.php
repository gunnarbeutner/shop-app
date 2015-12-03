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

class MerchantpriorityController {
	public function post() {
        if (!get_user_attr(get_user_email(), 'admin') || !isset($_REQUEST['email']))
            verify_csrf_token();

		$store_id = $_REQUEST['store'];
		$direction = $_REQUEST['direction'];

        $email = get_user_email();

        if (get_user_attr($email, 'admin') && isset($_REQUEST['email'])) {
            $email = $_REQUEST['email'];
        }

        $uid = get_user_attr($email, 'id');

		change_store_priority($uid, $store_id, $direction);

		header('Location: /app/order');
		die();
	}
}

