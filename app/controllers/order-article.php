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
require_once('helpers/article.php');

class OrderarticleController {
	public function get() {
		if (!get_order_status()) {
			$params = [ 'message' => 'Bestelländerungen aktuell nicht mehr möglich.' ];
			return [ 'error', $params ];
		}

		$article_id = $_GET['article'];

		$params = [
			'article' => get_article_info($article_id),
			'groups' => get_article_groups(),
			'store_id' => get_article_attr($article_id, 'store_id')
		];
		return [ 'order-article', $params ];
	}

	public function post() {
        global $shop_db;

		verify_csrf_token();

		if (!get_order_status()) {
			$params = [ 'message' => 'Bestelländerungen aktuell nicht mehr möglich.' ];
			return [ 'error', $params ];
		}

		$store_id = null;
		$articles = [];
		$amount = 0;

		foreach ($_POST as $key => $value) {
			if (!preg_match('/^group_/', $key)) {
				continue;
			}

			foreach ($value as $article_id) {
				$name = get_article_attr($article_id, 'title');
				$store_id = get_article_attr($article_id, 'store_id');
				$price = get_article_attr($article_id, 'price');

				$articles[] = $name;
				$amount = bcadd($amount, $price);
			}
		}

		if ($_POST['comment'] != '') {
			$articles[] = $_POST['comment'];
		}

        $email = get_user_email();

        if (get_user_attr($email, 'merchant') && isset($_REQUEST['email'])) {
            $email = $_REQUEST['email'];
        }

        $uid = get_user_attr($email, 'id');

		$title = implode('; ', $articles);

        $shop_db->query("BEGIN");

		$item_id = add_item($uid, $store_id, $title, $amount);
		
		$amount = get_max_order_amount($uid);
		if (!set_held_amount($email, $amount)) {
            $shop_db->query("ROLLBACK");
			
			$params = [ 'message' => 'Umsatzanfrage bei der Bank fehlgeschlagen. Bitte Kontodeckung überprüfen.' ];
			return [ 'error', $params ];
		}

        $shop_db->query("COMMIT");

		header('Location: /app/order');
		die();
	}
}
