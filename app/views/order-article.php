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

$store = get_stores()[$params['store_id']];

?>

<h1>Neue Bestellung</h1>

<?php
$service_fee = $store['service_charge_amount'];
if (bccomp($service_fee, '0') != 0) {
?>

<p>F&uuml;r diesen Laden f&auml;llt eine Liefergeb&uuml;hr in H&ouml;he von <?php echo format_number($service_fee); ?>&euro; an, die zwischen allen Bestellern aufgeteilt wird: <?php echo $store['service_charge_description']; ?></p>

<?php
}

foreach ($params['groups'] as $group_id => $group) {
	$count = 0;
	foreach ($params['article'] as $article_id => $article) {
		if ($article['article_group_id'] == $group_id) {
			$count++;
		}
	}
	if ($count == 0) {
		continue;
	}

?>
<h2><?php echo htmlentities($group['title']); ?></h2>

<p><?php echo htmlentities($group['description']); ?></p>

<form class="aui" method="post" action="/app/order-article">
<?php

	$required = $group['required'];

	if ($count == 1 && $required) {
?>
<p>
<?php
	} else {
?>
<fieldset class="group">
<?php
	}

	$first = true;
	foreach ($params['article'] as $article_id => $article) {
		if ($article['article_group_id'] != $group_id) {
			continue;
		}

		if (bccomp($article['price'], '0') != 0) {
			$price = ' - ' . format_number($article['price']) . '&euro;';
		} else {
			$price = '';
		}

		if ($article['description'] != '') {
			$description_html = ' (' . htmlentities($article['description']) . ')';
		} else {
			$description_html = '';
		}

		if ($count == 1 && $required) {
			echo htmlentities($article['title']);
			echo '<span style="font-size: 8pt;">' . $description_html . '</span>';
			echo $price;
?>
  <input type="hidden" name="group_<?php echo $group_id; ?>[]" value="<?php echo $article_id; ?>">
<?php
		} else {
			if ($required) {
				$type = 'radio';
			} else {
				$type = 'checkbox';
			}

			if ($first && $required) {
				$first = false;
				$checked = 'checked="checked" ';
			} else {
				$checked = '';
			}
?>
  <div class="<?php echo $type; ?>">
    <input class="<?php echo $type; ?>" type="<?php echo $type; ?>" name="group_<?php echo $group_id; ?>[]" value="<?php echo $article_id; ?>" id="article_<?php echo $article_id; ?>"<?php echo $checked; ?>>
    <label for="article_<?php echo $article_id; ?>"><?php echo htmlentities($article['title']); ?><span style="font-size: 8pt;"><?php echo $description_html; ?></span><?php echo $price; ?></label>
  </div>
<?php
		}
	}

	if ($count == 1 && $required) {
?>
</p>
<?php
	} else {
?>
</fieldset>
<?php
	}
}

?>
<h3>Bestellkommentar</h3>

<div class="field-group">
  <label for="comment">Kommentar</label>
  <input class="text" type="text" id="comment" name="comment">
</div>

<div class="buttons-container">
  <div class="buttons">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input class="button submit" type="submit" value="Bestellen">
  </div>
</div>
</form>
