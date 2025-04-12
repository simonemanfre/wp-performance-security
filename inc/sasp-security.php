<?php
function trp_sasp_whitelabel(){
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wp_generator');
	remove_action('wp_head', 'feed_links', 2);
	remove_action('wp_head', 'index_rel_link');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'feed_links_extra', 3);
	remove_action('wp_head', 'start_post_rel_link', 10, 0);
	remove_action('wp_head', 'parent_post_rel_link', 10, 0);
	remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
	remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
	remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
	remove_action('wp_head', 'feed_links', 2);
	remove_action('wp_head', 'feed_links_extra', 3);
	remove_action('wp_head', 'rest_output_link_wp_head' );
	remove_action('wp_head', 'wp_oembed_add_discovery_links' );
	remove_action('template_redirect', 'rest_output_link_header', 11, 0 );
	remove_action('wp_head', 'wp_resource_hints', 2 );

	add_filter('the_generator','trp_sasp_remove_wp_version_rss');

	function trp_sasp_remove_wp_version_rss() {
		return '';
	}

	//EMOJI
	function trp_sasp_disable_wp_emojicons() {
		// all actions related to emojis
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	}
	add_action( 'init', 'trp_sasp_disable_wp_emojicons' );
}


function trp_sasp_xmlrpc_disable(){
	// Disable use XML-RPC
	add_filter( 'xmlrpc_enabled', '__return_false' );

	// Disable X-Pingback to header
	add_filter( 'wp_headers', 'trp_sasp_disable_x_pingback' );
	
	function trp_sasp_disable_x_pingback( $headers ) {
	    unset( $headers['X-Pingback'] );

		return $headers;
	}
}

trp_sasp_whitelabel();
trp_sasp_xmlrpc_disable();


// HIDE WORDPRESS ERROR MESSAGES
function trp_sasp_hide_wordpress_errors() {
    return esc_html__('Incorrect username or password.', 'super-admin-security-performance');
}
add_filter('login_errors', 'trp_sasp_hide_wordpress_errors');


// TRAPSTUDIO CHECK SUPER ADMIN
function trp_sasp_is_super_admin() {
    return current_user_can('trp_super_admin') || current_user_can('trp_sasp_admin');
}

// DISABLE ACF VISUAL EDITOR
add_filter('acf/settings/show_admin', 'trp_sasp_is_super_admin');

// EDIT CAPABILITIES FOR ADMINISTRATOR
function trp_sasp_edit_role_caps() {
	$current_user = wp_get_current_user();

	if (!in_array('administrator', $current_user->roles)) {
		return;
	}

	if( trp_sasp_is_super_admin() ) {
	
		 // Restore capabilities
		$current_user->add_cap( 'upload_themes', true );
		$current_user->add_cap( 'install_themes', true );
		$current_user->add_cap( 'switch_themes', true );
		$current_user->add_cap( 'edit_themes', true );
		$current_user->add_cap( 'delete_themes', true );

		$current_user->add_cap( 'upload_plugins', true );
		$current_user->add_cap( 'install_plugins', true );
		$current_user->add_cap( 'activate_plugins', true );
		$current_user->add_cap( 'edit_plugins', true );
		$current_user->add_cap( 'delete_plugins', true );

		$current_user->add_cap( 'update_plugins', true );
		$current_user->add_cap( 'update_core', true );
		$current_user->add_cap( 'update_themes', true );

	} else {

		 // Disable theme and plugin editor
		if (get_option('trp_sasp_file_edit', 0)) {
            if (!defined('DISALLOW_FILE_EDIT')) {
                define('DISALLOW_FILE_EDIT', TRUE);
            }
		 }

		 // Remove dangerous capabilities for other admins
		if (get_option('trp_sasp_manage_themes', 0)) {
			$current_user->add_cap( 'upload_themes', false );
			$current_user->add_cap( 'install_themes', false );
			$current_user->add_cap( 'switch_themes', false );
			$current_user->add_cap( 'edit_themes', false );
			$current_user->add_cap( 'delete_themes', false );
		} else {
			$current_user->add_cap( 'upload_themes', true );
			$current_user->add_cap( 'install_themes', true );
			$current_user->add_cap( 'switch_themes', true );
			$current_user->add_cap( 'edit_themes', true );
			$current_user->add_cap( 'delete_themes', true );
		}

		if (get_option('trp_sasp_manage_plugins', 0)) {
			$current_user->add_cap( 'upload_plugins', false );
			$current_user->add_cap( 'install_plugins', false );
			$current_user->add_cap( 'activate_plugins', false );
			$current_user->add_cap( 'edit_plugins', false );
			$current_user->add_cap( 'delete_plugins', false );
		} else {
			$current_user->add_cap( 'upload_plugins', true );
			$current_user->add_cap( 'install_plugins', true );
			$current_user->add_cap( 'activate_plugins', true );
			$current_user->add_cap( 'edit_plugins', true );
			$current_user->add_cap( 'delete_plugins', true );
		}

		if (get_option('trp_sasp_manage_updates', 0)) {
			$current_user->add_cap( 'update_plugins', false );
			$current_user->add_cap( 'update_core', false );
			$current_user->add_cap( 'update_themes', false );
		} else {
			$current_user->add_cap( 'update_plugins', true );
			$current_user->add_cap( 'update_core', true );
			$current_user->add_cap( 'update_themes', true );
		}

	}
}
add_action( 'init', 'trp_sasp_edit_role_caps', 11 );
