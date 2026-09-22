# Changelog

All notable changes to CSS Lite for Elementor are documented here.

## 1.0.7

- Enhancement: Integrated WordPress core CodeMirror to transform the Site Custom CSS text box into a beautiful, syntax-highlighted code editor with line wrapping and a slick VS Code-style dark mode theme.
- Fix: Prevented the CSS sanitizer from aggressively stripping all line breaks and minifying code on save.

## 1.0.6

- Optimized frontend performance by leveraging Elementor's native CSS file caching instead of inline style generation.
- Improved compatibility with Elementor 3.24+ for Theme Builder documents in the editor.

## 1.0.5

- Added live-preview JavaScript injection for instant CSS rendering in the Elementor editor.
- Implemented a hybrid approach that balances instant rendering with FOUC prevention.
- Added a cache bypass to `UpdateChecker` so manual WordPress update checks fetch the latest release instantly.

## 1.0.4

- Improved update detection so new versions appear faster after a release.

## 1.0.3

- Active install count now updates right away when you enable usage sharing in the settings page.

## 1.0.2

- Active install count now appears immediately when you enable usage sharing, instead of waiting up to 24 hours.

## 1.0.1

- Added an optional anonymous usage sharing option to help track how many sites are using the plugin.
- Only the plugin version is sent — no personal information, site URL, or identifying data is ever collected.
- Disabled by default. You can enable it from Settings > CSS Lite.

## 1.0.0

- Initial release.
- Added CSS Lite controls for Elementor elements.
- Added page-level CSS in Elementor Page Settings.
- Added site-level CSS in WordPress plugin settings.
- Added support for widgets, containers, sections, and columns.
- Added `selector` replacement and normal CSS selector support.
- Added frontend and editor-preview CSS output.
- Added admin settings and basic CSS safety filtering.