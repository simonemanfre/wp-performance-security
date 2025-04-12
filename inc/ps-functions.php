<?php 
defined( 'PERFORMANCE_SECURITY_PLUGIN_DIR' ) || exit; // Exit if accessed directly

// Function to enable debug mode
function trp_ps_activate_debug_mode($scripts) {
    if (get_option('trp_ps_debug_mode', 0)) {
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
        if (!defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', true);
        }
    }
}
add_action('wp_default_scripts', 'trp_ps_activate_debug_mode');

// Function to remove jQuery Migrate
function trp_ps_remove_jquery_migrate($scripts) {
    if (get_option('trp_ps_jquery_migrate', 0)) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, array('jquery-migrate'));
            }
        }
    }
}
add_action('wp_default_scripts', 'trp_ps_remove_jquery_migrate');

// Function to move jQuery to the footer
function trp_ps_move_jquery_to_footer($wp_scripts) {
    if (get_option('trp_ps_jquery_in_footer', 0)) {
        if (!is_admin()) {
            $wp_scripts->add_data('jquery', 'group', 1);
            $wp_scripts->add_data('jquery-core', 'group', 1);
            $wp_scripts->add_data('jquery-migrate', 'group', 1);
        }
    }
}
add_action('wp_default_scripts', 'trp_ps_move_jquery_to_footer');

// Function to add speculation rules
function trp_ps_add_speculation_rules() {
    if (get_option('trp_ps_speculation_rules', 0)) {
        // GLOBAL PRERENDER ON HOVER
        echo '
        <script type="speculationrules">
        {
            "prerender": [{
                "where": {
                    "and": [
                        { "href_matches": "/*" },
                        { "not": {"href_matches": "/wp-admin"}},
                        { "not": {"selector_matches": ".no-prerender"}},
                        { "not": {"selector_matches": "[rel~=nofollow]"}}
                    ]    
                },
                "eagerness": "moderate"
            }]
        }
        </script>
        ';

        // WOOCOMMERCE PRERENDER
        if( class_exists('woocommerce') ) {
            
            if(is_product()) {
                // Prerender the cart if on a product page
                $next_url = wc_get_cart_url();

                echo '
                <script type="speculationrules">
                {
                    "prerender": [
                        {
                        "urls": ["'.$next_url.'"]
                        }
                    ]
                    }
                </script>
                ';
            } 

            if(is_cart()) {
                // Prerender the checkout if in the cart
                $next_url = wc_get_checkout_url();

                echo '
                <script type="speculationrules">
                {
                    "prerender": [
                        {
                        "urls": ["'.$next_url.'"]
                        }
                    ]
                    }
                </script>
                ';
            } 

            if(is_checkout()) {
                // Prerender the cart if in the checkout
                $next_url = wc_get_cart_url();
                
                echo '
                <script type="speculationrules">
                {
                    "prerender": [
                        {
                        "urls": ["'.$next_url.'"]
                        }
                    ]
                    }
                </script>
                ';
            }
        }
    }
}       
add_action( 'wp_head', 'trp_ps_add_speculation_rules' ); 

// Function to add HTTPS redirect
if (get_option('trp_ps_https_redirect', 0)) {
    function trp_ps_add_https_redirect( $rules ) {
        // Add new HTTPS rules
        $new_rules = "# BEGIN HTTPS\n";
        $new_rules .= "<IfModule mod_rewrite.c>\n";
        $new_rules .= "RewriteEngine On\n";
        $new_rules .= "RewriteCond %{HTTPS} off\n";
        $new_rules .= "RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]\n";
        $new_rules .= "</IfModule>\n";
        $new_rules .= "# END HTTPS\n\n";
        $new_rules .= $rules;

        return $new_rules;
    }
    add_filter('mod_rewrite_rules', 'trp_ps_add_https_redirect');
}

