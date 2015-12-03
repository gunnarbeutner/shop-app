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

require_once('helpers/db.php');
require_once('helpers/store.php');
require_once('helpers/user.php');

bcscale(2);

function format_number($num, $html = true) {
	if ((float)$num >= 0)
		$color = "green";
	else
		$color = "red";

	$num = str_replace('.', ',', bcadd($num, 0));

	if ($html)
		return sprintf('<span style="color: %s">%s</span>', $color, htmlentities($num));
	else
		return $num;
}

function get_normalized_store_priorities($order_id) {
	global $shop_db;

	$order_quoted = $shop_db->quote($order_id);

	$query = <<<QUERY
SELECT `store_id`, `index`
FROM `store_priority`
WHERE `order_id`=${order_quoted}
ORDER BY `index` ASC
QUERY;
	$store_prio = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$store_prio[$row['store_id']] = (int)$row['index'];
	}

	$query = <<<QUERY
SELECT `store_id`, COUNT(`id`) AS cnt
FROM `order_items`
WHERE `order_id`=${order_quoted}
GROUP BY `store_id`
ORDER BY `store_id` ASC
QUERY;
	$item_counts = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$item_counts[$row['store_id']] = (int)$row['cnt'];
	}

	$prio = [];
	$m_index = 0;

	foreach ($store_prio as $store_id => $index) {
		if (isset($item_counts[$store_id]) && $item_counts[$store_id] > 0) {
			$prio[$store_id] = $index;
			if ($index > $m_index) {
				$m_index = $index;
			}
		}
	}
	
	foreach ($item_counts as $store_id => $count) {
		if (!isset($prio[$store_id])) {
			$m_index++;
			$prio[$store_id] = $m_index;
		}
	}

	asort($prio);
	
	$index = 0;
	$normalized_prio = [];
	foreach ($prio as $store_id => $uprio) {
		$index++;
		$normalized_prio[] = [ 'store_id' => $store_id, 'index' => $index ];
	}
	
	$ids = [];
	foreach ($normalized_prio as $info) {
		$ids[] = $shop_db->quote($info['store_id']);
	}
			
	$query = <<<QUERY
DELETE FROM `store_priority`
WHERE `order_id`=${order_quoted}
QUERY;

	if (count($ids) > 0) {
		$ids_sql = implode(', ', $ids);
		 $query .= <<<QUERY
AND `store_id` NOT IN (${ids_sql})
QUERY;
	}

	$shop_db->query($query);
	
	if (count($normalized_prio) > 0) {
		$query = <<<QUERY
INSERT INTO `store_priority`
(`order_id`, `store_id`, `index`)
VALUES

QUERY;

		$first = true;
		foreach ($normalized_prio as $info) {
			$store_quoted = $shop_db->quote($info['store_id']);
			$index_quoted = $shop_db->quote($info['index']);

			if (!$first) {
				$query .= ', ';
			} else {
				$first = false;
			}
			
			$query .= <<<QUERY
(${order_quoted}, ${store_quoted}, $index_quoted)

QUERY;
		}
		
		$query .= <<<QUERY
ON DUPLICATE KEY UPDATE `index`=VALUES(`index`)
QUERY;
		$shop_db->query($query);
	}

	return $normalized_prio;
}

function get_current_order_date() {
	$hour = date('G');
	$minute = intval(date('i'));
	
	if (($hour == 13 && $minute > 30) || $hour > 13) {
		$date = date('Y-m-d', time() + 24 * 3600);
	} else {
		$date = date('Y-m-d');
	}
	
	return $date;
}

