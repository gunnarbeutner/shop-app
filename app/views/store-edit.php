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

<h1>Laden bearbeiten: <?php echo htmlentities($params['store']['name']); ?></h1>

<form class="aui" method="post" action="/app/store-edit">
  <div class="field-group">
    <label for="description">Beschreibung</label>
    <textarea class="textarea" name="description"><?php echo htmlentities($params['store']['description']); ?></textarea>
  </div>
  <div class="field-group">
    <label for="min_order_count">Mindestbestell-anzahl</label>
    <input class="text" type="text" name="min_order_count" value="<?php echo htmlentities($params['store']['min_order_count']); ?>">
  </div>
  <div class="field-group">
    <label for="min_order_volume">Mindestumsatz (&euro;)</label>
    <input class="text" type="text" name="min_order_volume" value="<?php echo htmlentities($params['store']['min_order_volume']); ?>">
  </div>
  <div class="field-group">
    <label for="service_charge_amount">Liefergeb&uuml;hr (&euro;)</label>
    <input class="text" type="text" name="service_charge_amount" value="<?php echo htmlentities($params['store']['service_charge_amount']); ?>">
  </div>
  <div class="field-group">
    <label for="service_charge_description">Liefergeb&uuml;hr (Beschreibung)</label>
    <textarea class="textarea" name="service_charge_description"><?php echo htmlentities($params['store']['service_charge_description']); ?></textarea>
  </div>
  <div class="field-group">
    <label for="rebate_percent">Rabatt (%)</label>
    <input class="text" type="text" name="rebate_percent" value="<?php echo htmlentities($params['store']['rebate_percent']); ?>">
    <div class="description">&Auml;nderungen des Rabattsatzes m&uuml;ssen mit Gunnar oder Marius abgesprochen werden.</div>
  </div>
  <div class="field-group">
    <label for="tracking_id">Tracking-ID</label>
    <input class="text" type="text" name="tracking_id" value="<?php echo htmlentities($params['store']['tracking_id']); ?>">
  </div>
  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="store" value="<?php echo $params['store']['id']; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <button type="submit" class="aui-button">
        <i class="fa fa-check"></i> Aktualisieren
      </button>
    </div>
  </div>
</form>
