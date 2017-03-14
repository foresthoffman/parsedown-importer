=== Parsedown Importer ===
Contributors: foresthoffman
Tags: posts, pages, admin, importer
Requires at least: 3.7
Tested up to: 4.7
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An unofficial Parsedown importer for translating Markdown files into WordPress posts/pages.

== Description ==

This plugin allows users to import Markdown files into posts. Prior to importing, settings for post status, post type, and post author can be set.

Post status settings:
1. Draft (default)
2. Publish
3. Private

Post type settings:
1. Post (default)
2. Page

Post author settings:
1. Current user (default)
2. All other users with the ability to edit posts

This plugin utilizes the [Parsedown](http://parsedown.org) PHP library by [Emanuil Rusev](http://erusev.com), which is mostly compliant with the [CommonMark](http://spec.commonmark.org/0.27/) spec. It also extends up the Parsedown library, by also allowing:
* checkboxes; '[ ]' and '[x]' are translated into unchecked/checked checkbox inputs

== Installation ==

1. Upload `pdi.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Usage ==

*Note: Only accounts with the ability to import will see the 'Tools' > 'Parsedown Import' sub-menu.*

1. Navigate to the 'Tools' > 'Parsedown Import' sub-menu
2. Click the 'Select Files' button, and select one or more Markdown (.md) files from the prompt
3. Optionally change the import settings
4. Click the 'Import' button

== Changelog ==

= 1.0.1 =
* Updated checkbox regex to allow brackets with no space in between.

= 1.0.0 =
* Updated readme(s) and plugin details.
* Init repo
