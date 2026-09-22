=== CSS Lite for Elementor ===
Contributors: Eben
Donate link: https://allmytools.xyz
Tags: elementor, custom css, css, editor, page builder
Requires at least: 6.0
Tested up to: 7.1.2
Requires PHP: 7.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds lightweight custom CSS controls for Elementor elements, pages, and site-wide design helpers.

== Description ==

CSS Lite for Elementor adds custom CSS fields for Elementor Free users who want faster styling at the element, page, and site level.

Use the `selector` keyword to target the current Elementor element or page body, or write normal CSS selectors and reusable classes.

This plugin is not affiliated with, endorsed by, or sponsored by Elementor.

= Features =

* Adds a CSS Lite control to Elementor elements.
* Adds page-level CSS inside Elementor Page Settings.
* Adds site-level CSS inside Settings > CSS Lite.
* Supports widgets, containers, sections, and columns.
* Supports Elementor-style `selector` replacement.
* Supports normal class selectors such as `.dot-grid-bg`.
* Adds CSS to Elementor's generated stylesheet for editor preview.
* Adds frontend CSS output for published pages.
* Includes a basic CSS safety filter for risky inline CSS patterns.
* Includes an admin settings page for plugin access and post type control.

= Example: current element =

Use `selector` when the CSS should target only the selected Elementor element.

`
selector {
  border-radius: 24px;
}

selector .button:hover {
  transform: translateY(-4px);
}
`

= Example: reusable class =

Add `dot-grid-bg` in Elementor's Advanced > CSS Classes field, then write:

`
.dot-grid-bg {
  position: relative;
}

.dot-grid-bg::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  background-color: #ffffff;
  background-image: radial-gradient(#9ca3af 1.5px, transparent 1.5px);
  background-size: 22px 22px;
}
`

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate CSS Lite for Elementor from the WordPress Plugins screen.
3. Edit a page with Elementor.
4. Select an Elementor element.
5. Open the Advanced tab and use CSS Lite.
6. For page-level CSS, open Elementor Page Settings and use CSS Lite.
7. For site-level CSS, go to Settings > CSS Lite.

== Screenshots ==

1. CSS Lite control inside the Elementor editor.
2. Page-level CSS Lite field inside Elementor Page Settings.
3. Site custom CSS field inside WordPress settings.
4. Class-based dot grid background applied on the frontend.

== Frequently Asked Questions ==

= What does this plugin add? =

It adds custom CSS controls for Elementor elements, Elementor page settings, and site-wide reusable CSS.

= Can I use normal CSS classes? =

Yes. Add a class in Elementor's Advanced > CSS Classes field, then write CSS for that class in CSS Lite.

= Can I use `selector` like Elementor custom CSS examples? =

Yes. The plugin replaces `selector` with the selected Elementor element's unique wrapper selector.

= Will the CSS appear inside the Elementor editor? =

Yes. The plugin adds CSS to Elementor's generated stylesheet so saved custom CSS can appear inside the editor preview and on the frontend.

= Does this support page-level CSS? =

Yes. Open Elementor Page Settings, then use the CSS Lite section.

= Does this support site-level CSS? =

Yes. Go to Settings > CSS Lite and add CSS in the Site custom CSS field.

= Who can edit custom CSS? =

By default, users who can edit posts can see the control. Site admins can enable administrator-only editing from Settings > CSS Lite.

== Security ==

CSS Lite for Elementor rejects dangerous inline CSS patterns such as style tag breakouts, script tags, JavaScript URLs, old IE expressions, behavior rules, and `@import`.

Custom CSS is powerful and should only be available to trusted users. Use the administrator-only setting when clients or contributors should not edit CSS.

== Changelog ==

= 1.0.7 =
* Enhancement: Added WordPress native CodeMirror syntax highlighting and formatting to the Site Custom CSS settings page.

= 1.0.6 =
* Optimized frontend performance by leveraging Elementor's native CSS caching instead of inline generation.
* Improved compatibility with Elementor 3.24+ for Theme Builder documents in the editor.

= 1.0.5 =
* Added live-preview JavaScript injection for instant CSS rendering in the Elementor editor.
* Implemented a hybrid approach that balances instant rendering with FOUC prevention.
* Added a cache bypass to UpdateChecker so manual WordPress update checks fetch the latest release instantly.

= 1.0.4 =
* Improved update detection so new versions appear faster after a release.

= 1.0.3 =
* Active install count now updates right away when you enable usage sharing in the settings page.

= 1.0.2 =
* Active install count now appears immediately when you enable usage sharing, instead of waiting up to 24 hours.

= 1.0.1 =
* Added an optional anonymous usage sharing option. When enabled, only the plugin version is sent to help us understand how many sites are using the plugin. No personal information, site URL, or identifying data is ever collected or transmitted.

= 1.0.0 =
* Initial release.
* Added CSS Lite controls for Elementor elements.
* Added page-level CSS in Elementor Page Settings.
* Added site-level CSS in WordPress plugin settings.
* Added support for widgets, containers, sections, and columns.
* Added `selector` replacement and normal CSS selector support.
* Added frontend and editor-preview CSS output.
* Added admin settings and basic CSS safety filtering.

== Upgrade Notice ==

= 1.0.7 =
Adds a native code editor to the settings page for easier CSS management.

= 1.0.6 =
Performance update. Fixes frontend CSS generation overhead and improves compatibility with newer Elementor versions.

= 1.0.5 =
Improves manual update checks and adds instantaneous live-preview CSS rendering in the Elementor editor.

= 1.0.4 =
Small fix. Improved update detection speed after a release.

= 1.0.3 =
Small fix. Active installs now update right away when you enable usage sharing in the settings page.

= 1.0.2 =
Small fix. Active installs now show up right away instead of taking up to 24 hours.

= 1.0.1 =
Minor update. Adds an optional anonymous usage sharing setting — disabled by default. No personal data is collected.

= 1.0.0 =
Initial release.
