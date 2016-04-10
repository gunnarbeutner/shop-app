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

require_once('helpers/db.php');
require_once('helpers/store.php');
require_once('helpers/article.php');

function _indent_from_line($line) {
    if (!preg_match('/^( +)/', $line, $matches)) {
        return 0;
    }

    return strlen($matches[1]) / 2;
}

function _parse_menu_helper(&$parent, $lines, &$line_num = 0, $indent = 0) {
    $current = null;

    for (; $line_num < count($lines); $line_num++) {
        $line = $lines[$line_num];
        if (preg_match('/^ *#/', $line) || preg_match('/^ *$/', $line)) {
            continue;
        }

        $line_indent = _indent_from_line($line);

        if (!preg_match('/^ *([a-zA-Z]+)/', $line, $matches)) {
            echo 'Incorrect command in line ' . ($line_num + 1) . ' (' . $line . ")\n";
            return false;
        }

        $command = $matches[1];

        if ($line_indent > $indent + 1 || $line_indent > $indent && $current === null) {
            echo 'Incorrect indent in line ' . ($line_num + 1) . ' (' . $line . ")\n";
            return false;
        }

        if ($line_indent < $indent) {
            return true;
        }

        if ($line_indent > $indent) {
            if (!_parse_menu_helper($current, $lines, $line_num, $indent + 1)) {
                return false;
            }

            $line_num--;

            continue;
        }

        if (preg_match('/^ *[a-zA-Z]+ *(.*)$/', $line, $matches)) {
            $args = $matches[1];
        } else {
            $args = '';
        }

        if ($command == 's') {
            $parent['store'] = $args;
        } else if ($command == 'o' || $command == 'x') {
            if (!array_key_exists('articles', $parent)) {
                $parent['articles'] = [];
            }

            $tokens = explode(':', $args, 2);
            $item_group = trim($tokens[0]);

            $tokens = explode('@', $tokens[1]);
            $item_name = trim($tokens[0]);
            if (count($tokens) > 1) {
                $item_price = str_replace(',', '.', trim($tokens[1]));
            } else {
                $item_price = null;
            }

            $parent['articles'][$item_name] = [
                'type' => $command,
                'group' => $item_group,
            ];

            if ($item_price !== null) {
                $parent['articles'][$item_name]['price'] = $item_price;
            }

            $current = &$parent['articles'][$item_name];
        } else if ($command == 'd') {
            $parent['description'] = $args;
        } else if ($command == 'i') {
            if (!array_key_exists('includes', $parent)) {
                $parent['includes'] = [];
            }

            $parent['includes'][] = trim($args);
        } else if ($command == 't') {
            if (!array_key_exists('templates', $parent)) {
                $parent['templates'] = [];
            }

            $parent['templates'][$args] = [];

            $current = &$parent['templates'][$args];
        }
    }

    return true;
}

function _flatten_template($templates, $include) {
    $result = $templates[$include];

    if (array_key_exists('includes', $result)) {
        foreach ($result['includes'] as $t_include) {
            $result = array_replace_recursive($result, _flatten_template($templates, $t_include));
        }
    }

    return $result;
}

function _resolve_templates_helper(&$parent, $templates) {
    foreach ($parent as $key => $value) {
        if (!is_array($value)) {
            continue;
        }

        if (array_key_exists('includes', $value)) {
            $res = $value;
            foreach ($value['includes'] as $include) {
                $res = array_replace_recursive($res, _flatten_template($templates, $include));
            }
            unset($res['includes']);
            $parent[$key] = $res;
        }

        _resolve_templates_helper($parent[$key], $templates);
    }

    return true;
}

function _resolve_templates(&$tree) {
    if (!array_key_exists('templates', $tree)) {
        $templates = [];
    } else {
        $templates = $tree['templates'];
        unset($tree['templates']);
    }
    return _resolve_templates_helper($tree, $templates);
}

function parse_menu($menu_decl) {
    $lines = explode("\n", $menu_decl);
    $tree = [];
    _parse_menu_helper($tree, $lines);
    if (!_resolve_templates($tree)) {
        return false;
    }
    return $tree;
}

