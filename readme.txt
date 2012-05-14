=== hitcounter ===

Contributors:      Tom de Bruin
Plugin Name:       hitcounter
Plugin URI:        http://wordpress.org/extend/plugins/hitcounter/
Tags:              view count, hit count, stats, statistics, readers, reader views
Author URI:        http://www.deadlyhifi.com
Author:            Tom de Bruin
Tested up to:      3.0
Stable tag:        1.3.1
Version:           1.3.1

== Description ==

THIS PLUGIN IS NO LONGER SUPPORTED.
There may be a new version in the future but for now…nothing.

A simple plugin to count and display the number of visitors on your blog posts (i.e. a read count).
It distinguishes between robots, registered human, and non registered humans.
The data is stored in a database table by the name of 'hitcounter'.

== Installation ==

THIS PLUGIN IS NO LONGER SUPPORTED.
There may be a new version in the future but for now…nothing.

Upload the hitcounter plugin to your blog, activate it, win.

Place `<?php userViews(); ?>` in your template files to output the number of views by humans.
The default output will be `<span class="views">Views: 100</span>`

If you want to override the output use `<?php userViews('<div class="viewers">Wow, ',' people read this.</div>'); ?>`
Which would output: `<div class="viewers">Wow, 100 people read this.</div>`

== Changelog ==

THIS PLUGIN IS NO LONGER SUPPORTED.
There may be a new version in the future but for now…nothing.

= 1.0 =
Initial release.

=1.0.1 =
Fixed a silly mistake where the_content was not returned correctly if not on is_single().

=1.1=
Added icons to represent users and robots.
Added column to post edit page to show views counts.

=1.2=
Add options page to allow view counts to be switched on or off on the post listings page.
Added option to fire hit on the_excerpt() rather than the_content() if both are displayed on your single post page. Therefore fixing the "double count" issue.
(http://wordpress.org/support/topic/404253)

=1.3=
Added option to ignore registered users with a user level greater than... set on option page.
Added option to ignore post author.
Fixed DB table to have default value of 0.
Saw the light and changed the code to fire on the_post() - eradicating the silliness of version 1.2.
General code tidy.
A bit of this, a bit of that.

=1.4=
End of life notice

== Frequently Asked Questions ==

THIS PLUGIN IS NO LONGER SUPPORTED.
There may be a new version in the future but for now…nothing.

If installing on WPMU/WP Multisite activate the plugin on a per site basis rather than the 'network activate'. 
See http://wordpress.org/support/topic/421758, http://core.trac.wordpress.org/ticket/14170, http://wordpress.org/extend/plugins/proper-network-activation/
if you need to do that.

Thanks to Tom Chippendale for a few pointers here and there.

Icons from "Token" Icon Set by brsev:

http://brsev.com/
http://brsev.deviantart.com/art/Token-128429570

== Donations ==

THIS PLUGIN IS NO LONGER SUPPORTED.
There may be a new version in the future but for now…nothing.

Charity of your choice.