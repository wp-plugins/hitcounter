<?php
/*
Plugin Name: hitcounter
Plugin URI: http://wordpress.org/extend/plugins/hitcounter/
Description: Enables You To Display How Many Times A Post Had Been Viewed By User Or Bot. Don't forget to visit the <a href="options-general.php?page=hitcounter">Settings Page</a>. See readme.txt before doing a Network Activate.
Version: 1.3
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
global $table_prefix, $wpdb;
global $hc_db_version;
$hc_db_version = "1.1";
global $hc_table;
$hc_table = $table_prefix.'hitcounter';
global $hc_options;
$hc_options = get_option('hitcounter_options');

/**
 * Create the database
 */
	function hc_install() {
		global $wpdb, $hc_db_version, $hc_table;
	
		// create event table
		$sql = "CREATE TABLE " . $hc_table . " (
			post_id mediumint(9) NOT NULL,
			bot_count mediumint(9) DEFAULT '0' NOT NULL,
			reg_count mediumint(9) DEFAULT '0' NOT NULL,
			unreg_count mediumint(9) DEFAULT '0' NOT NULL,
			UNIQUE KEY post_id (post_id)
		);";
	
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	
		// create table if it doesn't exist
		if ($wpdb->get_var("show tables like '$hc_table'") != $hc_table) {
	
			dbDelta($sql);
			add_option( 'hc_db_version', $hc_db_version, '', 'yes' );
			
		}
	
		// update table if things have changed.
		$installed_ver = get_option( "hc_db_version" );
		
		if ( $installed_ver != $hc_db_version ) {
	
			dbDelta($sql);
			update_option( 'hc_db_version', $hc_db_version, '', 'yes' );
		}
	}
	
	register_activation_hook(__FILE__, 'hc_install');

/**
 * detect user agent to determine if bot, user, or logged in user
 *
 * check admin settings - user level to log, and if to log post author.
 *
 * @param $content - pass in the content of the post, perform the action.
 * @return - return post content after having incremented view count
 */
	function detectAgent($content) {
		global $hc_options, $current_user;

		if ( is_single() ) {
	
			$post_id = get_the_ID();
			$agent = strtolower($_SERVER['HTTP_USER_AGENT']);

			if ( is_user_logged_in() ) {
			
				// if user is logged in get the user info
				// and check if we want to log the user
				get_currentuserinfo();
				$capability = $hc_options['rolelevel'];
				$ignore_author = $hc_options['author'];

				// if ignore author is set and current user is the author do nothing.
				if ( $ignore_author == '1' && $current_user->ID == get_the_author_meta(ID) ) {
					// do nothing
				} else {	
					// check capability of current user - compare to ignore option.
					// If no users are ignored the $capability is 0.
					if ( !current_user_can( $capability ) || $capability == '0' ) {
							$count = 'reg';
					}
				}

			} elseif ( preg_match('(mozilla|gecko|khtml|msie|presto|trident|opera|blackberry|htc|\blg|mot|nokia|playstation|psp|samsung|sonyericsson)', $agent) ) {
				$count = 'unreg';
			} else {
				$count = 'robot';
			}
			
			logCount($count, $post_id);
		}
		return $content;
	}
/*	
if ( $hc_options['excerpt'] )
	add_filter('the_excerpt', 'detectAgent');
else	
	add_filter('the_content', 'detectAgent');
*/	
	add_filter('the_post', 'detectAgent');

/**
 * Log the count by adding one depending on the user agent passed from detectAgent()
 *
 * @param $count - type of viewer: reg, unreg, or robot.
 * @param $post_id - ID if the currently viewed post.
 * @return - nothing.
 */
	function logCount($count, $post_id) {
		global $wpdb, $hc_table;
		
		switch ($count) {
    	case 'reg':
        	$wpdb->query("INSERT INTO ".$hc_table." (post_id, reg_count) VALUES (".$post_id.", 1) ON DUPLICATE KEY UPDATE reg_count = reg_count+1");
        	break;
   		case 'unreg':
			$wpdb->query("INSERT INTO ".$hc_table." (post_id, unreg_count) VALUES (".$post_id.", 1) ON DUPLICATE KEY UPDATE unreg_count = unreg_count+1");
       		break;
    	case 'robot':
			$wpdb->query("INSERT INTO ".$hc_table." (post_id, bot_count) VALUES (".$post_id.", 1) ON DUPLICATE KEY UPDATE bot_count = bot_count+1");
        	break;
 		default:
 			break;
		}
	}

/**
 * Output the number of views using the template tag userViews()
 * Adds registered and unregistered views together to produce total.
 *
 * @param $before - html to place before output - default provided
 * @param $after - html to place after output - default provided
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
 * @param $post_id - ID if the currently viewed post.
 * @return - array(bot_count, reg_count, unreg_count, human_count)
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
	
/**
 * Fill the data viewer with the data
 */   	
	function userViewsPostAdminData() {
		global $post;
	       	 
	        $views = get_views($post->ID);
	
			// total humans
			echo '<p class="iconic humans"><strong>'.$views[3].'</strong> total humans</p>';
			// registered humans
			echo '<p class="iconic registered"><strong>'.$views[1].'</strong> registered humans</p>';
			// unregistered humans
			echo '<p class="iconic unregistered"><strong>'.$views[2].'</strong> unregistered humans</p>';
			// robots
			echo '<p class="iconic robots"><strong>'.$views[0].'</strong> robots</p>';
	}

/**
 * Add a column to the edit post page
 */
	function views_columns($defaults) {
	    $defaults['views'] = __('Views');
	    return $defaults;
	}

