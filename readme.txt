=== Fonzy - AI Content Publisher ===
Contributors: fonzyai
Tags: fonzy, content, publishing, seo, ai
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connects your WordPress site to Fonzy.ai for automated article publishing with SEO meta support.

== Description ==

Fonzy is an AI-powered content strategy and publishing platform. This plugin creates a custom REST API endpoint on your WordPress site that allows Fonzy to publish articles directly, complete with:

* Post creation with HTML content
* Featured image from URL (automatic download to media library)
* SEO meta fields for Yoast SEO and RankMath
* Tag and category assignment
* Duplicate slug handling

== Installation ==

1. Upload the `fonzy-wp-plugin` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Users → Profile and create an Application Password
4. In your Fonzy dashboard, go to Settings → Integrations → WordPress
5. Enter your site URL, username, and the application password

== Frequently Asked Questions ==

= Do I need the plugin to use Fonzy with WordPress? =

The plugin is recommended for the best experience (SEO meta support, reliable image handling), but Fonzy can also publish via the built-in WordPress REST API without the plugin.

= Which SEO plugins are supported? =

Yoast SEO and RankMath. Meta title, meta description, and focus keyword are set automatically.

== Changelog ==

= 1.0.0 =
* Initial release
* REST API endpoint for article publishing
* Featured image sideloading
* Yoast SEO and RankMath meta field support
* Category and tag management
* Connection validation endpoint
