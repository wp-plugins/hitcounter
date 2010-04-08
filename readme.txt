=== hitcounter ===

Contributors:      Tom de Bruin, Tom Chippendale
Plugin Name:       hitcounter
Plugin URI:        http://posterous.deadlyhifi.com/hitcounter-wordpress-plugin-to-track-and-disp
Tags:              post count, hit count, stats, statistics
Author URI:        http://www.deadlyhifi.com
Author:            Tom de Bruin
Requires at least: 2.3
Tested up to:      2.9
Stable tag:        1.0
Version:           1.0

== Description ==

A simple counter for the number of visitors to your site. Distinguishes between robots, registered human, and non registered humans.
Stores the data in a database table by the name of 'hitcounter'.

== Installation ==

Upload the hitcounter plugin to your blog, activate it, win.

Place `<?php userViews(); ?>` in your template files to output the number of views by humans.
The default output will be <span class="views">Views: 100</span>

If you want to override the output use `<?php userViews('Wow, ',' people read this.'); ?>`
Which would output: Wow, 100 people read this.

== Changelog ==

= 1.0 =
Initial release.

== Frequently Asked Questions ==

If installing on WPMU activate the plugin on a per blog basis rather than the activate sitewide. 
At this stage I'm not sure how to make it MU compatible.

== Donations ==

Charity of your choice.