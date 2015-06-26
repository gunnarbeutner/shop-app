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

class PaymentmethodController {
	public function post() {
		verify_user();
		verify_csrf_token();

		$payment_method = $_POST['payment-method'];
		
		if (!in_array($payment_method, [ 'Direct Debit', 'Cash' ])) {
			$params = [ 'message' => 'Die ausgewählte Zahlungsmethode ist ungültig.' ];
			return [ 'error', $params ];
		}
		
		set_user_attr(get_user_email(), 'payment_method', $payment_method);
		
		header('Location: /app/order');
		die();
	}
}

