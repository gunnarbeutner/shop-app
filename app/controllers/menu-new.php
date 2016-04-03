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
require_once('helpers/article.php');
require_once('helpers/store.php');
require_once('helpers/csrf.php');

class MenunewController {
	public function get() {
		verify_user();
		
		if (!get_user_attr(get_user_email(), 'merchant')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

        $store_id = $_REQUEST['store'];
        $group_id = $_REQUEST['group'];
        $parent_id = $_REQUEST['parent'];

        if ($parent_id != '') {
            $parent = get_article($parent_id);
            $store_id = $parent['store_id'];
        }

        $store = get_stores()[$store_id];

        if ($group_id != '') {
            $group = get_article_groups()[$group_id];
        }

		$params = [
            'parent' => $parent,
            'store' => $store,
            'group' => $group
		];
		return [ 'menu-new', $params ];
	}

	public function post() {
		verify_csrf_token();

		if (!get_user_attr(get_user_email(), 'merchant')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}
		
        $title = $_POST['title'];

        if ($title == '') {
            $params = [ 'message' => 'Artikelname muss angegeben werden.' ];
            return [ 'error', $params ];
        }

        $description = $_POST['description'];
        $price = str_replace(',', '.', $_REQUEST['price']);
        $store = $_POST['store'];
        $group = $_POST['group'];

        if ($group == '') {
            $params = [ 'message' => 'Artikelgruppe muss angegeben werden.' ];
            return [ 'error', $params ];
        }

        $parent = $_POST['parent'];

        new_article($title, $description, $price, $store, $group, $parent);

        return [ 'menu-new-success' ];
	}
}