if (get_option('trp_ps_deflate_cache', 0)) {
    function trp_ps_add_deflate_cache( $rules ) {
        $rules .= '# DEFLATE compression
<IfModule mod_deflate.c>
# Compress HTML, CSS, JavaScript, Text, XML and fonts
AddOutputFilterByType DEFLATE text/html
AddOutputFilterByType DEFLATE text/css
AddOutputFilterByType DEFLATE text/javascript
AddOutputFilterByType DEFLATE text/plain
AddOutputFilterByType DEFLATE text/xml
AddOutputFilterByType DEFLATE application/javascript
AddOutputFilterByType DEFLATE application/x-javascript
AddOutputFilterByType DEFLATE application/xml
AddOutputFilterByType DEFLATE application/xhtml+xml
AddOutputFilterByType DEFLATE application/rss+xml
AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
AddOutputFilterByType DEFLATE application/x-font
AddOutputFilterByType DEFLATE application/x-font-opentype
AddOutputFilterByType DEFLATE application/x-font-otf
AddOutputFilterByType DEFLATE application/x-font-truetype
AddOutputFilterByType DEFLATE application/x-font-ttf
AddOutputFilterByType DEFLATE font/opentype
AddOutputFilterByType DEFLATE font/otf
AddOutputFilterByType DEFLATE font/ttf
AddOutputFilterByType DEFLATE image/svg+xml
AddOutputFilterByType DEFLATE image/x-icon
AddOutputFilterByType DEFLATE image/jpg
AddOutputFilterByType DEFLATE image/jpeg
AddOutputFilterByType DEFLATE image/gif
AddOutputFilterByType DEFLATE image/png
# Remove browser bugs
BrowserMatch ^Mozilla/4 gzip-only-text/html
BrowserMatch ^Mozilla/4\.0[678] no-gzip
BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
Header append Vary User-Agent
</IfModule>
# END DEFLATE

## EXPIRES CACHING ##
<IfModule mod_expires.c>
ExpiresActive On
ExpiresByType image/png "access plus 1 year"
ExpiresByType image/jpg "access plus 1 year"
ExpiresByType image/jpeg "access plus 1 year"
ExpiresByType image/gif "access plus 1 year"
ExpiresByType image/svg+xml "access plus 1 month"
ExpiresByType image/x-icon "access 1 year"
ExpiresByType audio/ogg "access plus 1 year"
ExpiresByType video/ogg "access plus 1 year"
ExpiresByType video/mp4 "access plus 1 year"
ExpiresByType video/webm "access plus 1 year"
ExpiresByType application/pdf "access 1 month"
ExpiresByType application/javascript "access 1 month"
ExpiresByType application/x-javascript "access 1 month"
ExpiresByType text/javascript "access 1 month"
ExpiresByType text/x-javascript "access 1 month"
ExpiresByType application/x-shockwave-flash "access 1 month"
</IfModule>
## EXPIRES CACHING ##
    ';

        return $rules;
    }
    add_filter('mod_rewrite_rules', 'trp_ps_add_deflate_cache');
}

function trp_ps_htaccess_security( $rules ) {	
	$rules .= '# SECURITY: XML RPC BLOCKING
<Files xmlrpc.php>
Order Deny,Allow
Deny from all
</Files>
# SECURITY: XML RPC BLOCKING

# SECURITY: SENSITIVE FILES
<FilesMatch "(^\.|wp-config(-sample)*\.php)">
	order deny,allow
	deny from all
</FilesMatch>
# SECURITY: SENSITIVE FILES

# SECURITY: PHP ERRORS
<IfModule mod_php5.c>
php_flag display_errors off
</IfModule>
<IfModule mod_php7.c>
	php_flag display_errors off
</IfModule>
# SECURITY: PHP ERRORS

# SECURITY: PAGE LISTING
Options -Indexes
# SECURITY: PAGE LISTING
';

	return $rules;
}
add_filter('mod_rewrite_rules', 'trp_ps_htaccess_security');