/**
 * fill that column with view data
 */
	function views_custom_column($column_name, $id) {
		global $hc_options;
	    
	    if( $column_name == 'views' ) {

	        $views = get_views($id);

			if ( $hc_options['display'] ) {
				// total humans
				echo '<span class="iconic humans">'.$views[3].'</span> ';
				
				if ( !$hc_options['displaydetail'] ) echo '<br />';
				
				// robots
				echo '<span class="iconic robots">'.$views[0].'</span>';
			}
			
			if ( $hc_options['display'] && $hc_options['displaydetail'] ) echo '<br />';
			
			if ( $hc_options['displaydetail'] ) {
				// registered humans
				echo '<span class="iconic registered">'.$views[1].'</span> ';
				
				if ( !$hc_options['display'] ) echo '<br />';
				
				// unregistered humans
				echo '<span class="iconic unregistered">'.$views[2].'</span>';
			}
	    }
	}

	// only display the column if the options are enabled.
	if ( $hc_options['display'] || $hc_options['displaydetail'] ) {

		add_filter('manage_posts_columns', 'views_columns');
		add_action('manage_posts_custom_column', 'views_custom_column', 10, 2);

	}
	
/**
 * make the column the correct width
 */
	function views_custom_column_css() {
		global $hc_options;

		if ( $hc_options['display'] && $hc_options['displaydetail'] )
			$width = '12';
		else
			$width = '8';

		$plugurl = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	?>
	<style type="text/css">/* hitcounter */
	.fixed .column-views {width:<?php echo $width; ?>%;}
	.iconic {background: url(<?php echo $plugurl; ?>/icons/iconic.png) no-repeat; padding-left: 14px;}
	.iconic.robots {background-position:0 0;}
	.iconic.humans {background-position:0 -150px;}
	.iconic.unregistered {background-position:0 -100px;}
	.iconic.registered {background-position:0 -50px;}
	</style>
	
	<?php
	}

	add_action('admin_head', 'views_custom_column_css');

/////////////////////////////////////

/**
 * Create the options page
 * http://codex.wordpress.org/Creating_Options_Pages
 * http://codex.wordpress.org/Function_Reference/register_setting
 * http://planetozh.com/blog/2009/05/handling-plugins-options-in-wordpress-28-with-register_setting/
 */
	function hitcounter_options_menu() {
	
		add_options_page('Hitcounter', 'Hitcounter', 'manage_options', 'hitcounter', 'hitcounter_settings_page');
	
		//call register settings function
		add_action( 'admin_init', 'register_hitcounter_settings' );
	
	}
	
	// create custom plugin settings menu
	add_action('admin_menu', 'hitcounter_options_menu');

/**
 * Register our settings
 */
	function register_hitcounter_settings() {

		register_setting( 'hitcounter-settings-group', 'hitcounter_options' );

	}
	
/**
 * Create the Settings page
 */
	function hitcounter_settings_page() {
		global $hc_options;
	?>
	<div class="wrap">
	
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>Hitcounter Options</h2>

	<form method="post" action="options.php">
	    <?php settings_fields( 'hitcounter-settings-group' ); ?>
	    
	    <table class="form-table">
	    
	        <tr valign="top">
		        <th scope="row" colspan="2"><h3>Show or hide view data on posts listing page</h3></th>
	        </tr>
	    
	        <tr valign="top">
	        <th scope="row"><span class="iconic humans">100</span> <span class="iconic robots">100</span></th>
		        <td>
		        	<input type="checkbox" name="hitcounter_options[display]" value="1" <?php checked('1', $hc_options['display']); ?> />
		        	<span class="description">Show human and robots view counts.</span>
		        </td>
	        </tr>

	        <tr valign="top">
		        <th scope="row"><span class="iconic registered">100</span> <span class="iconic unregistered">100</span></th>
		        <td>
		        	<input type="checkbox" name="hitcounter_options[displaydetail]" value="1" <?php checked('1', $hc_options['displaydetail']); ?> />
		        	<span class="description">Show registered and unregistered view counts.</span>
		        </td>
	        </tr>

	        <tr valign="top">
		        <th scope="row" colspan="2"><h3>Ignore Users&hellip;</h3></th>
	        </tr>
	        
	        <tr valign="top">
	        <th scope="row">Post Author</th>
		        <td>
		        	<input type="checkbox" name="hitcounter_options[author]" value="1" <?php checked('1', $hc_options['author']); ?> />
		        	<span class="description">Check to ignore post author.</span>
		        </td>
	        </tr>

	        <tr valign="top">
		        <th scope="row">Role</th>
		        <td>
		        	<select name="hitcounter_options[rolelevel]">
		        		<option value="0" <?php selected('read', $hc_options['rolelevel']); ?>>Count Everyone</option>
		        		<option value="edit_posts" <?php selected('edit_posts', $hc_options['rolelevel']); ?>>Contributor</option>
		        		<option value="publish_posts" <?php selected('publish_posts', $hc_options['rolelevel']); ?>>Author</option>
		        		<option value="manage_categories" <?php selected('manage_categories', $hc_options['rolelevel']); ?>>Editor</option>
		        		<option value="install_themes" <?php selected('install_themes', $hc_options['rolelevel']); ?>>Administrator</option>
		        	</select>
		        	<span class="description">Logged in users with this or a higher capability will not be counted.</span>
		        </td>
	        </tr>

		</table>
	    
	    <p class="submit">
	    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	    </p>
	
	</form>
	</div>

<?php }

?>