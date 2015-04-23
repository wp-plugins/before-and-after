<?php
class BA_Settings_Page
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
	private $plugin_title;
	private $root;

    /**
     * Start up
     */
    public function __construct($root)
    {
		$this->root = $root;
		$this->plugin_title = $root->plugin_title;
        add_action( 'admin_init', array( $this, 'admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'create_settings' ) );
        add_action( 'admin_init', array( $this, 'check_for_clear_cookies' ) );
		add_action('admin_head', array($this, 'output_admin_styles'));
    }
	
	function admin_scripts()
	{
		wp_enqueue_script(
			'gp-admin',
			plugins_url('../assets/js/gp-admin.js', __FILE__),
			array( 'jquery' ),
			false,
			true
		);	
	}
	
	function check_for_clear_cookies()
	{
		if (isset($_POST['b_a_clear_cookies']) && $_POST['b_a_clear_cookies'] == 'go') {
			$this->clear_goal_cookies();
		}
	}
	
	function clear_goal_cookies()
	{
		foreach ($_SESSION as $key => $value)
		{
			// test if $key starts with 'goal_'
			if (strpos($key, 'goal_') === 0) {
				// it does! so delete it
				unset($_SESSION[$key]);
			}
		}
	}
	
    /**
     * Options page callback
	 * Note: Called in ba.menus.class, when the user accesses the Settings menu
     */
    public function output_settings_page()
    {
	
	
		// Set class property
        $this->options = get_option( 'b_a_options' );
        ?>		
		<?php //$this->output_register_plugin_style(); ?>			
		<?php if ( !$this->root->is_pro() ):?>
<script type="text/javascript">
	jQuery(function () {
		if (typeof(gold_plugins_init_mailchimp_form) == 'function') {
		gold_plugins_init_mailchimp_form();
		}
	});
</script>
        <div class="wrap before_after_wrapper gold_plugins_settings">
		<?php else: ?>
        <div class="wrap before_after_wrapper gold_plugins_settings is_pro">
		<?php endif; ?>
            <div id="icon-options-general" class="icon32"></div>
            <h2><?php echo htmlentities($this->plugin_title)?> Settings</h2>           			
			<form method="post" action="options.php">
				<?php
					// This prints out all hidden setting fields
					settings_fields( 'b_a_option_group' );
										
				?>
				<?php if ( !$this->root->is_pro() ):?>
					<div class="ba_registration_settings register_plugin">
					<?php do_settings_sections( 'ba_registration_settings' ); ?>
					<?php submit_button(); ?>			
					</div>
				<?php else: ?>
					<div class="register_plugin is_registered">
						<h3>Before &amp; After Pro: Active</h3>
						<p><strong>&#x2713; &nbsp;  This copy of Before & After Pro is registered to <a href="mailto:<?php echo $this->options['registration_email']; ?>"><?php echo htmlentities($this->options['registration_email']); ?></a>.</strong></p>
						<?php $this->output_hidden_registration_fields(); ?>
					</div>
				<?php endif; ?>
				<?php
					// Output notification settings
					do_settings_sections( 'ba_notifications_settings' );
					submit_button();
				?>
				<?php
					// Output HubSpot settings
					do_settings_sections( 'ba_hubspot_settings' );
					submit_button();
				?>
				
            </form>
			<?php $this->output_clear_cookies_button(); ?>
			<?php if ( !$this->root->is_pro() ) { $this->output_mailing_list_form(); } ?>
        </div>		
        <?php
    }

    /**
     * Register and add settings
     */
    public function create_settings()
    {        	
		// Generic setting. We need this for some reason so that we have a chance to save everything else.
        register_setting(
            'b_a_option_group', // Option group
            'b_a_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'notifications', // ID
            'Notification Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'ba_notifications_settings' // Page
        );  

        add_settings_field(
            'email', // ID
            'Email', // Title 
            array( $this, 'email_callback' ), // Callback
            'ba_notifications_settings', // Page
            'notifications' // Section           
        );      

        add_settings_field(
            'subject', 
            'Subject', 
            array( $this, 'subject_callback' ), 
            'ba_notifications_settings', 
            'notifications'
        ); 
        add_settings_field(
            'email_body', 
            'Email Body:', 
            array( $this, 'email_body_callback' ), 
            'ba_notifications_settings', 
            'notifications'
        );		
        add_settings_section(
            'registration', // ID
            'Pro Registration', // Title
            array( $this, 'print_registration_section_info' ), // Callback
            'ba_registration_settings' // Page
        );  

        add_settings_field(
            'registration_email', // ID
            'Email', // Title 
            array( $this, 'registration_email_callback' ), // Callback
            'ba_registration_settings', // Page
            'registration' // Section           
        );      
        /*add_settings_field(
            'registration_url', // ID
            'Website URL', // Title 
            array( $this, 'registration_url_callback' ), // Callback
            'ba_registration_settings', // Page
            'registration' // Section           
        );*/      
        add_settings_field(
            'api_key', // ID
            'API Key', // Title 
            array( $this, 'api_key_callback' ), // Callback
            'ba_registration_settings', // Page
            'registration' // Section           
        );  

        add_settings_section(
            'hubspot', // ID
            'HubSpot Settings', // Title
            array( $this, 'print_hubspot_section_info' ), // Callback
            'ba_hubspot_settings' // Page
        );  
        add_settings_field(
            'send_to_hubspot', 
            'Post Submissions to HubSpot', 
            array( $this, 'send_to_hubspot_callback' ), 
            'ba_hubspot_settings', 
            'hubspot'
        );    
        add_settings_field(
            'portal_id', // ID
            'HUB ID', // Title 
            array( $this, 'portal_id_callback' ), // Callback
            'ba_hubspot_settings', // Page
            'hubspot' // Section           
        );      

        add_settings_field(
            'form_guid', 
            'Form GUID', 
            array( $this, 'form_guid_callback' ), 
            'ba_hubspot_settings', 
            'hubspot'
        ); 
        add_settings_field(
            'hubspot_blacklist', 
            'HubSpot Blacklist', 
            array( $this, 'hubspot_blacklist_callback' ), 
            'ba_hubspot_settings', 
            'hubspot'
        );    

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
		foreach($input as $key => $value)
		{
			switch($key)
			{
				case 'id_number':
					$new_input['id_number'] = absint( $input['id_number'] );
				break;

				case 'email':
				case 'subject':
				case 'email_body':
				case 'api_key':
				case 'registration_url':
				case 'registration_email':
				case 'portal_id':
				case 'form_guid':
				case 'hubspot_blacklist':
				case 'send_to_hubspot':
					$new_input[$key] = sanitize_text_field( $input[$key] );
				break;			

				default: // don't let any settings through unless they were whitelisted. (skip unknown settings)
					continue;
				break;			
			}
		}
		
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
		if (!$this->root->is_pro()) {
			echo '<p class="gold_plugin_not_registered">This feature requires Before &amp; After Pro. <a target="_blank" href="http://goldplugins.com/our-plugins/before-and-after/upgrade-to-before-and-after-pro/?utm_source=plugin&utm_campaign=notification_fields">Click here</a> to upgrade now!</p>';
		}
        echo '<p>Each time a goal is completed, we\'ll send a notification email.</p>';
		echo '<p><em>Tip: if you don\'t want notifications, just leave the Email field empty.</em></p>';
    }

    /** 
     * Print the HubSpot Section text
     */
    public function print_hubspot_section_info()
    {
		if (!$this->root->is_pro()) {
			echo '<p class="gold_plugin_not_registered">This feature requires Before &amp; After Pro. <a target="_blank" href="http://goldplugins.com/our-plugins/before-and-after/upgrade-to-before-and-after-pro/?utm_source=plugin&utm_campaign=hubspot_fields">Click here</a> to upgrade now!</p>';
		}
        echo '<p>Each time a goal is completed, we can send the data to your HubSpot account.</p>';
    }
	
    /** 
     * Print the Section text
     */
    public function print_registration_section_info()
    {
		echo '<p class="gold_plugin_not_registered">Your plugin is not successfully registered and activated. <a target="_blank" href="http://goldplugins.com/our-plugins/before-and-after/upgrade-to-before-and-after-pro/?utm_source=plugin&utm_campaign=registration_fields">Click here</a> to upgrade today!</p>';
		print '<strong>Enter your registration information below to register your copy of Before & After Pro:</strong>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function email_callback()
    {
        printf(
            '<input type="text" id="email" name="b_a_options[email]" value="%s" style="width:450px" %s />',
            isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : '',
			$this->root->is_pro() ? '' : 'disabled="true"'
        );
		echo '<p class="description">This email will receive the notification emails. Seperate multiple addresses with a comma.</p>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function subject_callback()
    {
        printf(
            '<input type="text" id="subject" name="b_a_options[subject]" value="%s" style="width:450px" %s />',
            isset( $this->options['subject'] ) ? esc_attr( $this->options['subject']) : 'New Conversion: [goal_name]',
			$this->root->is_pro() ? '' : 'disabled="true"'			
        );
		echo '<p class="description">We\'ll use this subject line for all of the notification emails.</p>';
    }

    public function email_body_callback()
    {
        printf(
            '<textarea id="email_body" name="b_a_options[email_body]" style="width:450px;height:200px;" %s>%s</textarea>',
   			$this->root->is_pro() ? '' : 'disabled="true"',
            isset( $this->options['email_body'] ) ? esc_attr( $this->options['email_body']) : "There was a new goal conversion on your website! \n\nPlease respond yourself, or forward this message to the correct person immediately."
        );
		echo '<p class="description">We\'ll append a table with the conversion information to this message.</p>';
    }
	
    public function api_key_callback()
    {
        printf(
            '<input type="text" id="api_key" name="b_a_options[api_key]" value="%s" style="width:450px" />',
            isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key']) : ''
        );
    }	
    public function registration_email_callback()
    {
        printf(
            '<input type="text" id="registration_email" name="b_a_options[registration_email]" value="%s" style="width:450px" />',
            isset( $this->options['registration_email'] ) ? esc_attr( $this->options['registration_email']) : ''
        );
    }
    public function registration_url_callback()
    {
        printf(
            '<input type="text" id="registration_url" name="b_a_options[registration_url]" value="%s" style="width:450px" />',
            isset( $this->options['registration_url'] ) ? esc_attr( $this->options['registration_url']) : ''
        );
    }

	function output_hidden_registration_fields()
	{
		$fields = array('api_key', 'registration_url', 'registration_email');
		foreach($fields as $field) {
			$val = isset( $this->options[$field] ) ? esc_attr( $this->options[$field]) : '';
			printf(
				'<input type="hidden" name="b_a_options[' . $field . ']" value="%s" />',
				$val
			);
		}
	}
	
	function output_clear_cookies_button()
	{
		$purl = admin_url('admin.php?page=before-and-after-settings') ;
		echo '<h3>Delete Your Conversion Cookies</h3>';
		echo '<p>Click this button to delete all of your own conversion cookies. After this, you will again see the "Before" text of all goals.</p>';
		echo '<form method="POST" action="' . $purl . '">';
		echo '<input type="hidden" name="b_a_clear_cookies" value="go" />';
		echo '<button class=button button-primary" type="submit">Delete My Conversion Cookies</button>';
		echo '</form>';
		
	}
	
	/* HubSpot Settings Field Callbacks */
	
    public function portal_id_callback()
    {
        printf(
            '<input type="text" id="portal_id" name="b_a_options[portal_id]" value="%s" style="width:450px" %s />',
            isset( $this->options['portal_id'] ) ? esc_attr( $this->options['portal_id']) : '',
			$this->root->is_pro() ? '' : 'disabled="true"'
        );
		echo '<p class="description">This is the Hub ID (previously called Portal ID) of the HubSpot account you will send submissions to.  Read More information on where to find your HUB ID <a href="http://help.hubspot.com/articles/KCS_Article/Account/Where-can-I-find-my-HUB-ID">here</a>.</p>';
    }
	
    public function form_guid_callback()
    {
        printf(
            '<input type="text" id="form_guid" name="b_a_options[form_guid]" value="%s" style="width:450px" %s />',
            isset( $this->options['form_guid'] ) ? esc_attr( $this->options['form_guid']) : '',
			$this->root->is_pro() ? '' : 'disabled="true"'
        );
		echo '<p class="description">This is the Form GUID of the HubSpot form you will send submissions to.  Read More information on where to find your Form GUID <a href="http://help.hubspot.com/articles/KCS_Article/Forms/How-do-I-find-the-form-GUID">here</a>.</p>';
    }
	
    public function send_to_hubspot_callback()
    {
        printf(
			'<input type="checkbox" name="b_a_options[send_to_hubspot]" id="send_to_hubspot" value="1" %s %s/>',
            isset( $this->options['send_to_hubspot'] ) ? 'checked="CHECKED"' : '',
			$this->root->is_pro() ? '' : 'disabled="true"'
        );
		echo '<p class="description">If checked, Form Submissions using Before &amp; After Pro will be sent to the configured HubSpot account.</p>';
    }
	
    public function hubspot_blacklist_callback()
    {
        printf(
			'<textarea name="b_a_options[hubspot_blacklist]" id="hubspot_blacklist" style="width:450px;height:200px;" %s>%s</textarea>',
            $this->root->is_pro() ? '' : 'disabled="true"',
			isset( $this->options['hubspot_blacklist'] ) ? $this->options['hubspot_blacklist'] : ''
        );
		echo '<p class="description">List of Form Titles to prevent sending to HubSpot.  If you have Form that you want to block from sending to HubSpot, but it is associated with a Goal, add the Title of the Form to this box.  Separate multiple titles with a comma.</p>';
    }	
	
	function output_mailing_list_form()
	{
		global $current_user;
?>
		<!-- Begin MailChimp Signup Form -->		
		<div id="signup_wrapper">
			<div class="topper">
				<h3>Save 20% on Before &amp; After Pro!</h3>
				<p class="pitch">Submit your name and email and we’ll send you a coupon for 20% off your upgrade to the Pro version.</p>
			</div>
			<div id="mc_embed_signup">
				<form action="http://illuminatikarate.us2.list-manage2.com/subscribe/post?u=403e206455845b3b4bd0c08dc&amp;id=934e059cff" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<label for="mce-NAME">Your Name:</label>
					<input type="text" value="<?php echo (!empty($current_user->display_name) ? $current_user->display_name : ''); ?>" name="NAME" class="name" id="mce-NAME" placeholder="Your Name">
					<label for="mce-EMAIL">Your Email:</label>
					<input type="email" value="<?php echo (!empty($current_user->user_email) ? $current_user->user_email : ''); ?>" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email address" required>
					<!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
					<div style="position: absolute; left: -5000px;"><input type="text" name="b_403e206455845b3b4bd0c08dc_6ad78db648" tabindex="-1" value=""></div>
					<div class="clear"><input type="submit" value="Send Me The Coupon Now" name="subscribe" id="mc-embedded-subscribe" class="smallBlueButton"></div>
						<p class="secure"><img src="<?php echo plugins_url( '../assets/img/lock.png', __FILE__ ); ?>" alt="Lock" width="16px" height="16px" />We respect your privacy.</p>
						<input type="hidden" id="mc-upgrade-plugin-name" value="Before &amp; After Pro" />
						<input type="hidden" id="mc-upgrade-link-per" value="http://goldplugins.com/purchase/before-after-pro/single?promo=newsub20" />
						<input type="hidden" id="mc-upgrade-link-biz" value="http://goldplugins.com/purchase/before-after-pro/business?promo=newsub20" />
						<input type="hidden" id="mc-upgrade-link-dev" value="http://goldplugins.com/purchase/before-after-pro/developer?promo=newsub20" />
						<input type="hidden" id="gold_plugins_already_subscribed" name="gold_plugins_already_subscribed" value="<?php echo get_user_setting ('_b_a_ml_has_subscribed', '0'); ?>" />
				</form>
				<div class="features">
					<strong>When you upgrade, you'll instantly gain access to:</strong>
					<ul>
						<li>Conversion Tracking</li>
						<li>Notification Emails</li>
						<li>HubSpot Integration</li>
						<li>Outstanding support</li>
						<li>Remove all banners from the admin area</li>
						<li>And more!</li>
					</ul>
					<a href="http://goldplugins.com/our-plugins/before-and-after/upgrade-to-before-and-after-pro/?utm_source=cpn_box&utm_campaign=upgrade&utm_banner=learn_more" title="Learn More">Learn More About Before &amp; After Pro &raquo;</a>
				</div>
			</div>
			<p class="u_to_p"><a href="http://goldplugins.com/our-plugins/before-and-after/upgrade-to-before-and-after-pro/?utm_source=plugin&utm_campaign=small_text_signup">Upgrade to Before &amp; After Pro now</a> to remove banners like this one.</p>
		</div>
		<!--End mc_embed_signup-->
<?php	
	}
	
	function output_admin_styles()
	{
		?>
		<style>
		</style>
		<?php
	}
}