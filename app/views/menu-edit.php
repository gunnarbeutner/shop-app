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

<h1>Artikel bearbeiten: <?php echo htmlentities($params['article']['title']); ?></h1>

<?php if ($params['article']['parent_article_id'] == '') { ?>
<p>
  <form action="/app/menu-new">
    <input type="hidden" name="nl" value="1">
    <input type="hidden" name="parent" value="<?php echo $params['article']['id']; ?>">
    <button class="aui-button aui-button-primary">Neue Men&uuml;option anlegen</button>
  </form>
</p>
<?php } ?>

<form class="aui" method="post" action="/app/menu-edit">
  <div class="field-group">
    <label for="title">Name</label>
    <input class="text" type="text" name="title" value="<?php echo htmlentities($params['article']['title']); ?>">
  </div>
  <div class="field-group">
    <label for="description">Beschreibung</label>
    <textarea class="textarea" name="description"><?php echo htmlentities($params['article']['description']); ?></textarea>
  </div>
  <div class="field-group">
    <label for="price">Preis</label>
    <input class="text" type="text" name="price" value="<?php echo format_number($params['article']['price'], false); ?>">
  </div>
  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="nl" value="1">
      <input type="hidden" name="article" value="<?php echo $params['article']['id']; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <button type="submit" class="aui-button">
        <i class="fa fa-check"></i> Aktualisieren
      </button>
    </div>
  </div>
</form>
