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
require_once('helpers/article.php');

?>

<h1>Mittagsmen&uuml;</h1>

<?php
	$stores = [];
	foreach ($params['stores'] as $store_id => $store) {
		if ($store['merchant_email'] === null) {
			continue;
		}

		$stores[$store_id] = $store;
	}

	$csrf_token = csrf_token();

	if ($params['order_status']) {
		$hour = date('G');
		$minute = intval(date('i'));
?>
<p>Bestellschluss: <?php if (($hour == 13 && $minute > 30) || $hour > 13) { echo "morgen, "; } ?>11:15 Uhr</p>
<?php
		if (count($stores) > 1) { ?>
<p><strong>Es ist m&ouml;glich, f&uuml;r mehrere L&auml;den Bestellungen einzustellen.
  Bei Bestellschluss wird pro Benutzer jedoch nur bei einem Laden eine Bestellung aufgegeben.</strong></p>
<?php
		}
	} else {
?>
<p>F&uuml;r heute werden leider keine neuen Bestellungen mehr angenommen.</p>
<?php	
	}
	
	$ids = [];
	foreach ($params['order']['store_prio'] as $info) {
		$ids[] = $info['store_id'];
	}
	foreach ($stores as $store) {
		if (in_array($store['id'], $ids)) {
			continue;
		}
		
		$ids[] = $store['id'];
	}

    $insurance_fee = get_insurance_fee();

	$index = 0;
	foreach ($ids as $id) {
		if (!$params['order_status'] && $id != $params['best_store']) {
			continue;
		}
		
		$store = null;
		foreach ($stores as $store) {
			if ($id != $store['id'] || $store['merchant_name'] === null) {
				continue;
			}
			
			break;
		}
		
		if ($store === null || $store['id'] != $id) {
			continue;
		}

        $sum = '0';

		$items = [];
		foreach ($params['order']['items'] as $item) {
			if ($item['store_id'] != $store['id']) {
				continue;
			}
            $sum = bcadd($sum, $item['price']);
			$items[] = $item;
		}

        $fee = bcmul(get_store_fee_multiplier($store['id'], true), $sum);
        if (bccomp($fee, '0') != 0) {
            $sum = bcadd($sum, $fee);
            $items[] = [ 'title' => $store['service_charge_description'], 'price' => $fee, 'protected' => true ];
        }

        $rebate = bcmul(get_store_rebate_multiplier($store['id']), $sum);
        if (bccomp($rebate, '0') != 0) {
            $items[] = [ 'title' => 'Rabatt (' . $store['rebate_percent'] . '%)', 'price' => $rebate, 'protected' => true ];
        }

        if (bccomp($sum, '0') != 0 && bccomp($insurance_fee, '0') != 0) {
            $sum = bcadd($sum, $insurance_fee);
            $items[] = [ 'title' => 'Servicegebühr', 'price' => $insurance_fee, 'protected' => true ];
        }

		$index++;

		if (count($stores) > 1 && count($items) > 0 && $params['order_status']) {
			$prio = "${index}. Wahl: ";
		} else {
			$prio = '';
		}

		$html = <<<HTML
  <h2>%s%s</h2>
				
  <p>Beschreibung: %s</p>
  <p title="%s">Anbieter: %s</p>
  <p>%s</p>
  <p style="%s">Ladenstatus: %s <span class="aui-icon aui-icon-small aui-iconfont-help tooltip" title="%s"></span></p>
HTML;

		if (isset($params['votes'][$store['id']])) {
			$votes = $params['votes'][$store['id']];
			$names = [];
			foreach (get_primary_votes_for_store($store['id']) as $info) {
				$names[] = htmlentities($info['user_name']);
			}
			$vote_users = implode(', ', $names);
			if ($votes != 1) {
				$vote_info = $votes . " Stimmen";
			} else {
				$vote_info = "1 Stimme";
			}
			$vote_info .= ": " . $vote_users;
		} else {
			$votes = 0;
			$vote_users = '';
			$vote_info = 'Bisher keine Stimmen';
		}
		
		$status = $params['store_status'][$store['id']];

		$status_css = "";

		if ((int)date('Gi') < 1045 || (int)date('Gi') > 1330)
			$status_css = "display: none;";

		switch ($status) {
			case 'guaranteed':
				$status_name = '<strong>Garantierte Bestellung</strong>';
				$status_popup = 'Für diesen Laden werden heute Bestellungen unabhängig von der Stimmzahl ausgeführt.';
				$vote_html_template = '';
				break;
			case 'probably':
				$status_name = '<strong>Wahrscheinliche Bestellung</strong>';
				$status_popup = 'Sofern sich die Stimmanzahl nicht ändert, wird bei diesem Laden bestellt.';
				break;
			case 'unlikely':
				$status_name = 'Wahrscheinlich keine Bestellung';
				$status_popup = 'Der Laden hat momentan zu wenige Stimmen oder der Mindestumsatz ist noch nicht erreicht, um für eine Bestellung in Betracht gezogen zu werden.';
				break;
		}
		
		printf($html, $prio,
			htmlentities($store['name']), $store['description'],
			htmlentities($store['merchant_email']), htmlentities($store['merchant_name']),
			$vote_info, $status_css, $status_name, $status_popup);

		$service_fee = $store['service_charge_amount'];
		if (bccomp($service_fee, '0') != 0) {
?>
<p>F&uuml;r diesen Laden f&auml;llt eine Liefergeb&uuml;hr in H&ouml;he von <?php echo format_number($service_fee); ?>&euro; an, die zwischen allen Bestellern aufgeteilt wird: <?php echo $store['service_charge_description']; ?></p>
<?php
		}

		$rebate_pct = $store['rebate_percent'];
		if (bccomp($rebate_pct, '0') != 0) {
?>
<p>F&uuml;r diesen Laden gibt es heute auf alle Artikel <?php echo $rebate_pct; ?>% Rabatt.</p>
<?php
		}

		$up_button = ($index != 1 && $index <= count($params['order']['store_prio']));
		$down_button = ($index != count($stores) && $index < count($params['order']['store_prio']));
		
		if ($params['order_status'] && ($up_button ||$down_button)) {
?>
  <h3>Position</h3>
  <p>
  <div class="aui-buttons"> 
<?php

			if ($up_button) {
?>

    <form class="aui" method="post" action="/app/merchant-priority" style="display: inline;">
      <input type="hidden" name="store" value="<?php echo htmlentities($store['id']); ?>">
	  <input type="hidden" name="direction" value="up">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
      <button type="submit" class="aui-button">
        <i class="fa fa-arrow-up"></i> Nach oben verschieben
      </button>
    </form>
<?php
			}

			if ($down_button) {
?>
    <form class="aui" method="post" action="/app/merchant-priority" style="display: inline;">
      <input type="hidden" name="store" value="<?php echo htmlentities($store['id']); ?>">
      <input type="hidden" name="direction" value="down">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
      <button type="submit" class="aui-button">
        <i class="fa fa-arrow-down"></i> Nach unten verschieben
      </button>
    </form>
<?php
			}
?>
  </div>
  </p>
<?php
		}
		
?>
  <h3>Bestellung</h3>
<?php
		if (count($items) > 0) {
?>
  <table class="aui">
    <tr>
      <th>Beschreibung</th>
      <th>Preis (&euro;)</th>
<?php
			if ($params['order_status']) {
?>
      <th>Aktionen</th>
<?php
			}
?>
    </tr>
<?php

			foreach ($items as $item) {
				if (!array_key_exists('protected', $item) || !$item['protected']) {
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

				$actions = <<<HTML
      <div class="aui-buttons">
        ${order_buttons}
      </div>
HTML;

			$html = <<<HTML
    <tr>
	  <td>%s</td>
	  <td>%s</td>
HTML;
			
			if ($params['order_status']) {
				$html .= <<<HTML
	  <td>${actions}</td>
HTML;
			}

			$html .= <<<HTML
	</tr>
HTML;
	  
				printf($html,
					htmlentities($item['title']), format_number($item['price']));
			}
?>
    <tr style="border-top: 2px solid black;">
      <td><b>Summe</b></td>
      <td><b><?php echo format_number(bcadd($sum, $rebate)); ?></b></td>
<?php if ($params['order_status']) { ?>
      <td></td>
<?php } ?>
    </tr>
  </table>
<?php
		} else if (!$params['order_status']) {
?>
  <p>Es liegen keine Bestellungen f&uuml;r diesen Laden vor.</p>
<?php
		}
		
		if ($params['order_status']) {
?>
  
  <p>
    <form action="/app/order-store">
      <input type="hidden" name="store" value="<?php echo $store['id']; ?>">
      <button class="aui-button aui-button-primary">Neue Bestellung</button>
    </form>
  </p>
  
<?php
		}

		if (!$params['order_status'] && $store['status_message'] != '') {
?>

<h3>Status</h3>

<p><strong><?php echo $store['status_message']; ?></strong></p>
<?php
			if ($store['tracking_id'] != 0) {
?>
<br>

<div style='background-color:#666;'><a id='ltw-home-url' href='http://locatoweb.com'>LocaToWeb - Real time GPS tracking</a>
<iframe id='ltw-embed-frame' frameborder='0' style='width:100%;height:600px;'></iframe>
<script>var ltwtrackurl='single/<?php echo htmlentities($store['tracking_id']); ?>';</script>
<script src='//az664202.vo.msecnd.net/scripts/embed-1.0.9.min.js'></script></div>
<?php
			}
		}
	}
?>
<script type="text/javascript">
	AJS.$(".tooltip").tooltip();
</script>