function get_current_order($uid) {
	global $shop_db;

	$uid_quoted = $shop_db->quote($uid);
	$order_date_quoted = $shop_db->quote(get_current_order_date());
	
	$query = <<<QUERY
SELECT `id`
FROM `orders`
WHERE `user_id`=${uid_quoted} AND `date`=${order_date_quoted}
QUERY;
	$row_order = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC);
	
	if ($row_order === false) {
		$query = <<<QUERY
INSERT INTO `orders`
(`user_id`, `date`)
VALUES
(${uid_quoted}, ${order_date_quoted})
QUERY;
		$shop_db->query($query);

		$query = <<<QUERY
SELECT `id`
FROM `orders`
WHERE `user_id`=$uid_quoted AND `date`=${order_date_quoted}
QUERY;
		$row_order = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC);

		$order_quoted = $shop_db->quote($row_order['id']);

		$index = 0;
		foreach (get_stores() as $store) {
			$index++;
			
			$store_quoted = $shop_db->quote($store['id']);
			$index_quoted = $shop_db->quote($index);

			$query = <<<QUERY
INSERT INTO `store_priority`
(`order_id`, `store_id`, `index`)
VALUES
(${order_quoted}, ${store_quoted}, ${index_quoted})
QUERY;
			$shop_db->query($query);
		}
	} else {
		$order_quoted = $shop_db->quote($row_order['id']);
	}

	$query = <<<QUERY
SELECT `id`, `store_id`, `title`, `price`, `fee`, `rebate`
FROM `order_items`
WHERE `order_id`=${order_quoted}
QUERY;
	$items = $shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
	
	$order = [
		'id' => $row_order['id'],
		'store_prio' => get_normalized_store_priorities($row_order['id']),
		'items' => $items
	];
	
	return $order;
}

function change_store_priority($uid, $store_id, $direction) {
	global $shop_db;

	$order = get_current_order($uid);

	$index = null;
	
	foreach ($order['store_prio'] as $store_prio) {
		if ($store_prio['store_id'] != $store_id) {
			continue;
		}
		
		$index = $store_prio['index'];
		break;
	}
	
	if ($index === null) {
		return;
	}

	if (($index == 1 && $direction == 'up') || ($index == count($order['store_prio']) && $direction == 'down')) {
		return;
	}
	
	if ($direction == 'up') {
		$new_index = $index - 1;
	} else {
		$new_index = $index + 1;
	}
	
	$order_quoted = $shop_db->quote($order['id']);
	$index_quoted = $shop_db->quote($index);
	$new_index_quoted = $shop_db->quote($new_index);

	$query = <<<QUERY
UPDATE `store_priority`
SET `index`=${index_quoted}
WHERE `order_id`=${order_quoted} AND `index` = ${new_index_quoted}
QUERY;
	$shop_db->query($query);
	
	$store_quoted = $shop_db->quote($store_id);
	
	$query = <<<QUERY
UPDATE `store_priority`
SET `index`=${new_index_quoted}
WHERE `order_id`=${order_quoted} AND `store_id`=${store_quoted}
QUERY;
	$shop_db->query($query);
}

function get_max_order_amount($uid) {
	global $shop_db;

	$order_quoted = $shop_db->quote(get_current_order($uid)['id']);

	$query = <<<QUERY
SELECT a.amount AS amount, a.store_id FROM (
SELECT SUM(price) AS amount, store_id
FROM order_items
WHERE `order_id` = ${order_quoted} AND fee = 0
GROUP BY `store_id`) AS a ORDER BY amount DESC LIMIT 1
QUERY;

	$row = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC);
	$store = get_stores()[$row['store_id']];
	return bcadd($row['amount'], $store['service_charge_amount']);
}

function remove_item($uid, $item_id) {
	global $shop_db;

	$order_quoted = $shop_db->quote(get_current_order($uid)['id']);
	$item_quoted = $shop_db->quote($item_id);

	$query = <<<QUERY
SELECT `store_id`
FROM `order_items`
WHERE `id` = ${item_quoted}
QUERY;

	$row = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC);

	$query = <<<QUERY
DELETE FROM `order_items`
WHERE `order_id`=${order_quoted} AND `id` = ${item_quoted}
QUERY;
	$shop_db->query($query);

	if ($row) {
		update_service_charges($row['store_id']);
		update_rebate($uid, $row['store_id']);
	}
}

function add_item($uid, $store_id, $title, $price, $is_fee = false, $is_rebate = false) {
	global $shop_db;

	$order_quoted = $shop_db->quote(get_current_order($uid)['id']);
	$store_quoted = $shop_db->quote($store_id);
	$title_quoted = $shop_db->quote($title);
	$price_quoted = $shop_db->quote($price);
	$fee_quoted = $shop_db->quote($is_fee);
	$rebate_quoted = $shop_db->quote($is_rebate);

	$query = <<<QUERY
INSERT INTO `order_items`
(`order_id`, `store_id`, `title`, `price`, `fee`, `rebate`)
VALUES
(${order_quoted}, ${store_quoted}, ${title_quoted}, ${price_quoted}, ${fee_quoted}, ${rebate_quoted})
QUERY;
	$shop_db->query($query);
	$id = $shop_db->lastInsertId();

	if (!$is_fee && !$is_rebate) {
		update_service_charges($store_id);
		update_rebate($uid, $store_id);
	}

	return $id;
}

