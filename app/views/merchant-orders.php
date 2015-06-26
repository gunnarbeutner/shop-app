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

?>

<h1>Auftragsliste</h1>

<?php
foreach ($params['stores'] as $store_id => $store) {
	$items = [];
	foreach ($params['order'] as $item) {
		if ($item['store_id'] == $store_id) {
			$items[] = $item;
		}
	}
	
	if (count($items) > 0) {
?>
<p>Laden: <?php echo htmlentities($store['name']); ?></p>

<table class="aui zebra" id="stores">
  <tr>
    <th>Benutzer</th>
    <th>Beschreibung</th>
	<th>Preis (&euro;)</th>
	<th>Lastschrift</th>
 </tr>
<?php

		$csrf_token = csrf_token();

		$sum = 0;
		
		foreach ($items as $item) {
			$html = <<<HTML
  <tr>
    <td title="%s">%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
  </tr>

HTML;
		
			if ($item['direct_debit_done']) {
				$direct_debit_status = '<span class="aui-icon aui-icon-small aui-iconfont-approve" style="color: green;"></span> Ausgef&uuml;hrt';
			} else {
				$direct_debit_status = '<span class="aui-icon aui-icon-small aui-iconfont-remove" style="color: red;"></span> Noch nicht ausgef&uuml;hrt';
			}
			
			printf($html,
				htmlentities($item['user_email']), htmlentities($item['user_name']),
				htmlentities($item['title']), format_number($item['price']),
				$direct_debit_status);
			
			$sum = bcadd($sum, $item['price']);
		}
?>
</table>

<p>Summe (&euro;): <?php echo format_number($sum); ?></p>

<br />

<p>
<?php
	}
}

	if ($format != 'pdf') {
		if (get_order_status()) {
?>
  <form class="aui" method="post" action="/app/order-status" style="display: inline;">
    <input type="hidden" name="status" value="0">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <button type="submit" class="aui-button">
      <i class="fa fa-pause"></i> Bestellung abschlie&szlig;en
    </button>
  </form>
<?php
		} else {
?>
  <form class="aui" method="post" action="/app/order-status" style="display: inline;">
    <input type="hidden" name="status" value="1">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <button type="submit" class="aui-button">
      <i class="fa fa-play"></i> Bestellung wieder &ouml;ffnen
    </button>
  </form>
<?php
		}
	}
?>
</p>
