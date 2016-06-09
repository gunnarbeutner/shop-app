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

$csrf_token = csrf_token();

if ($format == 'pdf') {
	$page_layout = 'landscape';

?>
<style type="text/css">
  table {
    border-spacing: 0.5rem;
    font-size: 18pt;
  }

  td {
    border-bottom: 1px dashed;
  }

  #table-checkbox {
    width: 30px;
  }

  #table-user {
    width: 200px;
  }

  #table-article {
    width: 570px;
  }

  #table-price {
    width: 200px;
  }
</style>
<?php
} else {
?>
<style type="text/css">
  td {
    position: relative;
    padding: 5px 10px;
  }

  tr.strikeout td:before {
    content: " ";
    position: absolute;
    top: 50%;
    left: 0;
    border-bottom: 2px solid #111;
    width: 100%;
  }

  tr.strikeout td {
    background: #ff9966;
  }
</style>
<?php
}
?>

<h1>Auftragsliste</h1>

<?php
function cmp_item($a, $b) {
	return strcmp($a['title'], $b['title']);
}

foreach ($params['stores'] as $store_id => $store) {
	$items = [];

	foreach ($params['order'] as $item) {
		if ($item['store_id'] == $store_id) {
            $item['ignored'] = false;
			$items[] = $item;
		}
	}

    if ($format != 'pdf') {
    	foreach ($params['ignored_order'] as $item) {
    		if ($item['store_id'] == $store_id) {
                $item['ignored'] = true;
    			$items[] = $item;
    		}
    	}
    }

	usort($items, 'cmp_item');

	if (count($items) > 0 && (!isset($_REQUEST['store']) || $_REQUEST['store'] == $store['id'])) {
?>
<p>
  Laden: <a href="/app/merchant-orders?store=<?php echo $store['id']; ?>"><?php echo htmlentities($store['name']); ?></a>
<?php if ($format != 'pdf') { ?>
  (<a href="/app/merchant-orders.pdf?store=<?php echo $store['id']; ?>" target="_blank">PDF-Download</a>)
<?php } ?>
</p>

<table class="aui aui-table-sortable zebra" id="stores">
  <thead>
    <tr>
<?php if ($format == 'pdf') { ?>
      <td id="table-checkbox">&nbsp;</td>
<?php } ?>
      <th id="table-user">Benutzer</th>
      <th id="table-article">Beschreibung</th>
      <th id="table-price">Preis (&euro;)</th>
<?php if ($format != 'pdf' && get_user_attr(get_user_email(), 'merchant')) { ?>
      <th>Zuletzt bearbeitet</th>
      <th>Lastschrift</th>
      <th class="aui-table-column-unsortable">Aktionen</th>
<?php } ?>
   </tr>
  </thead>
  <tbody>
<?php
		$sum = 0;
		$sum_fee = 0;
		$sum_rebate = 0;

		foreach ($items as $item) {
            if (!$item['ignored']) {
    			$sum = bcadd($sum, $item['price']);
            }

			$html = <<<HTML
  <tr class="%s">
HTML;

			if ($format == 'pdf' && get_user_attr(get_user_email(), 'merchant')) {
				$html .= <<<HTML
      <td><div style="width: 18pt; height: 18pt; border: 1px solid #000;"></div></td>
HTML;
			}
			$html .= <<<HTML
      <td title="%s">%s</td>
      <td>%s</td>
      <td>%s</td>
HTML;


			if ($format != 'pdf' && get_user_attr(get_user_email(), 'merchant')) {
				$html .= <<<HTML
      <td>%s</td>
      <td>%s</td>
      <td>%s</td>
HTML;
			}

			$html .= <<<HTML
  </tr>

HTML;
		
			if ($item['direct_debit_done']) {
				$direct_debit_status = '<span class="aui-icon aui-icon-small aui-iconfont-approve" style="color: green;"></span> Ausgef&uuml;hrt';
			} else {
				$direct_debit_status = '<span class="aui-icon aui-icon-small aui-iconfont-remove" style="color: red;"></span> Noch nicht ausgef&uuml;hrt';
			}

			if (!$item['direct_debit_done']) {
				$order_buttons = <<<HTML
        <div style="float: left; padding-right: 10px;">
          <form method="get" action="/app/order-edit" style="display: inline;">
            <input type="hidden" name="item" value="${item['id']}">
            <button type="submit" class="aui-button">
              <i class="fa fa-edit"></i> Bearbeiten
            </button>
          </form>
        </div>

        <div style="float: left;">
          <form method="post" action="/app/order-remove" style="display: inline;">
            <input type="hidden" name="item" value="${item['id']}">
            <input type="hidden" name="csrf_token" value="${csrf_token}">
            <button type="submit" class="aui-button">
              <i class="fa fa-remove"></i> Entfernen
            </button>
          </form>
        </div>
HTML;
			} else {
				$order_buttons = '';
			}

			printf($html,
                $item['ignored'] ? "strikeout" : "",
				htmlentities($item['user_email']), htmlentities($item['user_name']),
				htmlentities($item['title']), format_number($item['price']),
				htmlentities($item['modified']), $direct_debit_status, $order_buttons);
		}

		$sum_fee = bcmul(get_store_fee_multiplier($store_id, false), $sum);
		$sum_rebate = bcmul(get_store_rebate_multiplier($store_id), bcadd($sum, $sum_fee));
?>
  </tbody>
</table>

<p>Summe ohne Rabatte/Liefergeb&uuml;hr (&euro;): <?php echo format_number($sum); ?></p>

<?php if (bccomp($sum_fee, '0') != 0) { ?>
<p>Liefergeb&uuml;hr/Trinkgeld (&euro;): <?php echo format_number($sum_fee); ?></p>
<?php } ?>

<?php if (bccomp($sum_rebate, '0') != 0) { ?>
<p>Interne Rabatte (&euro;): <?php echo format_number($sum_rebate); ?></p>
<?php } ?>

<br />

<p>
<?php
	}
}

	if ($format != 'pdf' && get_user_attr(get_user_email(), 'merchant')) {
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
