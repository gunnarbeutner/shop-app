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

require_once('helpers/session.php');
require_once('helpers/user.php');
require_once('helpers/order.php');

$username = get_user_name();
$email = get_user_email();
$gravatar_url = "https://www.gravatar.com/avatar/" . md5(strtolower($email)) . "?s=35&amp;d=mm&amp;r=g";
$ext_info = get_user_ext_info($email);
$bank_token = hash_hmac('sha256', ((int)time() - (int)time() % 86400) . $email, BANK_MAC_SECRET);

?>

<div id="header" role="banner">
  <div class="aui-header aui-dropdown2-trigger-group" role="navigation">
    <div class="aui-header-inner">
      <div class="aui-header-primary">
        <h1 id="logo" class="aui-header-logo aui-header-logo-aui">
          <img src="<?php echo $app_url; ?>/images/<?php echo SHOP_LOGO; ?>" alt="<?php echo SHOP_BRAND; ?>" />
        </h1>
        <?php if ($format == 'html') { ?>
        <ul class="aui-nav">
          <li>
            <a href="/app/order">
              <i class="fa fa-shopping-cart menu-icon"></i>
              <span class="menu-text">Bestellung</span>
            </a>
          </li>
          <?php if (get_user_attr(get_user_email(), 'merchant')) { ?>
          <li>
            <a href="/app/stores">
              <i class="fa fa-cubes menu-icon"></i>
              <span class="menu-text">L&auml;den</span>
            </a>
          </li>
		  <?php } ?>
          <li>
            <a href="/app/merchant-orders">
              <i class="fa fa-list menu-icon"></i>
              <span class="menu-text">Auftragsliste</span>
            </a>
          </li>
          <?php if (get_user_attr(get_user_email(), 'merchant')) { ?>
          <li>
            <a href="/app/reports">
              <i class="fa fa-bar-chart menu-icon"></i>
              <span class="menu-text">Statistiken</span>
            </a>
          </li>
		  <?php } ?>
        </ul>
      </div>
      <div class="aui-navgroup-secondary">
        <ul class="aui-nav __skate" resolved="">
          <li>
            <a href="<?php echo SHOP_BANK_URL; ?>/app/login?account=<?php echo urlencode($email); ?>&token=<?php echo $bank_token; ?>" target="_blank">
              <i class="fa fa-bank menu-icon"></i>
              <span class="menu-text">Bank</span>
            </a>
          </li>
          <li>
            <a href="#" aria-haspopup="true" class="aui-dropdown2-trigger aui-alignment-target aui-alignment-element-attached-top aui-alignment-element-attached-right aui-alignment-target-attached-bottom aui-alignment-target-attached-right user-menu" data-container="#aui-hnav-example" aria-controls="dropdown2-nav2" aria-expanded="false" resolved="">
              <img src="<?php echo($gravatar_url); ?>" class="menu-icon" alt="<?php echo htmlentities($username); ?>"/>
              <span class="menu-text">
                <?php echo htmlentities($username); ?>
              </span>
              <span class="menu-text">(<?php echo format_number($ext_info['balance']); ?>&euro;)</span>
            </a>

            <!-- .aui-dropdown2 -->
            <div id="dropdown2-nav2" class="aui-dropdown2 aui-style-default aui-layer aui-alignment-element aui-alignment-side-bottom aui-alignment-snap-right aui-alignment-element-attached-top aui-alignment-element-attached-right aui-alignment-target-attached-bottom aui-alignment-target-attached-right" aria-hidden="true" resolved="" data-aui-alignment="bottom auto" data-aui-alignment-static="true" style="z-index: 3000; top: 0px; left: 0px; position: absolute; transform: translateX(1229px) translateY(783px) translateZ(0px);">
              <ul class="aui-list-truncate">
                <li>
                  <a href="/app/profile" tabindex="-1">
                    <i class="fa fa-user"></i> &nbsp;
                    Profil
                  </a>
               </li>
               <li>
                  <a href="/app/logout" tabindex="-1">
                    <i class="fa fa-sign-out"></i> &nbsp;
                    Abmelden
                  </a>
                </li>
              </ul>
            </div>
          </li>
        </ul>
      </div>
      <?php } ?>
    </div>
  </div>
</div>
