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

?>

<h1>Fehler</h1>

<p>Es ist ein Fehler aufgetreten: <?php echo htmlentities($params['message']); ?></p>

<p>
<?php if (!isset($params['back']) || $params['back']) { ?>
<a href="javascript:history.back();">Zur&uuml;ck</a>
<?php } else { ?>
<a href="/app/order">Zur Bestell&uuml;bersicht</a>
<?php } ?>
</p>