function get_votes() {
	global $shop_db;

	$order_date_quoted = $shop_db->quote(get_current_order_date());

	$query = <<<QUERY
SELECT sp.`store_id`, SUM(1 / sp.`index`) AS votes
FROM `store_priority` sp
LEFT JOIN `orders` o ON o.`id`=sp.`order_id`
WHERE o.`date` = ${order_date_quoted}
GROUP BY sp.`store_id`
ORDER BY sp.`store_id` ASC
QUERY;
	$votes = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$votes[$row['store_id']] = $row['votes'];
	}
	arsort($votes);
	return $votes;
}

function get_primary_votes() {
	global $shop_db;

	$order_date_quoted = $shop_db->quote(get_current_order_date());

	$query = <<<QUERY
SELECT sp.`store_id`, COUNT(sp.`store_id`) AS votes
FROM `store_priority` sp
LEFT JOIN `orders` o ON o.`id`=sp.`order_id`
WHERE sp.`index` = 1 AND o.`date` = ${order_date_quoted}
GROUP BY sp.`store_id`
ORDER BY sp.`store_id` ASC
QUERY;
	$votes = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$votes[$row['store_id']] = $row['votes'];
	}
	arsort($votes);
	return $votes;
}

function get_primary_votes_for_store($store_id) {
	global $shop_db;
	
	$order_date_quoted = $shop_db->quote(get_current_order_date());
	$store_quoted = $shop_db->quote($store_id);
	
	$query = <<<QUERY
SELECT o.`user_id`, u.`name` as user_name
FROM `store_priority` sp
LEFT JOIN `orders` o ON o.`id`=sp.`order_id`
LEFT JOIN `users` u ON u.`id`=o.`user_id`
WHERE sp.`index` = 1 AND o.`date` = ${order_date_quoted} AND sp.`store_id` = ${store_quoted}
QUERY;
	return $shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

function count_orders($store_id) {
	global $shop_db;
	
	$store_quoted = $shop_db->quote($store_id);
	$order_date_quoted = $shop_db->quote(get_current_order_date());

	$query = <<<QUERY
SELECT COUNT(oi.`id`) AS cnt
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
WHERE o.`date` = ${order_date_quoted} AND oi.`store_id` = ${store_quoted}
QUERY;

	return $shop_db->query($query)->fetch(PDO::FETCH_ASSOC)['cnt'];
}

function get_store_restriction_status($store) {
	global $shop_db;

	$status = 'guaranteed';
	
	$merchant_quoted = $shop_db->quote($store['merchant_id']);
	
	$query = <<<QUERY
SELECT COUNT(`id`) AS cnt
FROM `stores`
WHERE `merchant_id` = ${merchant_quoted}
QUERY;
	$cnt = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC)['cnt'];

	if ($cnt > 1) {
		$status = 'probably';
	}
	
	$store_id = $store['id'];
	$store_quoted = $shop_db->quote($store_id);
	$order_date_quoted = $shop_db->quote(get_current_order_date());
	$service_charge_description_quoted = $shop_db->quote($store['service_charge_description']);

	if ($store['min_order_volume'] > 0) {
		$query = <<<QUERY
SELECT SUM(oi.`price`) AS vol
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
WHERE o.`date` = ${order_date_quoted} AND oi.`store_id` = ${store_quoted} AND oi.`title` != ${service_charge_description_quoted}
AND oi.`fee` = 0 AND oi.`rebate` = 0
QUERY;
		$vol = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC)['vol'];

		if ($vol < $store['min_order_volume'])
			return 'unlikely';
		else
			$status = 'probably';
	}
	
	if ($store['min_order_count'] > 0) {
		$query = <<<QUERY
SELECT COUNT(oi.`id`) AS cnt
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
WHERE o.`date` = ${order_date_quoted} AND oi.`store_id` = ${store_quoted} AND oi.`title` != ${service_charge_description_quoted}
AND oi.`fee` = 0 AND oi.`rebate` = 0
QUERY;
		$cnt = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC)['cnt'];
		
		if ($cnt < $store['min_order_count'])
			return 'unlikely';
		else
			$status = 'probably';
	}

	return $status;
}

