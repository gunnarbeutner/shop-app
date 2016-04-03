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

<script src="/vendor/vakata/jstree/dist/jstree.min.js"></script>
<link rel="stylesheet" href="/vendor/vakata/jstree/dist/themes/default/style.min.css" />

<?php

$store_id = $params['store']['id'];

function print_menu_nodes($parent = null) {
    global $params, $store_id;

?><ul><?php

    $groups = [];

    foreach ($params['articles'] as $g_article_id => $g_article) {
        if ($g_article['parent_article_id'] != $parent) {
            continue;
        }

        $group_id = $g_article['article_group_id'];

        if (in_array($group_id, $groups)) {
            continue;
        }

        $groups[] = $group_id;

        $group_title = htmlentities($params['groups'][$group_id]['title']);

        if ($params['groups'][$group_id]['required']) {
            $required_info = '';
        } else {
            $required_info = ' (optional)';
        }

        echo <<<HTML
<li data-group="${group_id}" data-store="${store_id}" data-parent="${parent}">${group_title}${required_info}
<ul>
HTML;

        foreach ($params['articles'] as $article_id => $article) {
            if ($article['article_group_id'] != $group_id || $article['parent_article_id'] != $parent) {
                continue;
            }

            $title_quoted = htmlentities($article['title']);

            echo <<<HTML
<li data-jstree='{"icon":"fa fa-cog"}' data-article="{$article_id}">${title_quoted}
HTML;
            print_menu_nodes($article_id);

            echo '</li>';
        }

        echo '</ul>';
        echo '</li>';
    }

?></ul><?php
}

?>

<div style="display: flex;">
  <div id="menu-tree" style="float: left; margin-right: 30px;">
    <ul>
      <li id="parent" data-jstree='{"opened":true,"selected":true}' data-store="<?php echo $params['store']['id']; ?>" data-group=""><?php echo htmlentities($params['store']['name']); ?>
<?php
  print_menu_nodes();
?>
      </li>
    </ul>
  </div>

  <iframe style="min-height: 600px; width: 100%;" id="menu-editor" frameBorder="0"></iframe>
</div>

<script>
$(
  function () {
    $('#menu-tree')
      .on('changed.jstree', function (e, data) {
        var node = data.instance.get_node(data.selected[0]).li_attr
        if (node['data-article'] !== undefined)
            $('#menu-editor').attr('src', '/app/menu-edit?nl=1&article=' + node['data-article']);
        else if (node['data-parent'] !== undefined)
            $('#menu-editor').attr('src', '/app/menu-new?nl=1&parent=' + node['data-parent'] + '&group=' + node['data-group']);
        else if (node['data-store'] !== undefined)
            $('#menu-editor').attr('src', '/app/menu-new?nl=1&store=' + node['data-store'] + '&group=' + node['data-group']);
      })
      .jstree();
  }
);
</script>

