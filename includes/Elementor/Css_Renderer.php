<?php
/**
 * Frontend CSS renderer.
 *
 * @package CssLiteForElementor
 */

namespace ECCL\Elementor;

use ECCL\Security\Css_Sanitizer;

// Stop direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads Elementor data and prints scoped custom CSS for the current page.
 */
final class Css_Renderer {
	/**
	 * Stylesheet handle used for inline CSS.
	 */
	const STYLE_HANDLE = 'eccl-custom-css-lite';

	/**
	 * CSS sanitizer.
	 *
	 * @var Css_Sanitizer
	 */
	private $sanitizer;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Stores dependencies for rendering.
	 *
	 * @param Css_Sanitizer $sanitizer CSS sanitizer.
	 * @param array         $settings  Plugin settings.
	 */
	public function __construct( Css_Sanitizer $sanitizer, array $settings ) {
		$this->sanitizer = $sanitizer;
		$this->settings  = $settings;
	}

	/**
	 * Registers frontend hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scoped_css' ), 99 );
		add_action( 'elementor/element/parse_css', array( $this, 'add_css_to_elementor_stylesheet' ), 10, 2 );
		add_action( 'elementor/css-file/post/parse', array( $this, 'add_document_css_to_elementor_stylesheet' ) );
	}

	/**
	 * Adds page-level CSS to Elementor's generated post stylesheet.
	 *
	 * @param object $post_css Elementor post CSS file object.
	 * @return void
	 */
	public function add_document_css_to_elementor_stylesheet( $post_css ) {
		if ( ! is_object( $post_css ) || ! method_exists( $post_css, 'get_stylesheet' ) ) {
			return;
		}

		$stylesheet = $post_css->get_stylesheet();

		if ( ! is_object( $stylesheet ) || ! method_exists( $stylesheet, 'add_raw_css' ) ) {
			return;
		}

		$post_id = method_exists( $post_css, 'get_post_id' ) ? (int) $post_css->get_post_id() : 0;

		// Cache page CSS natively. Site-level CSS remains inline.
		if ( $post_id && $this->is_allowed_post_type( $post_id ) ) {
			$page_css = $this->get_page_css( $post_id );

			if ( '' !== $page_css ) {
				$page_selector = 'body.elementor-page-' . absint( $post_id );
				$page_css      = $this->replace_selector_placeholder( $page_css, $page_selector );
				$stylesheet->add_raw_css( "/* CSS Lite for Elementor: page {$post_id} */\n" . $page_css );
			}
		}
	}

	/**
	 * Adds custom CSS to Elementor's generated stylesheet for editor previews.
	 *
	 * @param object                  $post_css Elementor post CSS file object.
	 * @param \Elementor\Element_Base $element  Elementor element being parsed.
	 * @return void
	 */
	public function add_css_to_elementor_stylesheet( $post_css, $element ) {
		if ( ! is_object( $post_css ) || ! is_object( $element ) ) {
			return;
		}

		if ( ! method_exists( $element, 'get_settings' ) || ! method_exists( $element, 'get_id' ) ) {
			return;
		}

		$settings = $element->get_settings();

		if ( empty( $settings[ Controls::FIELD_KEY ] ) ) {
			return;
		}

		$css = $this->sanitizer->sanitize( $settings[ Controls::FIELD_KEY ] );

		if ( '' === $css ) {
			return;
		}

		$selector = $this->get_elementor_unique_selector( $post_css, $element );
		$css      = $this->replace_selector_placeholder( $css, $selector );

		if ( ! method_exists( $post_css, 'get_stylesheet' ) ) {
			return;
		}

		$stylesheet = $post_css->get_stylesheet();

		if ( is_object( $stylesheet ) && method_exists( $stylesheet, 'add_raw_css' ) ) {
			$stylesheet->add_raw_css( $css );
		}
	}

	/**
	 * Injects site-level CSS inline.
	 *
	 * @return void
	 */
	public function enqueue_scoped_css() {
		$site_css = $this->get_site_css();

		if ( '' === $site_css ) {
			return;
		}

		$css = "/* CSS Lite for Elementor: site */\n" . $site_css;

		wp_register_style( self::STYLE_HANDLE, false, array(), ECCL_VERSION );
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_add_inline_style( self::STYLE_HANDLE, $css );
	}

	/**
	 * Checks whether this post type is enabled in settings.
	 *
	 * @param int $post_id Current post ID.
	 * @return bool
	 */
	private function is_allowed_post_type( $post_id ) {
		$post_type     = get_post_type( $post_id );
		$allowed_types = isset( $this->settings['post_types'] ) ? (array) $this->settings['post_types'] : array();

		return $post_type && in_array( $post_type, $allowed_types, true );
	}

	/**
	 * Gets sanitized site-level CSS from plugin settings.
	 *
	 * @return string
	 */
	private function get_site_css() {
		$css = isset( $this->settings['site_css'] ) ? $this->settings['site_css'] : '';

		return $this->sanitizer->sanitize( $css );
	}

	/**
	 * Gets sanitized page-level CSS from Elementor page settings.
	 *
	 * @param int $post_id Elementor document post ID.
	 * @return string
	 */
	private function get_page_css( $post_id ) {
		$page_settings = get_post_meta( $post_id, '_elementor_page_settings', true );

		if ( ! is_array( $page_settings ) || empty( $page_settings[ Controls::PAGE_FIELD_KEY ] ) ) {
			return '';
		}

		return $this->sanitizer->sanitize( $page_settings[ Controls::PAGE_FIELD_KEY ] );
	}

	/**
	 * Replaces the Elementor-style selector keyword with the real wrapper selector.
	 *
	 * @param string $css      CSS entered by the user.
	 * @param string $selector Current Elementor element selector.
	 * @return string
	 */
	private function replace_selector_placeholder( $css, $selector ) {
		return preg_replace( '/\bselector\b/i', $selector, $css );
	}

	/**
	 * Gets Elementor's exact generated selector when possible.
	 *
	 * @param object                  $post_css Elementor post CSS file object.
	 * @param \Elementor\Element_Base $element  Elementor element being parsed.
	 * @return string
	 */
	private function get_elementor_unique_selector( $post_css, $element ) {
		if ( method_exists( $post_css, 'get_element_unique_selector' ) ) {
			return $post_css->get_element_unique_selector( $element );
		}

		return '.elementor-element-' . sanitize_html_class( $element->get_id() );
	}
}
