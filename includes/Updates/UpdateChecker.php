<?php
/**
 * WordPress update checker for self-hosted plugins.
 *
 * Calls the All My Tools API to discover newer stable releases
 * and injects them into WordPress' built-in plugin update flow.
 *
 * @package CssLiteForElementor
 */

namespace ECCL\Updates;

// Stop direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connects this plugin to the All My Tools update server.
 */
final class UpdateChecker {

	/**
	 * API endpoint for the update manifest.
	 *
	 * @var string
	 */
	private string $api_url;

	/**
	 * The plugin slug on the update server.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * The installed plugin version.
	 *
	 * @var string
	 */
	private string $current_version;

	/**
	 * How long (in seconds) to cache the API response.
	 *
	 * @var int
	 */
	private int $cache_ttl = 43200; // 12 hours

	/**
	 * Transient key used for response caching.
	 *
	 * @var string
	 */
	private string $cache_key;

	/**
	 * Sets up the checker with API endpoint, plugin slug, and current version.
	 */
	public function __construct() {
		$this->api_url        = 'https://allmytools.xyz/api/plugins/css-lite-for-elementor';
		$this->slug           = 'css-lite-for-elementor';
		$this->current_version = \ECCL_VERSION;
		$this->cache_key      = 'ecll_update_' . md5( $this->api_url );
	}

	/**
	 * Registers WordPress hooks so the checker runs during the normal update cycle.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// When the plugin is upgraded, clear the cached manifest so the next
		// update check immediately sees the new version available.
		add_action( 'init', array( $this, 'invalidate_cache_on_upgrade' ) );

		// Called when WordPress checks for plugin updates.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );

		// Called when the user clicks "View version details" on the plugins page.
		add_filter( 'plugins_api', array( $this, 'inject_plugin_info' ), 20, 3 );
	}

	/**
	 * Clears the update cache when the plugin version has changed (an upgrade).
	 *
	 * Without this, upgrading from 1.0.2 → 1.0.3 would leave stale cached
	 * manifest data pointing to the old version, and WordPress wouldn't
	 * find the next update until the 12-hour TTL expired.
	 *
	 * @return void
	 */
	public function invalidate_cache_on_upgrade(): void {
		$stored_version = get_option( 'ecll_stored_version', '' );

		if ( $stored_version !== $this->current_version ) {
			delete_transient( $this->cache_key );
			update_option( 'ecll_stored_version', $this->current_version, false );
		}
	}

	/**
	 * Fetches the latest release manifest from All My Tools.
	 *
	 * Returns null when the API is unreachable, returns an error, or has no
	 * stable release yet — silently skipping the update check in those cases.
	 *
	 * @return array|null Decoded manifest array on success, null on failure.
	 */
	private function fetch_manifest(): ?array {
		if ( isset( $_GET['force-check'] ) && $_GET['force-check'] === '1' ) {
			delete_transient( $this->cache_key );
		}

		// Return cached response when available.
		$cached = get_transient( $this->cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get( $this->api_url, array(
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['version'] ) ) {
			return null;
		}

		set_transient( $this->cache_key, $data, $this->cache_ttl );

		return $data;
	}

	/**
	 * Injects update information into the plugin update transient.
	 *
	 * Called by the 'pre_set_site_transient_update_plugins' filter.
	 *
	 * @param object $transient The update transient from WordPress.
	 * @return object The modified transient.
	 */
	public function inject_update( $transient ) {
		// Bail if WordPress is still building the transient object for the first time.
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$manifest = $this->fetch_manifest();

		if ( null === $manifest ) {
			return $transient;
		}

		$new_version = $manifest['version'] ?? '';
		if ( $new_version === '' ) {
			return $transient;
		}

		// Only add the update when the server version is strictly greater.
		if ( ! version_compare( $new_version, $this->current_version, '>' ) ) {
			return $transient;
		}

		$package = $manifest['download_url'] ?? '';

		$update = (object) array(
			'slug'        => $this->slug,
			'plugin'      => ECCL_BASENAME,
			'new_version' => $new_version,
			'package'     => $package,
			'url'         => 'https://allmytools.xyz/plugins/' . $this->slug,
			'requires'    => $manifest['requires'] ?? '6.0',
			'tested'      => $manifest['tested'] ?? '',
			'requires_php'=> $manifest['requires_php'] ?? '7.4',
			'icons'       => array(
				'1x'      => ECCL_URL . 'assets/images/icon-128x128.jpg',
				'2x'      => ECCL_URL . 'assets/images/icon-256x256.jpg',
				'default' => ECCL_URL . 'assets/images/icon-128x128.jpg',
			),
		);

		$transient->response[ ECCL_BASENAME ] = $update;

		return $transient;
	}

	/**
	 * Supplies plugin info when WordPress asks for it (e.g. "View version details").
	 *
	 * Called by the 'plugins_api' filter.
	 *
	 * @param false|object $result The default result (false = not processed yet).
	 * @param string       $action The requested action ('plugin_information').
	 * @param object       $args   Request arguments including the plugin slug.
	 * @return false|object Plugin information object or false to keep default behaviour.
	 */
	public function inject_plugin_info( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ( $args->slug ?? '' ) !== $this->slug ) {
			return $result;
		}

		$manifest = $this->fetch_manifest();

		if ( null === $manifest ) {
			return $result;
		}

		$changelog = ! empty( $manifest['changelog'] )
			? '<p>' . esc_html( $manifest['changelog'] ) . '</p>'
			: '<p>View the full changelog on <a href="https://allmytools.xyz/plugins/' . esc_attr( $this->slug ) . '/changelog" target="_blank">allmytools.xyz</a>.</p>';

		return (object) array(
			'name'          => 'CSS Lite for Elementor',
			'slug'          => $this->slug,
			'version'       => $manifest['version'] ?? '',
			'author'        => '<a href="https://allmytools.xyz">THEWPBRO</a>',
			'homepage'      => 'https://allmytools.xyz/plugins/' . $this->slug,
			'download_link' => $manifest['download_url'] ?? '',
			'requires'      => $manifest['requires'] ?? '6.0',
			'tested'        => $manifest['tested'] ?? '',
			'requires_php'  => $manifest['requires_php'] ?? '7.4',
			'last_updated'  => $manifest['last_updated'] ?? '',
			'sections'      => array(
				'description' => 'Add lightweight custom CSS controls for Elementor elements, pages, and site-wide design helpers.',
				'changelog'   => $changelog,
			),
			'icons'         => array(
				'1x'      => ECCL_URL . 'assets/images/icon-128x128.jpg',
				'2x'      => ECCL_URL . 'assets/images/icon-256x256.jpg',
				'default' => ECCL_URL . 'assets/images/icon-128x128.jpg',
			),
			// You can enable banners later if you design them!
			// 'banners'       => array(
			// 	'low'  => ECCL_URL . 'assets/images/banner-772x250.png',
			// 	'high' => ECCL_URL . 'assets/images/banner-1544x500.png',
			// ),
		);
	}
}
