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

function find_articles($store_id, $q) {
	global $shop_db;

	$store_quoted = $shop_db->quote($store_id);
	$q_quoted = $shop_db->quote($q);

	$query = <<<QUERY
SELECT `id`, `title`
FROM `articles`
WHERE `store_id` = ${store_quoted} AND (`title` LIKE CONCAT('%', ${q_quoted}, '%') OR `description` LIKE CONCAT('%', ${q_quoted}, '%'))
QUERY;

	$articles = [];
	foreach ($shop_db->query($query) as $row) {
		$articles[] = [
			'id' => $row['id'],
			'title' => $row['title']
		];
	}

	return $articles;
}

function get_primary_articles($store_id) {
	global $shop_db;

	$store_quoted = $shop_db->quote($store_id);

	$query = <<<QUERY
SELECT a.`id`, a.`title`, a.`description`, ag.`title` AS group_title
FROM `articles` a
LEFT JOIN `article_groups` ag ON a.`article_group_id`=ag.`id`
WHERE a.`store_id` = ${store_quoted} AND a.`parent_article_id` IS NULL
ORDER BY `title` ASC
QUERY;

	$articles = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $article) {
		$articles[$article['id']] = $article;
	}

	return $articles;
}

function get_article_attr($article_id, $attr) {
	global $shop_db;

	$article_quoted = $shop_db->quote($article_id);

	$query = <<<QUERY
SELECT ${attr} AS attr
FROM `articles`
WHERE `id` = ${article_quoted}
QUERY;
	$row = $shop_db->query($query)->fetch(PDO::FETCH_ASSOC);
	if ($row === false) {
		return false;
	} else {
		return $row['attr'];
	}
}

function get_article_info($article_id) {
	global $shop_db;

	$article_quoted = $shop_db->quote($article_id);

	$query = <<<QUERY
SELECT `id`, `title`, `description`, `price`, `article_group_id`
FROM `articles`
WHERE `id` = ${article_quoted} OR `parent_article_id` = ${article_quoted}
ORDER BY `title` ASC, `price` ASC
QUERY;

	$articles = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $article) {
		$articles[$article['id']] = $article;
	}

	return $articles;
}

function get_article_groups() {
	global $shop_db;

	$query = <<<QUERY
SELECT `id`, `title`, `description`, `required`, `yorder`
FROM `article_groups`
ORDER BY `yorder` ASC
QUERY;

	$groups = [];
	foreach ($shop_db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $group) {
		$groups[$group['id']] = $group;
	}

	return $groups;
}
