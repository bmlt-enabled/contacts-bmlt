=== Contacts BMLT ===

Contributors: bmlt-authors
Plugin URI: https://wordpress.org/plugins/contacts-bmlt/
Tags: bmlt, basic meeting list toolbox, Contacts, narcotics anonymous, na
Requires at least: 4.0
Requires PHP: 5.6
Tested up to: 5.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Contacts BMLT is a plugin that displays helpline and website information about service bodies using the contacts_bmlt shortcode.

SHORTCODE
Basic: [contacts_bmlt]
Attributes: root_server, display_type, parent_id

-- Shortcode parameters can be combined

== Usage ==

A minimum of root_server needs to be set.

Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot;]

**display_type** To change the display type add display_type=&quot;table&quot; there are two different types **table**, **block**
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; display_type=&quot;table&quot;]

**parent_id** This will only display service bodies who has set parent_id.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; parent_id=&quot;22&quot;]

**show_description** This will display the service bodies description underneath the name if set.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_description=&quot;1&quot;]


== EXAMPLES ==

<a href="https://sca.charlestonna.org/region-contacts/">https://sca.charlestonna.org/region-contacts/</a>

== MORE INFORMATION ==

<a href="https://github.com/bmlt-enabled/contacts-bmlt" target="_blank">https://github.com/bmlt-enabled/contacts-bmlt</a>


== Installation ==

This section describes how to install the plugin and get it working.

1. Download and install the plugin from WordPress dashboard. You can also upload the entire Contacts BMLT Plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Add [contacts_bmlt] shortcode to your WordPress page/post.
4. At a minimum assign root_server.

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png

== Changelog ==

= 1.0.0 =

* Initial WordPress submission.
