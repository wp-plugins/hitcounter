<?php
/*
Plugin Name: hitcounter
Plugin URI: http://wordpress.org/extend/plugins/hitcounter/
Description: Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot.
Version: 1.1
Author: Tom de Bruin
Author URI: http://deadlyhifi.com

Place <?php userViews(); ?> in your template files to output the number of views by humans.

The default output will be <span class="views">Views: 100</span>

If you want to override the output use:

<?php userViews('Wow, ',' people read this.'); ?>

Which would output: Wow, 100 people read this.

------------------------------------------------------------------------------------------

GNU General Public License, version 2
Copyright (C) 2010, Singletrack Mountain Bike Magazine (Gofar Enterprises)
http://www.singletrackworld.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

------------------------------------------------------------------------------------------

Icons from "Token" Icon Set by brsev:

http://brsev.com/
http://brsev.deviantart.com/art/Token-128429570

------------------------------------------------------------------------------------------
*/

//error_reporting(E_ALL);
global $hc_db_version;
$hc_db_version = "1.0";
global $hc_table;
$hc_table = $wpdb->prefix.'hitcounter';


/**
 * Create the databases
 * 
 */
	function hc_install() {
		global $wpdb, $hc_db_version, $hc_table;
	
		$hc_table = $wpdb->prefix.$hc_table;
	
		// create event table
		$sql = "CREATE TABLE " . $hc_table . " (
			post_id mediumint(9) NOT NULL,
			bot_count mediumint(9) NOT NULL,
			reg_count mediumint(9) NOT NULL,
			unreg_count mediumint(9) NOT NULL,
			UNIQUE KEY post_id (post_id)
		);";
	
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	
		// create table if it doesn't exist
		if ($wpdb->get_var("show tables like '$hc_table'") != $hc_table) {
	
			dbDelta($sql);
			add_option( "hc_db_version", $hc_db_version, '', 'yes' );
		}
	
		// update table if things have changed.
		$installed_ver = get_option( "hc_db_version" );
		if ( $installed_ver != $hc_db_version ) {
	
			dbDelta($sql);
			update_option( "hc_db_version", $hc_db_version, '', 'yes' );
		}
	}
	
	register_activation_hook(__FILE__, 'hc_install');


/**
 * detect user agent to determine if bot, user, or logged in user
 *
 * @param $content - pass in the content of the post, perform the action.
 * @return return post content.
 */
	function detectAgent($content) {
	
		if ( is_single() ) {
	
			$post_id = get_the_ID();
			$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	
			if ( is_user_logged_in() ) {
				$agent = 'reg';
			} elseif ( preg_match('(mozilla|gecko|khtml|msie|presto|trident|opera|blackberry|htc|\blg|mot|nokia|playstation|psp|samsung|sonyericsson)', $agent) ) {
				$agent = 'unreg';
			} else {
				$agent = 'robot';
			}
	
			logCount($agent, $post_id);
		}
		return $content;
	}
	
	add_filter('the_content', 'detectAgent');

/**
 * Log the count by adding one depending on the user agent passed from detectAgent()
 *
 * @param unknown $agent
 * @param unknown $post_id
 */
	function logCount($agent, $post_id) {
		global $wpdb, $hc_table;
	
		if ( $agent == 'reg' ) {
			$wpdb->query("INSERT INTO ".$hc_table." (post_id, reg_count) VALUES (".$post_id.", 1) ON DUPLICATE KEY UPDATE reg_count = reg_count+1");
		}
		if ( $agent == 'unreg' ) {
			$wpdb->query("INSERT INTO ".$hc_table." (post_id, unreg_count) VALUES (".$post_id.", 1) ON DUPLICATE KEY UPDATE unreg_count = unreg_count+1");
		}
		if ( $agent == 'robot' ) {
			$wpdb->query("INSERT INTO ".$hc_table." (post_id, bot_count) VALUES (".$post_id.", 1) ON DUPLICATE KEY UPDATE bot_count = bot_count+1");
		}
	}

