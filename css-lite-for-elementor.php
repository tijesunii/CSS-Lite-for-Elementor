<?php
/**
 * Plugin Name: CSS Lite for Elementor
 * Description: Adds lightweight custom CSS controls for Elementor elements, pages, and site-wide design helpers.
 * Version: 1.0.7
 * Author: Eben
 * Author URI: https://allmytools.xyz
 * Text Domain: css-lite-for-elementor
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package CssLiteForElementor
 */

// Stop direct file access, because WordPress should be the only entry point.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// These constants keep paths and version references consistent across the plugin.
define( 'ECCL_VERSION', '1.0.7' );
define( 'ECCL_FILE', __FILE__ );
define( 'ECCL_PATH', plugin_dir_path( __FILE__ ) );
define( 'ECCL_URL', plugin_dir_url( __FILE__ ) );
define( 'ECCL_BASENAME', plugin_basename( __FILE__ ) );

require_once ECCL_PATH . 'includes/Security/Css_Sanitizer.php';
require_once ECCL_PATH . 'includes/Admin/Settings_Page.php';
require_once ECCL_PATH . 'includes/Elementor/Controls.php';
require_once ECCL_PATH . 'includes/Elementor/Css_Renderer.php';
require_once ECCL_PATH . 'includes/Plugin.php';
require_once ECCL_PATH . 'includes/Telemetry/Telemetry.php';
require_once ECCL_PATH . 'includes/Updates/UpdateChecker.php';

// Boot the plugin after all classes are loaded.
\ECCL\Plugin::instance()->boot();
