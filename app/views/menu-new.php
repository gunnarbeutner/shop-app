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

<h1>Neuer Artikel f&uuml;r <?php echo htmlentities($params['store']['name']); ?></h1>

<form class="aui" method="post" action="/app/menu-new">
  <div class="field-group">
    <label for="title">Name</label>
    <input class="text" type="text" name="title">
  </div>
  <div class="field-group">
    <label for="description">Beschreibung</label>
    <textarea class="textarea" name="description"></textarea>
  </div>
  <div class="field-group">
    <label for="price">Preis</label>
    <input class="text" type="text" name="price" value="0,00">
  </div>
  <div class="field-group">
    <label for="group">Artikelgruppe</label>
<?php if ($params['group'] != null) { ?>
    <span id="parent"><?php echo htmlentities($params['group']['title']); ?></span>
<?php } else { ?>
<fieldset>
<table>
  <tr>
    <th>Name</th>
<?php if ($params['parent'] != null) { ?>
    <th>Auswahl erforderlich</th>
    <th>Y-Sortierung</th>
<?php } ?>
  </tr>
<?php
  foreach (get_article_groups() as $group_id => $group) {
    if ($params['parent'] == null && (!$group['required'] || $group['yorder'] != 1)) {
        continue;
    } else if ($params['parent'] != null && $group['yorder'] == 1) {
        continue;
    }

    $title_quoted = htmlentities($group['title']);
    $required_quoted = $group['required'] ? 'Ja' : 'Nein';
    $yorder_quoted = htmlentities($group['yorder']);

    echo <<<HTML
  <tr>
    <td><input type="radio" name="group" value="${group_id}">${title_quoted}</td>
HTML;

    if ($params['parent'] != null) {
        echo <<<HTML
    <td>${required_quoted}</td>
    <td>${yorder_quoted}</td>
HTML;
    }

    echo <<<HTML
  </tr>
HTML;
  }
?> 
</table>
</fieldset>
<?php } ?>
  </div>
<?php if ($params['parent']) { ?>
  <div class="field-group">
    <label for="parent">&Uuml;bergeordneter Artikel</label>
    <span id="parent"><?php echo htmlentities($params['parent']['title']); ?></span>
  </div>
<?php } ?>
  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="nl" value="1">
<?php if ($params['group'] != null) { ?>
      <input type="hidden" name="group" value="<?php echo htmlentities($params['group']['id']); ?>">
<?php } ?>
<?php if ($params['parent'] != null) { ?>
      <input type="hidden" name="parent" value="<?php echo htmlentities($params['parent']['id']); ?>">
<?php } ?>
      <input type="hidden" name="store" value="<?php echo htmlentities($params['store']['id']); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <button type="submit" class="aui-button">
        <i class="fa fa-check"></i> Erstellen
      </button>
    </div>
  </div>
</form>