/**
 * Output the number of views using the template tag userViews()
 *
 * Adds registered and unregistered views together to produce total.
 * Does not count robot views.
*/
	function userViews($before = '<span class="views">Views: ', $after = '</span>') {
		global $wpdb, $hc_table;
	
		$post_id = get_the_ID();
		$count = $wpdb->get_row("SELECT * FROM ".$hc_table." WHERE post_id = ".$post_id);
		$views = number_format($count->reg_count + $count->unreg_count);
		$views = $before.$views.$after;
	
		echo $views;
	}

/**
 * Fetches the view count per post
 * 
 * returns array(bot_count, reg_count, unreg_count, human_count)
 */   
	function get_views($post_id) {
		global $wpdb, $hc_table, $post;
		
		$count = $wpdb->get_row("SELECT * FROM ".$hc_table." WHERE post_id = ".$post_id);
		$viewcount[] = number_format($count->bot_count);
		$viewcount[] = number_format($count->reg_count);
		$viewcount[] = number_format($count->unreg_count);
		$viewcount[] = number_format($count->reg_count + $count->unreg_count);
		
		return $viewcount;
	}

/**
 * Display the view data on the post edit page.
 * 
 */   
	function userViewsPostAdmin() {
		add_meta_box(
			'userviews', // id of the <div> we'll add
			'Views', //title
			'userViewsPostAdminData', // callback function that will echo the box content
			'post', // where to add the box: on "post", "page", or "link" page
			'normal', // The part of the page where the edit screen section should be shown ('normal' or 'advanced')
			'low' // The priority within the context where the boxes should show ('high' or 'low')
		);
	}

	if (is_admin()) 
		add_action('admin_menu', 'userViewsPostAdmin');
	
// fill the data viewer with the data	
	function userViewsPostAdminData() {
		global $post;
			
	       	$plugurl = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	       	 
	        $views = get_views($post->ID);
	
			// total humans
			echo '<p style="background: url('.$plugurl.'/icons/iconic.png) no-repeat 0 -150px; padding-left: 14px;">'.$views[3].' total humans</p>';
			// registered humans
			echo '<p style="background: url('.$plugurl.'/icons/iconic.png) no-repeat 0 -50px; padding-left: 14px;">'.$views[1].'</strong> registered humans</p>';
			// unregistered humans
			echo '<p style="background: url('.$plugurl.'/icons/iconic.png) no-repeat 0 -100px; padding-left: 14px;">'.$views[2].'</strong> unregistered humans</p>';
			// robots
			echo '<p style="background: url('.$plugurl.'/icons/iconic.png) no-repeat 0 0; padding-left: 14px;">'.$views[0].'</strong> robots</p>';
	}


/**
 * Add a column to the edit post page
 *
 */
	function views_columns($defaults) {
	    $defaults['views'] = __('Views');
	    return $defaults;
	}

	add_filter( 'manage_posts_columns', 'views_columns' );

// fill that column with view data
	function views_custom_column($column_name, $id) {
	    if( $column_name == 'views' ) {

	       	$plugurl = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));

	        $views = get_views($id);

			// total humans
			echo '<span style="background: url('.$plugurl.'/icons/iconic.png) no-repeat 0 -150px; padding-left: 14px;">'.$views[3].'</strong></span> ';
			// robots
			echo '<span style="background: url('.$plugurl.'/icons/iconic.png) no-repeat 0 0; padding-left: 14px;">'.$views[0].'</strong></span><br />';
			// registered humans
			echo '<span style="background: url('.$plugurl.'/icons/iconic.png) no-repeat 0 -50px; padding-left: 14px;">'.$views[1].'</strong></span> ';
			// unregistered humans
			echo '<span style="background: url('.$plugurl.'/icons/iconic.png) no-repeat 0 -100px; padding-left: 14px;">'.$views[2].'</strong></span>';

	    }      
	}

	add_action('manage_posts_custom_column', 'views_custom_column', 10, 2);
	
// make the column the correct width
	function views_custom_column_css() {
	?>
	<style type="text/css">/* hitcounter */.fixed .column-views {width: 15%;}</style>
	<?php
	}

	add_action('admin_head', 'views_custom_column_css');

?>