/**
 * CSS Lite for Elementor – Live Editor CSS Injection
 *
 * Injects custom CSS into the Elementor preview iframe in real-time
 * as the user edits CSS Lite fields. This avoids the delay caused by
 * Elementor's post-CSS file only regenerating on page reload.
 *
 * @package CssLiteForElementor
 */

( function ( $ ) {
	'use strict';

	var STYLE_PREFIX = 'eccl-live-';

	/**
	 * Gets the preview iframe document with a cross-origin safety.
	 *
	 * @return {Document|null}
	 */
	function getIframeDoc() {
		var iframe = document.getElementById( 'elementor-preview-iframe' );
		if ( ! iframe ) {
			return null;
		}
		try {
			return iframe.contentDocument || iframe.contentWindow.document;
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Creates, updates, or removes a <style> element inside the iframe.
	 *
	 * @param {string} id  Unique element id for the style tag.
	 * @param {string} css CSS content (empty string removes the tag).
	 */
	function upsertStyle( id, css ) {
		var doc = getIframeDoc();
		if ( ! doc || ! doc.head ) {
			return;
		}

		var el = doc.getElementById( id );

		if ( ! css || ! css.trim() ) {
			if ( el ) {
				el.parentNode.removeChild( el );
			}
			return;
		}

		if ( ! el ) {
			el = doc.createElement( 'style' );
			el.id = id;
			el.type = 'text/css';
			doc.head.appendChild( el );
		}

		el.textContent = css;
	}

	/**
	 * Reads eccl_custom_css from an Elementor model and injects it into the iframe.
	 *
	 * @param {object} model Elementor element Backbone model.
	 */
	function applyElementCss( model ) {
		if ( ! model || ! model.get || ! model.getSetting ) {
			return;
		}

		var css = model.getSetting( 'eccl_custom_css' ) || '';
		var elId = model.get( 'id' );

		if ( ! elId ) {
			return;
		}

		var styleId = STYLE_PREFIX + elId;

		if ( css.trim() ) {
			css = css.replace( /\bselector\b/gi, '.elementor-element-' + elId );
		}

		upsertStyle( styleId, css );
	}

	/**
	 * Injects page-level custom CSS into the iframe.
	 *
	 * @param {string} css Page-level CSS (can be empty).
	 */
	function applyPageCss( css ) {
		upsertStyle( STYLE_PREFIX + 'page', css || '' );
	}

	/**
	 * Walks every Elementor element model and injects its stored CSS.
	 */
	function syncAllFromModels() {
		var el = window.elementor;
		if ( ! el || ! el.elements ) {
			return;
		}

		el.elements.models.forEach( applyElementCss );

		if ( el.settings && el.settings.page && el.settings.page.getSetting ) {
			applyPageCss( el.settings.page.getSetting( 'eccl_page_custom_css' ) || '' );
		}
	}

	/**
	 * Initialises listeners after Elementor boots.
	 */
	$( window ).on( 'elementor:init', function () {
		var el = window.elementor;
		if ( ! el ) {
			return;
		}

		// Initial sync once the iframe document is ready.
		el.on( 'document:loaded', function () {
			setTimeout( syncAllFromModels, 150 );
		} );

		// Live change listener — Elementor's internal data channel broadcasts
		// Backbone "change" events whenever element or page settings are modified.
		if ( el.channels && el.channels.data ) {
			el.channels.data.on( 'change', function ( model ) {
				if ( ! model || ! model.changedAttributes ) {
					return;
				}

				var changed = model.changedAttributes();

				if ( changed && changed.eccl_custom_css !== undefined ) {
					applyElementCss( model );
				}

				if ( changed && changed.eccl_page_custom_css !== undefined ) {
					applyPageCss( changed.eccl_page_custom_css );
				}
			} );
		}
	} );
} )( jQuery );
