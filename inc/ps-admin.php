<?php 
defined( 'PERFORMANCE_SECURITY_PLUGIN_DIR' ) || exit; // Exit if accessed directly

// PLUGIN OPTIONS PAGE
function trp_ps_plugin_option_page() {
    // Check permissions
    if (!trp_ps_is_super_admin()) {
        return;
    }

    add_options_page(
        'Performance & Security',
        'Performance & Security',
        'manage_options',
        'ps',
        'trp_ps_plugin_option_page_html'
    );
}
add_action('admin_menu', 'trp_ps_plugin_option_page');

// Register settings
function trp_ps_register_settings() {
    register_setting('trp_ps_options', 'trp_ps_jquery_migrate');
    register_setting('trp_ps_options', 'trp_ps_jquery_in_footer');
    register_setting('trp_ps_options', 'trp_ps_speculation_rules');
    register_setting('trp_ps_options', 'trp_ps_https_redirect');
    register_setting('trp_ps_options', 'trp_ps_deflate_cache');
    register_setting('trp_ps_options', 'trp_ps_file_edit');
    register_setting('trp_ps_options', 'trp_ps_manage_themes');
    register_setting('trp_ps_options', 'trp_ps_manage_plugins');
    register_setting('trp_ps_options', 'trp_ps_manage_updates');
    register_setting('trp_ps_options', 'trp_ps_super_admin_users', array(
        'type' => 'array',
        'sanitize_callback' => 'trp_ps_sanitize_super_admin_users',
    ));
}
add_action('admin_init', 'trp_ps_register_settings');

// Sanitize the super admin users array
function trp_ps_sanitize_super_admin_users($users) {
    if (!is_array($users)) {
        return array();
    }
    return array_map('absint', $users);
}

