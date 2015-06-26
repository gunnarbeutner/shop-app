# Shop

A small self-service shop system for lunch orders in the office.

Features:

 * Support for multiple merchants and stores
 * Order priorities
 * Automated direct debit (via bank-app)

## Requirements

 * PHP 5.6+

## Installation

1. Install the PHP GD extension:

    apt-get install php5-gd

2. Install Composer in the project directory:

    curl -sS https://getcomposer.org/installer | php

3. Install dependencies:

    php composer.phar update

4. Create a new config file called 'config.php'. You can use 'config.example.php' as a template.

5. Set up mod\_rewrite and mod\_alias rules:

    RewriteEngine on
    RewriteRule ^/?$ /app/login [R,L]
    RewriteRule /app/.* /app.php [L]

    Alias /vendor /home/shop/shop-app/vendor

6. Restart Apache:

    service apache2 restart
