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

<h1>Status bearbeiten: <?php echo htmlentities($params['store']['name']); ?></h1>

<form class="aui" method="post" action="/app/store-edit-status">
  <div class="field-group">
    <label for="text">Status</label>
    <textarea class="textarea" name="text"><?php echo htmlentities($params['store']['status_message']); ?></textarea>
  </div>
  <div class="field-group">
    <div class="checkbox">
      <label for="broadcast">Benachrichtigung per Jabber versenden</label>
      <input type="checkbox" class="checkbox" name="broadcast" value="yes">
    </div>
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
