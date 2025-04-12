<?php
/*
Plugin Name: Performance & Security
Description: Improve performance and security of your WordPress website
Author: Simone Manfredini & Trapstudio
Author URI: https://simonemanfre.it/
License: GPL2
Domain Path: /languages/
Text Domain: ps
Version: 0.0.1
*/
/*  This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

define( 'PERFORMANCE_SECURITY_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
define( 'PERFORMANCE_SECURITY_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );

if (is_admin()) {
    require PERFORMANCE_SECURITY_PLUGIN_DIR . '/inc/ps-admin.php';
}

require_once PERFORMANCE_SECURITY_PLUGIN_DIR . '/inc/ps-functions.php';
require_once PERFORMANCE_SECURITY_PLUGIN_DIR . '/inc/ps-security.php';

// Plugin activation
function trp_ps_plugin_activate() {
    // Assign the super_admin capability to the user activating the plugin
    $current_user = wp_get_current_user();
    if ($current_user) {
        $current_user->add_cap('trp_super_admin', true);
    }
}
register_activation_hook(__FILE__, 'trp_ps_plugin_activate');

// Plugin deactivation
function trp_ps_plugin_deactivate() {
    // Remove the super_admin capability from all users
    $super_admin = get_users(
        array(
            'role__in' => 'administrator',
            'capability' => 'trp_super_admin'
        )
    );

    foreach ($super_admin as $user) {
        if ($user) {
            $user->add_cap('trp_super_admin', false);
        }
    }
}
register_deactivation_hook(__FILE__, 'trp_ps_plugin_deactivate');