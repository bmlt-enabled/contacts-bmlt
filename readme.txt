=== Contacts BMLT ===

Contributors: pjaudiomv, bmltenabled
Plugin URI: https://wordpress.org/plugins/contacts-bmlt/
Tags: bmlt, basic meeting list toolbox, Contacts, narcotics anonymous, na
Requires PHP: 8.0
Tested up to: 6.3.2
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Contacts BMLT is a plugin that displays helpline and website information about service bodies using the contacts_bmlt shortcode.

SHORTCODE
Basic: [contacts_bmlt]
Attributes: root_server, display_type, parent_id, show_description, show_email, show_url_in_name, show_tel_url, show_full_url, show_all_services, show_locations

-- Most Shortcode parameters can be combined

== Usage ==

A minimum of root_server needs to be set.

Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot;]

**display_type** To change the display type add display_type=&quot;table&quot; there are two different types **table**, **block** the default is table.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; display_type=&quot;table&quot;]

**parent_id** This will only display service bodies who has set parent_id.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; parent_id=&quot;22&quot;]

**show_description** This will display the service bodies description underneath the name if set.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_description=&quot;1&quot;]

**show_email** This will display the service bodies contact email underneath the name if set.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_email=&quot;1&quot;]

**show_url_in_name** This will add a link to the service body name, this is the default action. To remove the url from the service body name add show_url_in_name=&quot;0quot;.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_url_in_name=&quot;0&quot;]

**show_tel_url** This will add a tel link to the telephone number. Default is to not add it.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_tel_url=&quot;1&quot;]

**show_full_url** This will add a separate column or div with the full url displayed. Default is to not add it.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_full_url=&quot;1&quot;]

**show_all_services** This will display all service bodies regardless of whether they have their phone or URL field filled out. The default is not to display them.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_all_services=&quot;1&quot;]

**show_locations** This will display a list of locations below the service body name. Accepted values are location_neighborhood, location_city_subsection, location_municipality, location_sub_province.
Ex. [contacts_bmlt root_server=&quot;https://www.domain.org/main_server&quot; show_locations=&quot;location_municipality&quot;]


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

= 1.3.0 =

* Note this version requires BMLT Root Server version 3.04 or greater.
* Refactored codebase.

= 1.2.2 =

* Fix for various PHP warnings.

= 1.2.1 =

* Fix for User-Agent issue that appears to be present on SiteGround hosted root servers.

= 1.2.0 =

* Updated version logic for BMLT 3.0.0 compatibility.

= 1.1.5 =

* Bump version, minor changes.

= 1.1.4 =

* Open links in new window.

= 1.1.3 =

* Fix to better comply with WordPress best practices.

= 1.1.2 =

* Fix for unneeded escaping of quotes.

= 1.1.1 =

* Fix casing for location info.

= 1.1.0 =

* Added option to show all service bodies regardless of if they have any contact info.
* Simplified some code with checking the service bodies.
* Added ability to show service body locations under service body name.

= 1.0.0 =

* Initial WordPress submission.
