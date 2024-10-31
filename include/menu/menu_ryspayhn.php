<?php
/**
 * Copyright (c) 2020.
 * File: menu_ryspayhn.php
 * Last Modified: 13/1/20 9:55 a. m.
 * Jesus Nuñez
 */
require_once plugin_dir_path(__FILE__) . 'admin/admin_page_ryspayhn.php';
add_action('admin_enqueue_scripts', 'ryspayhn_styles');
add_action('admin_menu', 'ryspayhn_menus');
function ryspayhn_menus()
{
    add_menu_page(
        'Ryspayhn',
        'Ryspayhn',
        'manage_options',
        'ryspayhn',
        'ryspayhn_admin'
    );
}


