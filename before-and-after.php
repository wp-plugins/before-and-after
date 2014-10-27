<?php
/*
Plugin Name: Before And After - Lead Capture Plugin for Wordpress
Plugin URI: http://goldplugins.com/our-plugins/before-and-after/
Description: Before And After is a lead capture plugin for Wordpress. It allows a webmaster to require visitors to complete a goal, such as filling out a contact form, before viewing the content inside the shortcode. This functionality is also useful when webmaster's want to ensure visitors read a Terms Of Service or Copyright Notice before viewing a given page.
Author: Gold Plugins
Version: 2.0.1
Author URI: http://goldplugins.com

This plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin .  If not, see <http://www.gnu.org/licenses/>.
*/
	
include('include/b_a-custom-post-type.php');
include('include/backfill_functions.php');
include('include/ba.settings.page.class.php');
include('include/ba.goal_model.class.php');
include('include/ba.conversion_model.class.php');
include('include/ba.notification_model.class.php');
include('include/ba.menus.class.php');
include('include/ba.shortcodes.class.php');
include('include/ba.cf7.plugin.php');
include('include/ba.gravityforms.plugin.php');
include('include/ba_kg.php');


class BeforeAndAfterPlugin
{
	var $plugin_title = 'Before & After';
	var $proUser = false;
	
	/* Plugin init. Registers shortcodes and starts the session if needed */
	function __construct()
	{
		// first, ensure the session has been started so that we'll be able to mark goals as completed
		if(session_id() == '') {
			session_start();
		}
		
		// check the reg key
		$this->verify_registration_key();

		// instantiate our subclasses
		$this->Goal = new BA_Goal_Model( $this );
		$this->Conversions = new BA_Conversion_Model( $this );
		$this->Notifications = new BA_Notification_Model ( $this );
		$this->Menus = new BA_Menus( $this );
		$this->Shortcodes = new BA_Shortcodes( $this );
		$this->Settings = new BA_Settings_Page( $this );
		$this->CF7_Plugin = new BA_CF7_Plugin( $this );
		$this->GForms_Plugin = new BA_GravityForms_Plugin( $this );

		add_action( 'admin_head', array($this, 'admin_css') );
		add_action( 'init', array($this, 'catch_download_links') );
		
	}
		
	function admin_css()
	{
		if(is_admin()) {
			$css_url = plugins_url( 'assets/css/flags.css' , __FILE__ );
			wp_register_style('before-and-after-flags', $css_url);
			wp_enqueue_style('before-and-after-flags');
		}	
	}
	
	function show_completed_goals()
	{
?>
		<h1>Hello Then!</h1>
		<p>Fancy a cup of tea?</p>
<?php		
	}
	
	function catch_download_links()
	{
		if (isset($_GET['file_download']))
		{
			$download_key = sanitize_text_field($_GET['file_download']);
			$file_url = '';
			
			// find the conversion which matches this key
			$args = array(	'post_type' => 'b_a_conversion',
							'posts_per_page' => 1,
							'meta_key' => '_b_a_download_key', 
							'meta_value' => $download_key );
			
			$conversion_list = get_posts($args);
			$conversion = count($conversion_list) > 0 ? array_shift($conversion_list) : false;
			if ($conversion) {
				// find the matching goal
				$goal_id = intval(get_post_meta($conversion->ID, 'goal_id', true));
				$goal = $goal_id > 0 ? get_post($goal_id) : false;
				if($goal)
				{
					$after_action = get_post_meta($goal->ID, '_goal_after_action', true);
					if ($after_action && $after_action == 'file_url') {
						$file_url = $this->Goal->get_goal_setting_value($goal->ID, 'after-values', 'file_url', '');
					}
				}
			}

			if ($file_url != '') {
				//die('Real Download URL: ' . $file_url);
				wp_redirect( $file_url , 301 );
				exit; 				
			}
			else {
				die('Invalid URL. Please check the link and try again.');
			}
		}
	}
	
	// check the reg key, and set $this->isPro to true/false reflecting whether the Pro version has been registered
	function verify_registration_key()
	{
		$options = get_option( 'b_a_options' );	
		if (isset($options['api_key']) && 
			isset($options['registration_email']) && 
			isset($options['registration_url']) ) {
		
				// check the key
				$keychecker = new B_A_KeyChecker();
				$correct_key = $keychecker->computeKey($options['registration_url'], $options['registration_email']);
				if (strcmp($options['api_key'], $correct_key) == 0) {
					$this->proUser = true;
				} else {
					$this->proUser = false;
				}
		
		} else {
			// keys not set, so can't be valid.
			$this->proUser = false;
			
		}
		
		// look for the Pro plugin - this is also a way to be validated
		$plugin = "before-and-after-pro/before-and-after-pro.php";
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );			
		if(is_plugin_active($plugin)){
			$this->proUser = true;
		}
		
	}

	function is_pro()
	{
		if (isset($_GET['fake_pro']) && $_GET['fake_pro'] == '1') {
			return true;
		}
		else {
			return $this->proUser;
		}
	}

}

// Instantiate one copy of the plugin class, to kick things off
$beforeAndAfter = new BeforeAndAfterPlugin();