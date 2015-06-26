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

ob_clean();

$info = [];

$num = 0;

foreach ($params['stores'] as $store_id => $store) {
	$num++;
	if (isset($params['votes'][$store_id])) {
		$votes = (int)$params['votes'][$store_id];
	} else {
		$votes = 0;
	}
	if ($store['merchant_name'] === null) {
		continue;
	}
	$info[] = [ 'store' => $store, 'votes' => $votes ];
}

function info_cmp($a, $b) {
	if ($a['votes'] == $b['votes']) {
		if ($a['store_id'] == $b['store_id'])
			return 0;
		else if ($a['store_id'] < $b['store_id'])
			return -1;
		else
			return 1;
	} else if ($a['votes'] < $b['votes'])
		return 1;
	else
		return -1;
}

usort($info, 'info_cmp');

header('Content-type: application/json');
echo json_encode($info);
ob_end_flush();
exit(0);

?>