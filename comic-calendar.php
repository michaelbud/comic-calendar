<?php
/**
 * Plugin Name: Comic Calendar
 * Description: A flexible comic management system. Create comics, display them on a calendar, and ensure perfect SEO for every strip.
 * Version: 2.0.0
 * Author: Michael Bud
 * Text Domain: comic-calendar
 * License: GPL3
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define( 'CC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CC_PLUGIN_FILE', __FILE__ );

// Include Core Files
require_once CC_PLUGIN_PATH . 'includes/cc-helpers.php';
require_once CC_PLUGIN_PATH . 'includes/cc-cpt.php';
require_once CC_PLUGIN_PATH . 'includes/cc-rewrites.php';
require_once CC_PLUGIN_PATH . 'includes/cc-assets.php';
require_once CC_PLUGIN_PATH . 'includes/cc-shortcodes.php';
require_once CC_PLUGIN_PATH . 'includes/cc-seo.php';
require_once CC_PLUGIN_PATH . 'includes/cc-widget-random.php';

// Include Admin Settings (Only if in admin)
if ( is_admin() ) {
    require_once CC_PLUGIN_PATH . 'admin/cc-settings.php';
}

// Activation: Register CPT/Rewrites and Flush
register_activation_hook( CC_PLUGIN_FILE, function() {
    cc_register_cpt();
    cc_register_rewrites();
    flush_rewrite_rules();
});

// Deactivation: Flush
register_deactivation_hook( CC_PLUGIN_FILE, function() {
    flush_rewrite_rules();
});