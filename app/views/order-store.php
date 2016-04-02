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
require_once('helpers/store.php');

$store_id = $params['store_id'];
$store = get_stores()[$store_id];
$csrf_token = csrf_token();

?>

<h1>Neue Bestellung f&uuml;r <?php echo htmlentities($store['name']); ?></h1>

<?php
$service_fee = $store['service_charge_amount'];
if (bccomp($service_fee, '0') != 0) {
?>

<p>F&uuml;r diesen Laden f&auml;llt eine Liefergeb&uuml;hr in H&ouml;he von <?php echo format_number($service_fee); ?>&euro; an, die zwischen allen Bestellern aufgeteilt wird: <?php echo $store['service_charge_description']; ?></p>

<?php
}

$uarticles = get_primary_articles($store_id);

if (count($uarticles) > 0) {
?>
<h2>Speisekarte</h2>
<ul>
<?php
	$ugroups = [];
	foreach ($uarticles as $uarticle_id => $uarticle) {
		if (in_array($uarticle['group_title'], $ugroups)) {
			continue;
		}

		$ugroups[] = $uarticle['group_title'];
	}

	foreach ($ugroups as $ugroup) {
?><h3><?php echo htmlentities($ugroup); ?></h3><?php
		foreach ($uarticles as $uarticle_id => $uarticle) {
			if ($uarticle['group_title'] != $ugroup) {
				continue;
			}

			if ($uarticle['description'] != '') {
				$description_html = ' (' . htmlentities($uarticle['description']) . ')';
			} else {
				$description_html = '';
			}

?><li><a href="/app/order-article?article=<?php echo $uarticle_id; ?>"><?php echo htmlentities($uarticle['title']); ?></a><span style="font-size: 8pt;"><?php echo $description_html; ?></span></li>
<?php
		}
	}
?>
      </ul>
      <h2>Manuell eingeben</h2>
<?php
}
?>
      <form class="aui" method="post" action="/app/order-add">
<?php if (get_user_attr(get_user_email(), 'merchant')) { ?>
        <div class="field-group">
	      <label for="add-email">Benutzer</label>
	      <input class="text address" type="text" name="email" id="add-email" value="<?php echo htmlentities(get_user_email()); ?>">
        </div>
<?php } ?>
        <div class="field-group">
	      <label for="add-title">Beschreibung</label>
	      <input class="text article" type="text" name="title" id="add-title" data-store="<?php echo $store_id; ?>">
        </div>
        <div class="field-group">
	      <label for="add-price">Preis (&euro;)</label>
	      <input class="text small-field" type="text" name="price" id="add-price">
        </div>
        <div class="buttons-container">
          <div class="buttons">
			<input type="hidden" name="store" value="<?php echo $store_id; ?>">
			<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <button type="submit" class="aui-button">
  		      <i class="fa fa-check"></i> Hinzuf&uuml;gen
            </button>
          </div>
	    </div>
    </form>
  </div>

