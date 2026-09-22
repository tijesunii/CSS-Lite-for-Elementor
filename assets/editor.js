( function( $ ) {
	'use strict';

	$( window ).on( 'elementor:init', function() {
		var elementor = window.elementor;

		if ( ! elementor ) {
			return;
		}
		
		// Inject custom CSS for elements into live preview.
		elementor.hooks.addFilter( 'editor/style/styleText', function( css, view ) {
			var model = view.getEditModel(),
				customCss = model.get( 'settings' ).get( 'eccl_custom_css' );

			if ( customCss ) {
				var selector = '.elementor-element-' + model.get( 'id' );
				customCss = customCss.replace( /selector/g, selector );
				css += '\n/* CSS Lite Live Preview */\n' + customCss;
			}

			return css;
		} );

		// Safely retrieves the current document model.
		var getPageModel = function() {
			if ( elementor.documents && typeof elementor.documents.getCurrent === 'function' ) {
				var currentDoc = elementor.documents.getCurrent();
				if ( currentDoc && currentDoc.settings ) {
					return currentDoc.settings;
				}
			}
			if ( elementor.settings && elementor.settings.page && elementor.settings.page.model ) {
				return elementor.settings.page.model;
			}
			return null;
		};

		// Inject page-level CSS into the preview iframe.
		var pageModel = getPageModel();
		
		if ( pageModel ) {
			pageModel.on( 'change:eccl_page_custom_css', function( model ) {
				var customCss = model.get( 'eccl_page_custom_css' );
				var $previewHead = elementor.$previewContents.find( 'head' );
				var $styleTag = $previewHead.find( '#eccl-page-custom-css-preview' );

				if ( ! $styleTag.length ) {
					$styleTag = $( '<style>', { id: 'eccl-page-custom-css-preview' } );
					$previewHead.append( $styleTag );
				}

				if ( customCss ) {
					// Resolve the active document ID.
					var pageId = 0;
					if ( elementor.documents && typeof elementor.documents.getCurrent === 'function' ) {
						var currentDoc = elementor.documents.getCurrent();
						if ( currentDoc ) {
							pageId = currentDoc.id;
						}
					}
					if ( ! pageId && elementor.config && elementor.config.document ) {
						pageId = elementor.config.document.id;
					}

					var selector = 'body.elementor-page-' + pageId;
					customCss = customCss.replace( /selector/g, selector );
					$styleTag.text( customCss );
				} else {
					$styleTag.text( '' );
				}
			} );

			// Initialize page CSS and remove server-rendered conflicts.
			elementor.on( 'preview:loaded', function() {
				var activeModel = getPageModel();
				if ( activeModel ) {
					activeModel.trigger( 'change:eccl_page_custom_css', activeModel );
				}
			} );
		}
		
	} );

} )( jQuery );
