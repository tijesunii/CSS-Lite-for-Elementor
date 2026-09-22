<?php
/**
 * CSS sanitization helpers.
 *
 * @package CssLiteForElementor
 */

namespace ECCL\Security;

// Stop direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes user-authored CSS before it is printed in a style tag.
 */
final class Css_Sanitizer {
	/**
	 * Maximum CSS characters allowed per Elementor element.
	 */
	const MAX_LENGTH = 20000;

	/**
	 * Cleans a CSS string and rejects patterns that are unsafe in inline CSS.
	 *
	 * @param string $css Raw CSS from Elementor settings.
	 * @return string Safe-enough CSS for scoped inline output, or an empty string when rejected.
	 */
	public function sanitize( $css ) {
		if ( ! is_string( $css ) ) {
			return '';
		}

		// WordPress slashes request data, so unslashing keeps saved CSS readable.
		$css = wp_unslash( $css );

		// Normalize line endings so pattern checks behave consistently.
		$css = str_replace( array( "\r\n", "\r" ), "\n", $css );
		$css = trim( $css );

		if ( '' === $css ) {
			return '';
		}

		// Very large CSS blocks make pages heavy and are more likely to be accidental paste dumps.
		if ( strlen( $css ) > self::MAX_LENGTH ) {
			$css = substr( $css, 0, self::MAX_LENGTH );
		}

		// Remove HTML tags before checking CSS-specific risk patterns.
		$css = wp_strip_all_tags( $css );
		$css = str_replace( "\0", '', $css );

		if ( $this->contains_blocked_pattern( $css ) ) {
			return '';
		}

		return $css;
	}

	/**
	 * Checks for CSS patterns that can break out of style tags or execute unsafe behavior.
	 *
	 * @param string $css CSS after basic cleanup.
	 * @return bool
	 */
	private function contains_blocked_pattern( $css ) {
		$blocked_patterns = array(
			'/<\/\s*style/i',
			'/<\s*script/i',
			'/expression\s*\(/i',
			'/javascript\s*:/i',
			'/vbscript\s*:/i',
			'/data\s*:\s*text\/html/i',
			'/behavior\s*:/i',
			'/-moz-binding\s*:/i',
			'/@import\b/i',
		);

		foreach ( $blocked_patterns as $pattern ) {
			if ( preg_match( $pattern, $css ) ) {
				return true;
			}
		}

		return false;
	}
}

