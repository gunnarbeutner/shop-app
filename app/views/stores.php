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
require_once('helpers/order.php');

?>

<h1>L&auml;den</h1>

<table class="aui zebra" id="stores">
  <tr>
    <th>Name</th>
	<th>Mindestbestellmenge</th>
	<th>Mindestumsatz (&euro;)</th>
	<th>Anbieter</th>
    <th>Aktionen</th>
 </tr>
<?php

	$csrf_token = csrf_token();

	foreach ($params['stores'] as $store_id => $store) {
		if ($store['merchant_email'] != get_user_email()) {
			$actions = <<<ACTIONS
      <form class="aui" method="post" action="/app/merchant-status">
		<input type="hidden" name="store" value="${store_id}"></input>
		<input type="hidden" name="status" value="1"></input>
        <input type="hidden" name="csrf_token" value="${csrf_token}"></input>
        <input class="submit button" type="submit" value="Als Anbieter setzen"></input>
      </form>
ACTIONS;
		} else {
			$actions = <<<ACTIONS
      <form class="aui" method="post" action="/app/merchant-status">
		<input type="hidden" name="store" value="${store_id}"></input>
		<input type="hidden" name="status" value="0"></input>
		<input type="hidden" name="csrf_token" value="${csrf_token}"></input>
        <input class="submit button" type="submit" value="Als Anbieter entfernen"></input>
      </form>
ACTIONS;
		}
		$html = <<<HTML
  <tr>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td title="%s">%s</td>
    <td>
$actions
    </td>
  </tr>

HTML;

		if ($store['merchant_name'] !== null) {
			$merchant_email = htmlentities($store['merchant_email']);
			$merchant_name = htmlentities($store['merchant_name']);
		} else {
			$merchant_email = '';
			$merchant_name = '&mdash;';
		}
		
		printf($html,
		    htmlentities($store['name']),
			htmlentities($store['min_order_count']), format_number($store['min_order_volume']),
		    $merchant_email, $merchant_name);
	}
?>
</table>
