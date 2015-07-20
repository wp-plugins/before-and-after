<?php
/*
Plugin Name: Before And After - Lead Capture Plugin for Wordpress
Plugin URI: http://goldplugins.com/our-plugins/before-and-after/
Description: Before And After is a lead capture plugin for Wordpress. It allows a webmaster to require visitors to complete a goal, such as filling out a contact form, before viewing the content inside the shortcode. This functionality is also useful when webmaster's want to ensure visitors read a Terms Of Service or Copyright Notice before viewing a given page.
Author: Gold Plugins
Version: 2.5.2
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
include('include/ba.hubspot.php');


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
		$this->HubSpot = new BA_HubSpot( $this );

		add_action( 'admin_head', array($this, 'admin_css') );
		add_action( 'init', array($this, 'catch_download_links') );

		//add our custom links for Settings and Support to various places on the Plugins page
		$plugin = plugin_basename(__FILE__);
		add_filter( "plugin_action_links_{$plugin}", array($this, 'add_settings_link_to_plugin_action_links') );
		add_filter( 'plugin_row_meta', array($this, 'add_custom_links_to_plugin_description'), 10, 2 );	
	}
		
	function admin_css()
	{
		if(is_admin()) {
			$admin_css_url = plugins_url( 'assets/css/admin_style.css' , __FILE__ );
			wp_register_style('before-and-after-admin', $admin_css_url);
			wp_enqueue_style('before-and-after-admin');
			
			$flags_css_url = plugins_url( 'assets/css/flags.css' , __FILE__ );
			wp_register_style('before-and-after-flags', $flags_css_url);
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
			isset($options['registration_email']) /* && 
			isset($options['registration_url']) */ ) {
		
				// check the key
				$keychecker = new B_A_KeyChecker();
				$correct_key = $keychecker->computeKeyEJ($options['registration_email']);
				if (strcmp($options['api_key'], $correct_key) == 0) {
					$this->proUser = true;
				} else if(isset($options['registration_url']) && isset($options['registration_email'])) {//only check if its an old key if the relevant fields are set
					//maybe its an old style of key
					$correct_key = $keychecker->computeKey($options['registration_url'], $options['registration_email']);
					if (strcmp($options['api_key'], $correct_key) == 0) {
						$this->proUser = true;
					} else {
						$this->proUser = false;
					}
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
		return $this->proUser;
	}

	//add an inline link to the settings page, before the "deactivate" link
	function add_settings_link_to_plugin_action_links($links) { 
	  $settings_link = '<a href="admin.php?page=before-and-after-settings">Settings</a>';
	  array_unshift($links, $settings_link); 
	  return $links; 
	}

	// add inline links to our plugin's description area on the Plugins page
	function add_custom_links_to_plugin_description($links, $file) { 

		/** Get the plugin file name for reference */
		$plugin_file = plugin_basename( __FILE__ );
	 
		/** Check if $plugin_file matches the passed $file name */
		if ( $file == $plugin_file )
		{
			$new_links['settings_link'] = '<a href="admin.php?page=before-and-after-settings">Settings</a>';
			$new_links['support_link'] = '<a href="http://goldplugins.com/contact/?utm-source=plugin_menu&utm_campaign=support&utm_banner=before-and-after" target="_blank">Get Support</a>';
				
			if(!$this->is_pro()){
				$new_links['upgrade_to_pro'] = '<a href="http://goldplugins.com/our-plugins/before-and-after/upgrade-to-before-and-after-pro/?utm_source=plugin_menu&utm_campaign=upgrade" target="_blank">Upgrade to Pro</a>';
			}
			
			$links = array_merge( $links, $new_links);
		}
		return $links; 
	}

}

// Instantiate one copy of the plugin class, to kick things off
$beforeAndAfter = new BeforeAndAfterPlugin();