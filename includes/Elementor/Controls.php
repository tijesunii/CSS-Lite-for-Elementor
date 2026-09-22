<?php
/**
 * Elementor editor controls.
 *
 * @package CssLiteForElementor
 */

namespace ECCL\Elementor;

use Elementor\Controls_Manager;

// Stop direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the custom CSS field to Elementor elements.
 */
final class Controls {
	/**
	 * Elementor setting key used to store custom CSS on each element.
	 */
	const FIELD_KEY = 'eccl_custom_css';

	/**
	 * Elementor document setting key used to store page-level custom CSS.
	 */
	const PAGE_FIELD_KEY = 'eccl_page_custom_css';

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Tracks elements that already received our section during the current request.
	 *
	 * @var array
	 */
	private $registered_elements = array();

	/**
	 * Stores settings for later permission and post-type checks.
	 *
	 * @param array $settings Plugin settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Registers Elementor hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'elementor/documents/register_controls', array( $this, 'add_page_custom_css_section' ) );
		add_action( 'elementor/element/after_section_end', array( $this, 'add_custom_css_section' ), 20, 3 );
		add_action( 'elementor/element/container/section_layout/after_section_end', array( $this, 'add_custom_css_section_to_container' ), 20, 2 );
		add_action( 'elementor/element/container/section_background/after_section_end', array( $this, 'add_custom_css_section_to_container' ), 20, 2 );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_styles' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
	}

	/**
	 * Adds a page-level CSS Lite field to Elementor Page Settings.
	 *
	 * @param \Elementor\Core\DocumentTypes\PageBase $document Elementor document instance.
	 * @return void
	 */
	public function add_page_custom_css_section( $document ) {
		if ( ! class_exists( '\Elementor\Core\DocumentTypes\PageBase' ) || ! $document instanceof \Elementor\Core\DocumentTypes\PageBase ) {
			return;
		}

		if ( ! $document::get_property( 'has_elements' ) ) {
			return;
		}

		if ( ! $this->user_can_edit_css() ) {
			return;
		}

		$post_id = method_exists( $document, 'get_main_id' ) ? (int) $document->get_main_id() : 0;

		if ( $post_id && ! $this->is_allowed_post_type( $post_id ) ) {
			return;
		}

		$document->start_controls_section(
			'eccl_page_custom_css_section',
			array(
				'label' => esc_html__( 'CSS Lite', 'css-lite-for-elementor' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		$document->add_control(
			self::PAGE_FIELD_KEY,
			array(
				'label'       => esc_html__( 'Page Custom CSS', 'css-lite-for-elementor' ),
				'type'        => $this->get_css_control_type(),
				'language'    => 'css',
				'rows'        => 18,
				'description' => esc_html__( 'CSS saved here only loads on this Elementor page. Use normal selectors, or selector to target this page body.', 'css-lite-for-elementor' ),
				'render_type' => 'ui',
				'selectors'   => array(
					'{{WRAPPER}}' => '/* css lite */',
				),
			)
		);

		$document->end_controls_section();
	}

	/**
	 * Loads small editor-only styling for the code field.
	 *
	 * @return void
	 */
	public function enqueue_editor_styles() {
		wp_enqueue_style(
			'eccl-editor',
			ECCL_URL . 'assets/editor.css',
			array(),
			ECCL_VERSION
		);
	}

	/**
	 * Loads editor-only JavaScript for live preview injection.
	 *
	 * @return void
	 */
	public function enqueue_editor_scripts() {
		wp_enqueue_script(
			'eccl-editor-js',
			ECCL_URL . 'assets/editor.js',
			array( 'jquery', 'elementor-editor' ),
			ECCL_VERSION,
			true
		);
	}

	/**
	 * Adds the Custom CSS section after Elementor's built-in Advanced section.
	 *
	 * @param \Elementor\Controls_Stack $element    Elementor element being edited.
	 * @param string                    $section_id Current Elementor section ID.
	 * @param array                     $args       Section registration arguments.
	 * @return void
	 */
	public function add_custom_css_section( $element, $section_id, $args ) {
		unset( $args );

		if ( ! $this->is_supported_insertion_point( $element, $section_id ) ) {
			return;
		}

		if ( $this->has_registered_for_element( $element ) ) {
			return;
		}

		if ( ! $this->user_can_edit_css() ) {
			return;
		}

		if ( ! $this->is_allowed_post_type() ) {
			return;
		}

		$element->start_controls_section(
			'eccl_custom_css_section',
			array(
				'label' => esc_html__( 'CSS Lite', 'css-lite-for-elementor' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			self::FIELD_KEY,
			array(
				'label'       => esc_html__( 'Custom CSS', 'css-lite-for-elementor' ),
				'type'        => $this->get_css_control_type(),
				'language'    => 'css',
				'rows'        => 18,
				'description' => esc_html__( 'Use selector for this exact element, or write normal CSS classes/selectors like .dot-grid-bg.', 'css-lite-for-elementor' ),
				'render_type' => 'ui',
				'selectors'   => array(
					'{{WRAPPER}}' => '/* css lite */',
				),
			)
		);

		$element->add_control(
			'eccl_custom_css_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Tip: add a class in Advanced > CSS Classes, then write CSS for that class here.', 'css-lite-for-elementor' ),
				'content_classes' => 'elementor-control-field-description',
			)
		);

		$element->end_controls_section();

		$this->mark_registered_for_element( $element );
	}

	/**
	 * Adds the Custom CSS section through Elementor's container-specific hooks.
	 *
	 * Elementor containers can skip the older Advanced section IDs used by widgets,
	 * so this bridge makes flexbox/grid containers receive the same control.
	 *
	 * @param \Elementor\Controls_Stack $element Elementor container being edited.
	 * @param array                     $args    Section registration arguments.
	 * @return void
	 */
	public function add_custom_css_section_to_container( $element, $args ) {
		$this->add_custom_css_section( $element, 'section_layout', $args );
	}

	/**
	 * Checks whether the current user can edit custom CSS.
	 *
	 * @return bool
	 */
	private function user_can_edit_css() {
		if ( ! empty( $this->settings['administrators_only'] ) ) {
			return current_user_can( 'manage_options' );
		}

		return current_user_can( 'edit_posts' );
	}

	/**
	 * Checks whether the current edited post type is enabled in plugin settings.
	 *
	 * @return bool
	 */
	private function is_allowed_post_type( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = $this->get_current_post_id();
		}

		if ( ! $post_id ) {
			return true;
		}

		$post_type     = get_post_type( $post_id );
		$allowed_types = isset( $this->settings['post_types'] ) ? (array) $this->settings['post_types'] : array();

		return $post_type && in_array( $post_type, $allowed_types, true );
	}

	/**
	 * Gets the current post ID from WordPress context without reading request data.
	 *
	 * Elementor normally sets the edited post as the current global post while it
	 * registers controls. If no post context exists, the control remains available.
	 *
	 * @return int
	 */
	private function get_current_post_id() {
		global $post;

		if ( $post instanceof \WP_Post ) {
			return (int) $post->ID;
		}

		$queried_id = get_queried_object_id();

		return $queried_id ? (int) $queried_id : 0;
	}

	/**
	 * Uses Elementor's Code control when available, otherwise falls back to a textarea.
	 *
	 * @return string
	 */
	private function get_css_control_type() {
		if ( defined( Controls_Manager::class . '::CODE' ) ) {
			return Controls_Manager::CODE;
		}

		return Controls_Manager::TEXTAREA;
	}

	/**
	 * Checks Elementor section IDs where it makes sense to append our panel.
	 *
	 * @param \Elementor\Controls_Stack $element    Elementor element being edited.
	 * @param string $section_id Elementor section ID currently being registered.
	 * @return bool
	 */
	private function is_supported_insertion_point( $element, $section_id ) {
		$widget_sections = array(
			'_section_advanced',
			'_section_style',
			'section_advanced',
		);

		if ( in_array( $section_id, $widget_sections, true ) ) {
			return true;
		}

		$layout_element_names = array( 'container', 'section', 'column' );
		$layout_sections      = array(
			'section_layout',
			'section_background',
			'section_border',
			'section_position',
			'section_effects',
		);

		// Containers, sections, and columns use layout-focused sections before Advanced controls.
		return in_array( $element->get_name(), $layout_element_names, true ) && in_array( $section_id, $layout_sections, true );
	}

	/**
	 * Checks whether this element already has the CSS Lite section.
	 *
	 * @param \Elementor\Controls_Stack $element Elementor element being edited.
	 * @return bool
	 */
	private function has_registered_for_element( $element ) {
		$hash = spl_object_hash( $element );

		return isset( $this->registered_elements[ $hash ] );
	}

	/**
	 * Marks an Elementor element so we do not add duplicate sections.
	 *
	 * @param \Elementor\Controls_Stack $element Elementor element being edited.
	 * @return void
	 */
	private function mark_registered_for_element( $element ) {
		$this->registered_elements[ spl_object_hash( $element ) ] = true;
	}
}
