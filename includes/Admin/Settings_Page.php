<?php
/**
 * Admin settings page.
 *
 * @package CssLiteForElementor
 */

namespace ECCL\Admin;

use ECCL\Security\Css_Sanitizer;

// Stop direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the plugin settings screen.
 */
final class Settings_Page {
	/**
	 * WordPress option name used for plugin settings.
	 */
	const OPTION_NAME = 'eccl_settings';

	/**
	 * Registers admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Returns normalized settings with defaults applied.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'enabled'              => 1,
			'post_types'           => array( 'page', 'post' ),
			'site_css'             => '',
			'administrators_only'  => 0,
			'delete_on_uninstall'  => 0,
			'telemetry_opt_in'     => 0,
		);

		$stored = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, $defaults );
	}

	/**
	 * Adds the settings page under the WordPress Settings menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			esc_html__( 'CSS Lite for Elementor', 'css-lite-for-elementor' ),
			esc_html__( 'CSS Lite', 'css-lite-for-elementor' ),
			'manage_options',
			'eccl-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueues CodeMirror for the settings page.
	 *
	 * @param string $hook The current admin page.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_eccl-settings' !== $hook ) {
			return;
		}

		$settings = wp_enqueue_code_editor( array( 
			'type'       => 'text/css',
			'codemirror' => array(
				'lineWrapping' => true,
			),
		) );

		if ( false === $settings ) {
			return;
		}

		wp_add_inline_script(
			'code-editor',
			sprintf(
				'jQuery( function() { wp.codeEditor.initialize( "eccl-site-css", %s ); } );',
				wp_json_encode( $settings )
			)
		);

		// Dark mode theme (VS Code style) for the editor
		$dark_theme_css = '
			.CodeMirror { height: 400px; background: #1e1e1e !important; color: #d4d4d4 !important; border-radius: 4px; border: 1px solid #1e1e1e !important; box-shadow: inset 0 0 5px rgba(0,0,0,0.5); }
			.CodeMirror-gutters { background: #252526 !important; border-right: 1px solid #404040 !important; }
			.CodeMirror-linenumber { color: #858585 !important; }
			.CodeMirror-cursor { border-left: 2px solid #d4d4d4 !important; }
			.CodeMirror .cm-property { color: #9cdcfe !important; }
			.CodeMirror .cm-qualifier { color: #d7ba7d !important; }
			.CodeMirror .cm-string { color: #ce9178 !important; }
			.CodeMirror .cm-number { color: #b5cea8 !important; }
			.CodeMirror .cm-keyword { color: #569cd6 !important; }
			.CodeMirror .cm-def { color: #4ec9b0 !important; }
			.CodeMirror .cm-tag { color: #d7ba7d !important; }
			.CodeMirror .cm-atom { color: #569cd6 !important; }
			.CodeMirror .cm-variable-3, .CodeMirror .cm-type { color: #4ec9b0 !important; }
			.CodeMirror .cm-comment { color: #6a9955 !important; }
			.CodeMirror-selected { background: #264f78 !important; }
			.CodeMirror-activeline-background { background: transparent !important; }
			.CodeMirror-line::selection, .CodeMirror-line>span::selection, .CodeMirror-line>span>span::selection { background: #264f78; }
			.CodeMirror-line::-moz-selection, .CodeMirror-line>span::-moz-selection, .CodeMirror-line>span>span::-moz-selection { background: #264f78; }
		';
		wp_add_inline_style( 'code-editor', $dark_theme_css );
	}

	/**
	 * Registers the plugin setting and its fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'eccl_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_settings(),
			)
		);

		add_settings_section(
			'eccl_main_section',
			esc_html__( 'Custom CSS Settings', 'css-lite-for-elementor' ),
			array( $this, 'render_section_intro' ),
			'eccl-settings'
		);

		add_settings_field( 'enabled', esc_html__( 'Enable plugin', 'css-lite-for-elementor' ), array( $this, 'render_enabled_field' ), 'eccl-settings', 'eccl_main_section' );
		add_settings_field( 'site_css', esc_html__( 'Site custom CSS', 'css-lite-for-elementor' ), array( $this, 'render_site_css_field' ), 'eccl-settings', 'eccl_main_section' );
		add_settings_field( 'post_types', esc_html__( 'Allowed post types', 'css-lite-for-elementor' ), array( $this, 'render_post_types_field' ), 'eccl-settings', 'eccl_main_section' );
		add_settings_field( 'administrators_only', esc_html__( 'Editing access', 'css-lite-for-elementor' ), array( $this, 'render_administrators_only_field' ), 'eccl-settings', 'eccl_main_section' );
		add_settings_field( 'delete_on_uninstall', esc_html__( 'Uninstall cleanup', 'css-lite-for-elementor' ), array( $this, 'render_delete_on_uninstall_field' ), 'eccl-settings', 'eccl_main_section' );

		add_settings_field( 'telemetry_opt_in', esc_html__( 'Anonymous usage data', 'css-lite-for-elementor' ), array( $this, 'render_telemetry_field' ), 'eccl-settings', 'eccl_main_section' );
	}

	/**
	 * Cleans settings before they are stored in the database.
	 *
	 * @param array $input Raw settings from the submitted form.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input      = is_array( $input ) ? $input : array();
		$post_types = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? $input['post_types'] : array();
		$valid      = array_keys( $this->get_public_post_types() );
		$sanitizer  = new Css_Sanitizer();

		// Only save post types that actually exist and are public in WordPress.
		$post_types = array_values( array_intersect( array_map( 'sanitize_key', $post_types ), $valid ) );

		return array(
			'enabled'             => empty( $input['enabled'] ) ? 0 : 1,
			'post_types'          => $post_types,
			'site_css'            => $sanitizer->sanitize( isset( $input['site_css'] ) ? $input['site_css'] : '' ),
			'administrators_only' => empty( $input['administrators_only'] ) ? 0 : 1,
			'delete_on_uninstall' => empty( $input['delete_on_uninstall'] ) ? 0 : 1,
			'telemetry_opt_in'    => empty( $input['telemetry_opt_in'] ) ? 0 : 1,
		);
	}

	/**
	 * Renders a short intro for the settings section.
	 *
	 * @return void
	 */
	public function render_section_intro() {
		echo '<p>' . esc_html__( 'Control where CSS Lite for Elementor is available and who can use it.', 'css-lite-for-elementor' ) . '</p>';
	}

