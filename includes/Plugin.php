<?php
/**
 * Main plugin coordinator.
 *
 * @package CssLiteForElementor
 */

namespace ECCL;

use ECCL\Admin\Settings_Page;
use ECCL\Elementor\Controls;
use ECCL\Elementor\Css_Renderer;
use ECCL\Security\Css_Sanitizer;
use ECCL\Telemetry\Telemetry;
use ECCL\Updates\UpdateChecker;

// Stop direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires WordPress, Elementor, admin settings, and frontend rendering together.
 */
final class Plugin {
	/**
	 * Holds the single plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Keeps plugin settings available to every component.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Returns the shared plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the first layer of WordPress hooks.
	 *
	 * @return void
	 */
	public function boot() {
		$this->settings = Settings_Page::get_settings();

		add_action( 'admin_init', array( $this, 'refresh_settings' ) );
		add_action( 'admin_notices', array( $this, 'show_missing_elementor_notice' ) );
		add_action( 'elementor/loaded', array( $this, 'init_elementor_features' ) );

		( new Settings_Page() )->register_hooks();

		// Self-hosted update checker — fetches release manifests from All My Tools.
		( new UpdateChecker() )->register_hooks();

		// Opt-in telemetry — anonymous active-install pings to All My Tools.
		( new Telemetry() )->register_hooks();

		// Schedule the daily ping if the user opted in.
		add_action( 'init', function () {
			( new Telemetry() )->maybe_schedule();
		} );

		// Fire an immediate ping when telemetry is turned on via the settings page.
		add_action( 'update_option_' . Settings_Page::OPTION_NAME, function ( $old_value, $new_value ) {
			$was_opted_in = ! empty( $old_value[ Telemetry::OPT_IN_KEY ] );
			$now_opted_in = ! empty( $new_value[ Telemetry::OPT_IN_KEY ] );

			if ( ! $was_opted_in && $now_opted_in ) {
				( new Telemetry() )->send_ping();
			}
		}, 10, 2 );
	}

	/**
	 * Refreshes settings during admin requests so recently saved options are used.
	 *
	 * @return void
	 */
	public function refresh_settings() {
		$this->settings = Settings_Page::get_settings();
	}

	/**
	 * Initializes features that require Elementor to be loaded.
	 *
	 * @return void
	 */
	public function init_elementor_features() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$sanitizer = new Css_Sanitizer();

		( new Controls( $this->settings ) )->register_hooks();
		( new Css_Renderer( $sanitizer, $this->settings ) )->register_hooks();
	}

	/**
	 * Shows a clear admin notice when Elementor is not active.
	 *
	 * @return void
	 */
	public function show_missing_elementor_notice() {
		if ( did_action( 'elementor/loaded' ) ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'CSS Lite for Elementor needs Elementor to be installed and active before it can add custom CSS controls.', 'css-lite-for-elementor' )
		);
	}

	/**
	 * Checks whether the plugin is enabled in settings.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		return ! empty( $this->settings['enabled'] );
	}
}
