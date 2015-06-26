<?php

ini_set('include_path', ini_get('include_path') . ':' . __DIR__ . ':' . __DIR__  . '/app');

const SHOP_DB_DSN = 'mysql:dbname=shop;charset=utf8';
const SHOP_DB_USERNAME = 'shop';
const SHOP_DB_PASSWORD = 'xxxx';

const SHOP_BRAND = 'My Shop';
const SHOP_LOGO = 'my-logo.png';
const SHOP_DOMAIN = 'shop.example.org';

const SHOP_MAIL_FILTERS = [
	'*'
];

const SHOP_MAC_SECRET = 'xxxx';

const SHOP_BANK_URL = 'https://banking.example.org';
const SHOP_BANK_USER = 'net-lunch-shop';
const SHOP_BANK_PASSWORD = 'xxxx';
