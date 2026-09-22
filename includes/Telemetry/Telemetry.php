<?php
/**
 * Opt-in anonymous usage telemetry.
 *
 * When the user enables telemetry, this class sends an anonymous ping
 * to the All My Tools server so active-install counts stay accurate.
 *
 * No personal data, site URL, or identifying information is ever sent.
 * Only the plugin version and a one-way hashed site identifier.
 *
 * @package CssLiteForElementor
 */

namespace ECCL\Telemetry;

use ECCL\Admin\Settings_Page;

// Stop direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the scheduled ping to the telemetry endpoint.
 */
final class Telemetry {

	/**
	 * WordPress option that stores whether the user opted in.
	 *
	 * Stored inside the same eccl_settings array so it stays with the
	 * rest of the plugin config.
	 */
	const OPT_IN_KEY = 'telemetry_opt_in';

	/**
	 * Cron hook name used to schedule the daily ping.
	 */
	const CRON_HOOK = 'ecll_telemetry_ping';

	/**
	 * API endpoint on the All My Tools server.
	 *
	 * @var string
	 */
	private string $ping_url;

	/**
	 * The plugin slug on the server.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * The installed plugin version (from the constant defined in the main file).
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Sets up the ping endpoint and plugin metadata.
	 */
	public function __construct() {
		$this->ping_url = 'https://allmytools.xyz/api/plugins/css-lite-for-elementor/ping';
		$this->slug     = 'css-lite-for-elementor';
		$this->version  = \ECCL_VERSION;
	}

	/**
	 * Registers WordPress cron and admin-notice hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Schedule the daily ping when telemetry is enabled.
		add_action( self::CRON_HOOK, array( $this, 'send_ping' ) );

		// Show a one-time admin notice asking users to opt in (only if they haven't decided yet).
		add_action( 'admin_notices', array( $this, 'show_opt_in_notice' ) );

		// Handle the "Sure" / "No thanks" dismiss actions.
		add_action( 'admin_init', array( $this, 'handle_opt_in_action' ) );
	}

	/**
	 * Schedules the daily cron job if the user has opted in.
	 *
	 * @return void
	 */
	public function maybe_schedule(): void {
		if ( ! $this->is_opted_in() ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );

			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Sends a single anonymous ping to the server.
	 *
	 * Called by WordPress cron on the daily schedule.
	 *
	 * @return void
	 */
	public function send_ping(): void {
		if ( ! $this->is_opted_in() ) {
			return;
		}

		// Build a consistent, anonymous site hash.
		// Uses home_url() + wp_salt() so the hash survives domain changes
		// but never reveals the actual URL.
		$site_hash = hash_hmac( 'sha256', home_url(), wp_salt( 'auth' ) );
		$payload   = wp_json_encode( array(
			'site_hash' => $site_hash,
			'version'   => $this->version,
		) );

		wp_remote_post( $this->ping_url, array(
			'timeout' => 10,
			'body'    => $payload,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		) );
	}

	/**
	 * Shows a dismissible admin notice asking the user to opt in to telemetry.
	 *
	 * Only shown once — after dismissal the choice is stored permanently.
	 *
	 * @return void
	 */
	public function show_opt_in_notice(): void {
		// Only show on All My Tools plugin admin pages or the plugins list.
		$screen = get_current_screen();
		if ( ! $screen || ( 'plugins' !== $screen->id && 'settings_page_eccl-settings' !== $screen->id ) ) {
			return;
		}

		// Bail if the user already made a choice.
		if ( $this->has_decided() ) {
			return;
		}

		$opt_in_url = add_query_arg( array(
			'ecll_telemetry_action' => 'opt_in',
			'_ecll_nonce'           => wp_create_nonce( 'ecll_telemetry' ),
		) );

		$opt_out_url = add_query_arg( array(
			'ecll_telemetry_action' => 'opt_out',
			'_ecll_nonce'           => wp_create_nonce( 'ecll_telemetry' ),
		) );

		echo '<div class="notice notice-info is-dismissible">';
		echo '<p><strong>CSS Lite for Elementor:</strong> Help improve the plugin by sharing anonymous install data? No personal information is sent.</p>';
		echo '<p>';
		echo '<a href="' . esc_url( $opt_in_url ) . '" class="button button-primary" style="margin-right:8px;">Sure, count me in</a>';
		echo '<a href="' . esc_url( $opt_out_url ) . '" class="button">No thanks</a>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Handles the opt-in / opt-out action from the admin notice links.
	 *
	 * @return void
	 */
	public function handle_opt_in_action(): void {
		$action = sanitize_key( $_GET['ecll_telemetry_action'] ?? '' );

		if ( ! in_array( $action, array( 'opt_in', 'opt_out' ), true ) ) {
			return;
		}

		if ( ! isset( $_GET['_ecll_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_ecll_nonce'] ) ), 'ecll_telemetry' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings_Page::get_settings();
		$settings[ self::OPT_IN_KEY ] = ( 'opt_in' === $action ) ? 1 : 0;
		update_option( Settings_Page::OPTION_NAME, $settings );

		$this->maybe_schedule();

		// Send an immediate ping so the active install shows up right away.
		if ( 'opt_in' === $action ) {
			$this->send_ping();
		}
	}

	/**
	 * Whether the user has opted in to telemetry.
	 *
	 * @return bool
	 */
	private function is_opted_in(): bool {
		$settings = Settings_Page::get_settings();

		return ! empty( $settings[ self::OPT_IN_KEY ] );
	}

	/**
	 * Whether the user has made a decision about telemetry (opt in or out).
	 *
	 * Returns false when the key doesn't exist yet (first visit).
	 *
	 * @return bool
	 */
	private function has_decided(): bool {
		$settings = Settings_Page::get_settings();

		return isset( $settings[ self::OPT_IN_KEY ] );
	}
}