// Options page content
function trp_ps_plugin_option_page_html() {
    // Check permissions
    if (!current_user_can('trp_super_admin') && !current_user_can('trp_ps_admin')) {
        wp_die(__('You do not have permission to access this page.', 'wp-security-performance'));
    }

    // Save settings if the form is submitted
    if (isset($_POST['submit'])) {
        check_admin_referer('trp_ps_options_save', 'trp_ps_nonce');

        // Sanitize and save general settings
        $selected_users = isset($_POST['trp_ps_super_admin_users']) ? array_map('absint', (array) $_POST['trp_ps_super_admin_users']) : array();
        update_option('trp_ps_super_admin_users', $selected_users);

        // Sanitize and save performance settings
        update_option('trp_ps_jquery_migrate', isset($_POST['trp_ps_jquery_migrate']) ? 1 : 0);
        update_option('trp_ps_jquery_in_footer', isset($_POST['trp_ps_jquery_in_footer']) ? 1 : 0);

        // Sanitize and save security settings
        update_option('trp_ps_speculation_rules', isset($_POST['trp_ps_speculation_rules']) ? 1 : 0);
        update_option('trp_ps_https_redirect', isset($_POST['trp_ps_https_redirect']) ? 1 : 0);
        update_option('trp_ps_deflate_cache', isset($_POST['trp_ps_deflate_cache']) ? 1 : 0);
        update_option('trp_ps_file_edit', isset($_POST['trp_ps_file_edit']) ? 1 : 0);
        update_option('trp_ps_manage_themes', isset($_POST['trp_ps_manage_themes']) ? 1 : 0);
        update_option('trp_ps_manage_plugins', isset($_POST['trp_ps_manage_plugins']) ? 1 : 0);
        update_option('trp_ps_manage_updates', isset($_POST['trp_ps_manage_updates']) ? 1 : 0);

        // Manage super admin users
        $current_super_admins = get_option('trp_ps_super_admin_users', array());

        // Remove capability from users no longer selected
        foreach ($current_super_admins as $user_id) {
            if (!in_array($user_id, $selected_users)) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $user->add_cap('trp_ps_admin', false);
                }
            }
        }

        // Add capability to newly selected users
        foreach ($selected_users as $user_id) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $user->add_cap('trp_ps_admin', true);
            }
        }

        update_option('trp_ps_super_admin_users', $selected_users);

        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }

    // Retrieve current values
    $super_admin_users = get_option('trp_ps_super_admin_users', array());
    $remove_jquery_migrate = get_option('trp_ps_jquery_migrate', 0);
    $jquery_in_footer = get_option('trp_ps_jquery_in_footer', 0);
    $speculation_rules = get_option('trp_ps_speculation_rules', 0);
    $htaccess_redirect = get_option('trp_ps_https_redirect', 0);
    $deflate_cache = get_option('trp_ps_deflate_cache', 0);
    $file_edit = get_option('trp_ps_file_edit', 0);
    $manage_themes = get_option('trp_ps_manage_themes', 0);
    $manage_plugins = get_option('trp_ps_manage_plugins', 0);
    $manage_updates = get_option('trp_ps_manage_updates', 0);

    // Current user ID
    $current_user_id = get_current_user_id();

    // Retrieve all site administrators excluding the current user and "super admin"
    $administrators = get_users(
        array(
            'role__in' => 'administrator',
            'capability__not_in' => array('trp_super_admin'),
        )
    );

    $super_admin = get_users(
        array(
            'role__in' => 'administrator',
            'capability' => 'trp_super_admin'
        )
    );

    // Tabs
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=ps&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">General</a>
            <a href="?page=ps&tab=security" class="nav-tab <?php echo $active_tab === 'security' ? 'nav-tab-active' : ''; ?>">Security</a>
            <a href="?page=ps&tab=performance" class="nav-tab <?php echo $active_tab === 'performance' ? 'nav-tab-active' : ''; ?>">Performance</a>
        </h2>
        <form method="post" action="">
            <?php settings_fields('trp_ps_options'); ?>

            <?php wp_nonce_field('trp_ps_options_save', 'trp_ps_nonce'); ?>

            <?php if ($active_tab === 'general') : ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Manage Super Admin</th>
                        <td>
                            <p class="description">Select other users you want to make Super Admin. <br />You can't disable the main Admin.</p>

                            <?php foreach ($super_admin as $user) : ?>
                                <label style="display: block; margin-top: 16px;">
                                    <input 
                                        type="checkbox" 
                                        name="" 
                                        value="<?php echo esc_attr($user->ID); ?>" 
                                        checked
                                        disabled
                                    >
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </label>
                            <?php endforeach; ?>

                            <?php foreach ($administrators as $user) : ?>
                                <label style="display: block; margin-top: 8px;">
                                    <input 
                                        type="checkbox" 
                                        name="trp_ps_super_admin_users[]" 
                                        value="<?php echo esc_attr($user->ID); ?>" 
                                        <?php checked(in_array($user->ID, $super_admin_users), true); ?> 
                                        <?php echo ($user->ID == $current_user_id) ? "disabled" : ""; ?>
                                    >
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>

            <?php elseif ($active_tab === 'security') : ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Speculation Rules</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_speculation_rules" value="1" <?php checked(1, $speculation_rules); ?>>
                                Enable speculation rules with prerender on link :hover
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect HTTPS</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_https_redirect" value="1" <?php checked(1, $htaccess_redirect); ?>>
                                Enable redirect from HTTP:// to HTTPS://
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Deflate & Cache</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_deflate_cache" value="1" <?php checked(1, $deflate_cache); ?>>
                                Enable Deflate & Cache on Apache Server
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Disable File Edit</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_file_edit" value="1" <?php checked(1, $file_edit); ?>>
                                Disable File Edit for non Super Admin users
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Disable Theme Management</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_manage_themes" value="1" <?php checked(1, $manage_themes); ?>>
                                Disable Theme Management for non Super Admin users
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Disable Plugin Management</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_manage_plugins" value="1" <?php checked(1, $manage_plugins); ?>>
                                Disable Plugin Management for non Super Admin users
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Disable Update Management</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_manage_updates" value="1" <?php checked(1, $manage_updates); ?>>
                                Disable Update Management for non Super Admin users
                            </label>
                        </td>
                    </tr>
                </table>

            <?php elseif ($active_tab === 'performance') : ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">jQuery Migrate</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_jquery_migrate" value="1" <?php checked(1, $remove_jquery_migrate); ?>>
                                Remove jQuery Migrate from website frontend
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">jQuery in Footer</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trp_ps_jquery_in_footer" value="1" <?php checked(1, $jquery_in_footer); ?>>
                                Load jQuery in footer instead of header
                            </label>
                        </td>
                    </tr>
                </table>
            
            <?php endif; ?>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
