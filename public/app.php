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

require_once(__DIR__ . '/../config.php');
require_once('vendor/autoload.php');
require_once('helpers/session.php');

define('DOMPDF_ENABLE_AUTOLOAD', false);
define('DOMPDF_ENABLE_REMOTE', true);

use Dompdf\Dompdf;

ob_start();

$uri = $_SERVER['REQUEST_URI'];
$qtokens = explode('?', $uri, 2);
$tokens = explode('/', $qtokens[0], 3);

if (count($tokens) < 3 || $tokens[0] != '' || $tokens[1] != 'app') {
	http_response_code(400);
	echo 'Invalid request.';
	die();
}

$atokens = explode('.', $tokens[2], 2);

$action = $atokens[0];

if (count($atokens) > 1)
	$format = $atokens[1];
else
	$format = 'html';

if (!preg_match('/^[a-z-]+$/', $action) || !file_exists('../app/controllers/' . $action . '.php')) {
	http_response_code(400);
	echo 'Invalid action.';
	die();
}

if (is_logged_in()) {
	$utoken = get_user_token();
	$token = get_user_attr(get_user_email(), 'login_token');
	
	if ($utoken != $token) {
		session_destroy();
		header('Location: /app/login');
		die();
	}
}

require_once('controllers/' . $action . '.php');

$controller_name = ucfirst(str_replace('-', '', $action)) . 'Controller';

$controller = new $controller_name();

$method_name = strtolower($_SERVER['REQUEST_METHOD']);

if (!in_array($method_name, array('get', 'post'))) {
	http_response_code(400);
	echo 'Invalid method.';
	die();
}

$viewInfo = $controller->$method_name();
$view = $viewInfo[0];
$params = $viewInfo[1];

if (!preg_match('/^[a-z-]+$/', $view) || !file_exists('../app/views/' . $view . '.php')) {
	http_response_code(400);
	echo 'Invalid view name.';
	die();
}

if ($format == 'pdf') {
	header("Content-type: application/pdf");

	ob_start();
}

require_once('views/layout.php');

if ($format == 'pdf') {
	$html = ob_get_clean();
	ob_end_flush();

	$dompdf = new DOMPDF();
	$dompdf->getOptions()->setIsRemoteEnabled(true);
	$dompdf->load_html($html);
	$dompdf->render();
	echo $dompdf->output();
}

?>