	/**
	 * Renders the enable/disable checkbox.
	 *
	 * @return void
	 */
	public function render_enabled_field() {
		$settings = self::get_settings();

		printf(
			'<label><input type="checkbox" name="%1$s[enabled]" value="1" %2$s> %3$s</label>',
			esc_attr( self::OPTION_NAME ),
			checked( 1, (int) $settings['enabled'], false ),
			esc_html__( 'Add custom CSS controls to Elementor elements.', 'css-lite-for-elementor' )
		);
	}

	/**
	 * Renders the site-level CSS field.
	 *
	 * @return void
	 */
	public function render_site_css_field() {
		$settings = self::get_settings();

		printf(
			'<textarea id="eccl-site-css" name="%1$s[site_css]" rows="14" class="large-text code" spellcheck="false">%2$s</textarea><p class="description">%3$s</p>',
			esc_attr( self::OPTION_NAME ),
			esc_textarea( $settings['site_css'] ),
			esc_html__( 'CSS saved here loads across the whole site. Use this for reusable classes and global design helpers.', 'css-lite-for-elementor' )
		);
	}

	/**
	 * Renders post type checkboxes.
	 *
	 * @return void
	 */
	public function render_post_types_field() {
		$settings   = self::get_settings();
		$post_types = $this->get_public_post_types();

		foreach ( $post_types as $name => $post_type ) {
			printf(
				'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="%1$s[post_types][]" value="%2$s" %3$s> %4$s</label>',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $name ),
				checked( in_array( $name, (array) $settings['post_types'], true ), true, false ),
				esc_html( $post_type->labels->singular_name )
			);
		}
	}

	/**
	 * Renders the administrator-only access checkbox.
	 *
	 * @return void
	 */
	public function render_administrators_only_field() {
		$settings = self::get_settings();

		printf(
			'<label><input type="checkbox" name="%1$s[administrators_only]" value="1" %2$s> %3$s</label>',
			esc_attr( self::OPTION_NAME ),
			checked( 1, (int) $settings['administrators_only'], false ),
			esc_html__( 'Only users who can manage site options can edit custom CSS.', 'css-lite-for-elementor' )
		);
	}

	/**
	 * Renders the uninstall cleanup checkbox.
	 *
	 * @return void
	 */
	public function render_delete_on_uninstall_field() {
		$settings = self::get_settings();

		printf(
			'<label><input type="checkbox" name="%1$s[delete_on_uninstall]" value="1" %2$s> %3$s</label>',
			esc_attr( self::OPTION_NAME ),
			checked( 1, (int) $settings['delete_on_uninstall'], false ),
			esc_html__( 'Delete plugin settings when the plugin is uninstalled.', 'css-lite-for-elementor' )
		);
	}

	/**
	 * Renders the full settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'css-lite-for-elementor' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'CSS Lite for Elementor', 'css-lite-for-elementor' ) . '</h1>';
		echo '<form action="' . esc_url( admin_url( 'options.php' ) ) . '" method="post">';
		settings_fields( 'eccl_settings_group' );
		do_settings_sections( 'eccl-settings' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Renders the telemetry opt-in checkbox.
	 *
	 * @return void
	 */
	public function render_telemetry_field() {
		$settings = self::get_settings();

		printf(
			'<label><input type="checkbox" name="%1$s[telemetry_opt_in]" value="1" %2$s> %3$s</label><p class="description">%4$s</p>',
			esc_attr( self::OPTION_NAME ),
			checked( 1, (int) ( $settings['telemetry_opt_in'] ?? 0 ), false ),
			esc_html__( 'Share anonymous install data (plugin version only) to help improve CSS Lite for Elementor.', 'css-lite-for-elementor' ),
			esc_html__( 'No personal data, URL, or identifying information is ever sent.', 'css-lite-for-elementor' )
		);
	}

	/**
	 * Gets public post types where Elementor content can reasonably exist.
	 *
	 * @return array
	 */
	private function get_public_post_types() {
		return get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);
	}
}
