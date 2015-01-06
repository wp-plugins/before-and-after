<?php
class BA_Menus
{
	var $root;
	
	public function __construct($root)
	{
		$this->root = $root;

		// setup hooks to create the admin menus
        add_action( 'admin_menu', array( $this, 'create_primary_admin_menu' ), 10 ); // note: set priority to <=9, else the "Goals" custom post type will override the first submenu
		add_action( 'admin_menu', array( $this, 'create_admin_submenus' ), 10 );		
	}
	
	// Creates the "Before & After" menu heading, and adds the Settings submenu to it
	function create_primary_admin_menu()
	{
		// Note: this will also create a child menu with the same name. 
		// We'll override that name in the next step, to make it read "Settings"
		add_menu_page( 
			$this->root->plugin_title . ' Settings',
			$this->root->plugin_title, 
			'manage_options',
			'before-and-after-settings',
			array( $this->root->Settings, 'output_settings_page' )
		);
	}
	
	// Add the submenus to our Before & After primary menu
	function create_admin_submenus()
	{

 		if (!$this->root->is_pro())
		{
			// Remove the normal Conversions menu, as we are not using the Pro version
			remove_submenu_page(
				'before-and-after-settings',
				'edit.php?post_type=b_a_conversion'	
			);
			// If they are not upgraded to Pro yet, show them a screenshot of the conversions menu
 			add_submenu_page(
				'before-and-after-settings',
				'Conversions', 
				'Conversions',
				'manage_options', 
				'upgrade-to-b_a_pro',
				array($this->root->Conversions, 'show_upgrade_message')
				
			);
		}

		// Because we want the main menu to be called "Before & After", but the first menu to be called "Settings", we'll need to override the title now by creating a duplicate menu with the correct title ("Settings")
		add_submenu_page('before-and-after-settings', $this->root->plugin_title . ' Settings', 'Settings', 'manage_options', 'before-and-after-settings', array( $this->root->Settings, 'output_settings_page' ) );

		add_submenu_page(
			'before-and-after-settings',
			'Help & Troubleshooting', 
			'Help & Troubleshooting',
			'manage_options', 
			'b_a_help_and_troubleshooting',
			array($this, 'show_help_page')
			
		);
		
		
		
		
		/* 	add_submenu_page(
			'before-and-after-settings',
			'Completed Goals', 
			'Completed Goals',
			'manage_options', 
			'before-and-after-completed_goals',
			array( $this->root, 'show_completed_goals' )
		);
		 */
	}
	function show_upgrade_page()
	{
		echo "<h3>Upgrade To Before & After Pro</h3>";
		echo "You should upgrade to PRO! Then you'd be tracking Goal Conversions.";
	}
	function show_help_page()
	{
		include('pages/help.html');
	}
	
}