function get_store_status() {
	$votes = get_votes();
	$stores = get_stores();
	$merchants = [];
	$status = [];

	foreach ($votes as $store_id => $vote_count) {
		$store = $stores[$store_id];
		$merchant_id = $store['merchant_id'];
		if ($merchant_id == '') {
			continue;
		}
		if (!isset($merchants[$merchant_id])) {
			$merchants[$merchant_id] = [ $store_id ];
			$status[$store_id] = get_store_restriction_status($stores[$store_id]);
		} else {
			$merchants[$merchant_id][] = $store_id;
			$first = true;
			foreach ($merchants[$merchant_id] as $o_store_id) {
				if ($first) {
					$store_status = get_store_restriction_status($stores[$o_store_id]);
					if ($store_status == 'guaranteed') {
						$store_status = 'probably';
					}
					$status[$o_store_id] = $store_status;
					$first = false;
				} else {
					$status[$o_store_id] = 'unlikely';
				}
			}
			$status[$store_id] = 'unlikely';
		}
	}

	foreach ($stores as $store_id => $store) {
		if (!isset($status[$store_id])) {
			$status[$store_id] = 'unlikely';
		}
	}

	return $status;
}

function get_best_store() {
	$votes = get_votes();
	
	$best_store_id = null;
	$max_vote_count = 0;
	
	foreach ($votes as $store_id => $vote_count) {
		if ($vote_count > $max_vote_count) {
			$max_vote_count = $vote_count;
			$best_store_id = $store_id;
		}
	}
	
	return $best_store_id;
}

function update_service_charges($store_id) {
	global $shop_db;

	$stores = get_stores();
	$store = $stores[$store_id];

	$order_date_quoted = $shop_db->quote(get_current_order_date());
	$title_quoted = $shop_db->quote($store['service_charge_description']);
	$store_quoted = $shop_db->quote($store_id);

	$query = <<<QUERY
DELETE oi FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
WHERE o.`date` = ${order_date_quoted}
AND oi.`title` = ${title_quoted}
AND oi.`store_id` = ${store_quoted}
QUERY;

	$shop_db->query($query);

	$query = <<<QUERY
SELECT DISTINCT u.`id` AS user_id, u.`email` AS user_email
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `users` u ON u.`id`=o.`user_id`
WHERE o.`date` = ${order_date_quoted}
QUERY;
	$users = $shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC);

	$query = <<<QUERY
SELECT u.`id` AS user_id, u.`name` AS user_name, u.`email` AS user_email, oi.`title`, oi.`price`, oi.`store_id`, oi.`order_id`, oi.`direct_debit_done`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `users` u ON u.`id`=o.`user_id`
WHERE o.`date` = ${order_date_quoted}
QUERY;
	$items = $shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC);

	$service_fee = $store['service_charge_amount'];
	if (bccomp($service_fee, '0') == 0)
		return;

	foreach ($users as $user) {
		$user_id = $user['user_id'];
		foreach ($stores as $ustore_id => $store) {
			if ($store_id != $ustore_id)
				continue;

			$amount = '0';
			$total_amount = '0';
			foreach ($items as $item) {
				if ($item['store_id'] != $store_id)
					continue;

				$total_amount = bcadd($total_amount, $item['price']);

				if ($item['user_id'] != $user_id)
					continue;

				$amount = bcadd($amount, $item['price']);
			}

			if (bccomp($amount, '0') == 0)
				continue;

			$fee = bcmul(bcdiv($amount, $total_amount), $service_fee);
			if (bccomp($fee, $service_fee) < 0)
				$fee = bcadd($fee, '0.01', 2);
			add_item($user_id, $store_id, $store['service_charge_description'], $fee, true);
		}
	}
}