function _find_or_create_article_group($name, $required) {
    foreach (get_article_groups() as $group_id => $group) {
        if ($group['title'] == $name && $group['required'] == $required) {
            return $group['id'];
        }
    }

    if ($required) {
        $yorder = 1;
    } else {
        $yorder = 2;
    }

    return new_article_group($name, '', $required, $yorder);
}

function _import_menu_helper($store_id, &$parent, $old_articles, &$completed) {
    if (array_key_exists('id', $parent)) {
        $parent_article_id = $parent['id'];
    } else {
        $parent_article_id = null;
    }

    foreach ($parent['articles'] as $new_article_name => &$new_article) {
        $found = false;

        foreach ($old_articles as $old_article_id => $old_article) {
            if ($new_article_name != $old_article['title']) {
                continue;
            }

            if ($parent_article_id !== null && $old_article['parent_article_id'] != $parent_article_id) {
                continue;
            }

            $found = true;
            break;
        }

        $required = ($new_article['type'] == 'o');
        $article_group_id = _find_or_create_article_group($new_article['group'], $required);

        if (array_key_exists('description', $new_article)) {
            $description = $new_article['description'];
        } else {
            $description = '';
        }

        if (array_key_exists('price', $new_article)) {
            $price = $new_article['price'];
        } else {
            $price = '0';
        }

        if ($found) {
            //echo 'Update: ' . $old_article['title'] . ' (' . $old_article_id . ")\n";
            set_article_attr($old_article_id, 'description', $description);
            set_article_attr($old_article_id, 'price', $price);

            $required = ($new_article['type'] == 'o');
            set_article_attr($old_article_id, 'article_group_id', $article_group_id);

            $new_article['id'] = $old_article_id;
        } else {
            echo 'New: ' . $new_article_name . "\n";
            $new_article['id'] = new_article($new_article_name, $description, $price, $store_id, $article_group_id, $parent_article_id);
        }

        $completed[] = $new_article['id'];

        if (array_key_exists('articles', $new_article)) {
            _import_menu_helper($store_id, $new_article, $old_articles, $completed);
        }
    }
}

function import_menu($tree) {
    $store_name = $tree['store'];

    $found = false;
    foreach (get_stores() as $store_id => $store) {
        if ($store['name'] == $store_name) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo 'Store not found.';
        return false;
    }

    $articles = get_articles($store_id);
    $completed = [];

    _import_menu_helper($store_id, $tree, $articles, $completed);

    foreach ($articles as $article_id => $article) {
        if (in_array($article_id, $completed)) {
            continue;
        }

        echo 'Remove: ' . $article['title']  . ' (' . $article_id . ")\n";
        remove_article($article_id);
    }

    return true;
}

function export_menu_helper($parent_article_id, $articles, $groups, $indent) {
    $result = '';

    foreach ($articles as $article_id => $article) {
        if ($article['parent_article_id'] != $parent_article_id) {
            continue;
        }

        $group = $groups[$article['article_group_id']];

        for ($i = 0; $i < $indent; $i++) {
            $result .= '  ';
        }

        if ($group['required']) {
            $result .= 'o';
        } else {
            $result .= 'x';
        }

        $result .= ' ' . $group['title'] . ': ' . $article['title'];

        if (bccomp($article['price'], '0') != 0) {
            $result .= ' @ ' . format_number($article['price'], false);
        }

        $result .= "\n";

        if ($article['description'] != '') {
            for ($i = 0; $i < $indent + 1; $i++) {
                $result .= '  ';
            }

            $result .= 'd ' . $article['description'] . "\n";
        }

        $result .= export_menu_helper($article_id, $articles, $groups, $indent + 1);

        if ($indent == 0) {
            $result .= "\n";
        }
    }

    return $result;
}

function export_menu($store_id) {
    $result = 's ' . get_stores()[$store_id]['name'] . "\n\n";
    $result .= export_menu_helper(null, get_articles($store_id), get_article_groups(), 0);
    return $result;
}
