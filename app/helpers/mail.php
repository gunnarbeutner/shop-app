<?php

/* 
 * Copyright (C) Error: on line 4, column 33 in Templates/Licenses/license-gpl20.txt
The string doesn't match the expected date/time format. The string to parse was: "09.07.2015". The expected format was: "MMM d, yyyy". Gunnar
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

function app_mail($to, $subject, $message) {
	$headers = [];
	$headers[] = "From: " . SHOP_BRAND . " <no-reply@" . SHOP_DOMAIN . ">";
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-type: text/plain; charset=utf-8";

	mail($to, $subject, $message, implode("\r\n", $headers));
}
