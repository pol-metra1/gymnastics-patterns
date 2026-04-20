=== Gymnastics Pattern Generator ===
Contributors: yourname
Tags: gymnastics, pattern, leotard, sewing, pdf
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional pattern generator for rhythmic gymnastics leotards with optional skirt.

== Description ==
This plugin allows registered users to input 30+ body measurements and generate a custom leotard pattern in PDF, with optional skirt styles. Patterns are tiled across A4 pages with alignment marks.

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/`.
2. Manually place TCPDF library in `gymnastics-patterns/vendor/tcpdf/`.
3. Activate the plugin.
4. Use shortcodes `[gymnastics_pattern_form]` and `[gymnastics_my_patterns]`.

== Frequently Asked Questions ==
= How to add TCPDF? =
Download TCPDF from https://tcpdf.org and extract to `vendor/tcpdf/`.

== Changelog ==
= 1.0.0 =
* Initial release.
