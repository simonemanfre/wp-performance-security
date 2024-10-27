<?php
/*
Plugin Name: Performance & Security
Description: Blueprint for develop new WordPress plugin
Author: Simone manfredini
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

// PAGINA OPZIONI PLUGIN
function trp_ps_plugin_option_page() {
    add_options_page(
        'Performance & Security',
        'Performance & Security',
        'manage_options',
        'ps',
        'trp_ps_plugin_option_page_html'
    );
}
add_action('admin_menu', 'trp_ps_plugin_option_page');

// Registra le impostazioni
function trp_ps_register_settings() {
    register_setting('trp_ps_options', 'trp_remove_jquery_migrate');
}
add_action('admin_init', 'trp_ps_register_settings');

// Contenuto pagina opzioni
function trp_ps_plugin_option_page_html() {
    // Verifica i permessi
    if (!current_user_can('manage_options')) {
        return;
    }

    // Salva le impostazioni se il form è stato inviato
    if (isset($_POST['submit'])) {
        update_option('trp_remove_jquery_migrate', isset($_POST['trp_remove_jquery_migrate']) ? 1 : 0);
    }

    // Recupera il valore corrente
    $remove_jquery_migrate = get_option('trp_remove_jquery_migrate', 0);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="">
            <?php settings_fields('trp_ps_options'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">jQuery Migrate</th>
                    <td>
                        <label>
                            <input type="checkbox" name="trp_remove_jquery_migrate" value="1" <?php checked(1, $remove_jquery_migrate); ?>>
                            Rimuovi jQuery Migrate dal frontend
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Funzione per rimuovere jQuery Migrate
function trp_remove_jquery_migrate($scripts) {
    if (get_option('trp_remove_jquery_migrate', 0)) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, array('jquery-migrate'));
            }
        }
    }
}
add_action('wp_default_scripts', 'trp_remove_jquery_migrate');