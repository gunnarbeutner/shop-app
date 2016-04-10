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

<h1>Men&uuml; bearbeiten</h1>

<form class="aui" method="post" action="/app/menu-edit">
  <textarea name="menu" style="font-family:Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace; width: 800px; height: 600px;"><?php echo htmlentities($params['menu']); ?></textarea>
  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <button type="submit" class="aui-button">
        <i class="fa fa-check"></i> Aktualisieren
      </button>
    </div>
  </div>
</form>
