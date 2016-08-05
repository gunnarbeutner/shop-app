<?php

ini_set('include_path', ini_get('include_path') . ':' . __DIR__ . ':' . __DIR__  . '/app');

const SHOP_DB_DSN = 'mysql:host=192.0.2.17;dbname=lunch_shop;charset=utf8';
const SHOP_DB_USERNAME = 'shop';
const SHOP_DB_PASSWORD = 'xxxx';

const SHOP_BRAND = 'My Shop';
const SHOP_LOGO = 'my-logo.png';
const SHOP_DOMAIN = 'lunch.example.org';

const SHOP_MAIL_FILTERS = [
	'*.*@example.org'
];

const SHOP_MAC_SECRET = 'xxxx';

const SHOP_BANK_URL = 'https://banking.example.org';
const SHOP_BANK_USER = 'net-lunch-shop';
const SHOP_BANK_PASSWORD = 'xxxx';

const BANK_MAC_SECRET = 'xxxx';

const SHOP_INSURANCE_USER = 'xxxx';
const SHOP_INSURANCE_LIMIT = 'xxx';
const SHOP_INSURANCE_PER_ORDER = 'xxx';
