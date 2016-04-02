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

$item_id = $params['item_id'];
$item = $params['item'];
$email = $params['email'];
$store_id = $item['store_id'];
$title = $item['title'];
$price = format_number($item['price'], false);
$store = get_stores()[$store_id];
$csrf_token = csrf_token();

?>

<h1>Bestellung bearbeiten</h1>

<form class="aui" method="post" action="/app/order-edit">
<?php if (get_user_attr(get_user_email(), 'merchant')) { ?>
  <div class="field-group">
    <label for="edit-email">Benutzer</label>
    <input class="text address" type="text" name="email" id="edit-email" value="<?php echo htmlentities($email); ?>">
  </div>
<?php } ?>
  <div class="field-group">
    <label for="edit-title-<?php echo $store_id; ?>">Beschreibung</label>
    <input class="text article" type="text" name="title" id="edit-title" value="<?php echo htmlentities($title); ?>">
  </div>
  <div class="field-group">
    <label for="edit-price-<?php echo $store_id; ?>">Preis (&euro;)</label>
    <input class="text small-field" type="text" name="price" id="edit-price" value="<?php echo htmlentities($price); ?>">
  </div>
  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="item" value="<?php echo $item_id; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
      <button type="submit" class="aui-button">
      <i class="fa fa-check"></i> &Auml;ndern
      </button>
    </div>
  </div>
</form>
