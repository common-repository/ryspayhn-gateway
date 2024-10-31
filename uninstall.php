<?php
/**
 * Copyright (c) 2020.
 * File: uninstall.php
 * Last Modified: 11/1/20 21:49
 * Jesus
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}
$optionDate = "ryspayhnDate";
delete_option($optionDate);