function update_rebate($uid, $store_id) {
	global $shop_db;

	$stores = get_stores();
	$store = $stores[$store_id];

	$uid_quoted = $shop_db->quote($uid);
	$order_date_quoted = $shop_db->quote(get_current_order_date());
	$store_quoted = $shop_db->quote($store_id);

	$query = <<<QUERY
DELETE oi FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
WHERE o.`date` = ${order_date_quoted}
AND o.`user_id` = ${uid_quoted}
AND oi.`store_id` = ${store_quoted}
AND oi.`rebate` = 1
QUERY;

	$shop_db->query($query);

	$query = <<<QUERY
SELECT SUM(oi.price) AS amount
FROM order_items oi
LEFT JOIN orders o ON o.id=oi.order_id
WHERE o.`date` = ${order_date_quoted}
AND o.`user_id` = ${uid_quoted}
AND oi.`store_id` = ${store_quoted}
AND oi.`rebate` = 0
AND oi.`fee` = 0
QUERY;
	$amount = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC)['amount'];

	$rebate_pct = $store['rebate_percent'];
	$rebate_amount = bcdiv(bcmul($amount, $rebate_pct), -100);

	if (bccomp($rebate_amount, '0') != 0)
		add_item($uid, $store_id, 'Rabatt (' . $rebate_pct . '%)', $rebate_amount, false, true);
}

function get_current_merchant_order() {
	global $shop_db;

	$store_status = get_store_status();

	$order_date_quoted = $shop_db->quote(get_current_order_date());

	$query = <<<QUERY
SELECT DISTINCT u.`id` AS user_id, u.`email` AS user_email
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `users` u ON u.`id`=o.`user_id`
WHERE o.`date` = ${order_date_quoted}
QUERY;
	$users = $shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC);

	$query = <<<QUERY
SELECT u.`name` AS user_name, u.`email` AS user_email, oi.`title`, oi.`price`, oi.`store_id`, oi.`order_id`, oi.`direct_debit_done`, oi.`rebate`, oi.`fee`
FROM `order_items` oi
LEFT JOIN `orders` o ON o.`id`=oi.`order_id`
LEFT JOIN `users` u ON u.`id`=o.`user_id`
WHERE o.`date` = ${order_date_quoted}
QUERY;
	$items = $shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
	
	$order = [];

	foreach ($users as $user) {
		$user_id = $user['user_id'];
		$user_email = $user['user_email'];
		
		$user_order = get_current_order($user_id);
		
		foreach (get_normalized_store_priorities($user_order['id']) as $prio_info) {
			$store_id = $prio_info['store_id'];
			$status = $store_status[$store_id];

			if ($status != 'guaranteed' && $status != 'probably') {
				continue;
			}
			
			$found_user_items = false;

			$amount = 0;

			foreach ($items as $item) {
				if ($item['store_id'] != $store_id) {
					continue;
				}

				if ($item['user_email'] == $user_email) {
					$order[] = $item;
					$found_user_items = true;
				}
			}
			
			if ($found_user_items) {
				break;
			}
		}
	}

	return $order;
}

function get_order_status() {
	global $shop_db;
	
	$order_date_quoted = $shop_db->quote(get_current_order_date());
	
	$query = <<<QUERY
SELECT `status`
FROM `order_status`
WHERE `date` = ${order_date_quoted}
QUERY;
	$row = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC);
	if ($row === false) {
		return true;
	} else {
		return $row['status'] == 'Open';
	}
}

function set_order_status($status) {
	global $shop_db;
	
	$status_quoted = $shop_db->quote($status ? 'Open' : 'Closed');
	$order_date_quoted = $shop_db->quote(get_current_order_date());

	$query = <<<QUERY
INSERT INTO `order_status`
(`date`, `status`)
VALUES
(${order_date_quoted}, ${status_quoted})
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`)
QUERY;
	$shop_db->query($query);
}

function has_order_for_shop($email, $store_id) {
    if (get_order_status()) {
        $uid = get_user_attr($email, 'id');
        $items = get_current_order($uid)['items'];
        foreach ($items as $item) {
            if ($item['store_id'] == $store_id) {
                return true;
            }
        }
    } else {
        $items = get_current_merchant_order();
        foreach ($items as $item) {
            if ($item['store_id'] == $store_id && $item['user_email'] == $email) {
                return true;
            }
        }
    }
    return false;
}

