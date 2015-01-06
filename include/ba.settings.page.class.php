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
        add_action( 'admin_init', array( $this, 'create_settings' ) );
        add_action( 'admin_init', array( $this, 'check_for_clear_cookies' ) );
		add_action('admin_head', array($this, 'output_admin_styles'));
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
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h2><?php echo htmlentities($this->plugin_title)?> Settings</h2>           			
			<form method="post" action="options.php">
				<?php
					// This prints out all hidden setting fields
					settings_fields( 'b_a_option_group' );
				?>
				<?php if (!	$this->root->is_pro()):?>
					<div class="ba_registration_settings register_plugin">
					<?php do_settings_sections( 'ba_registration_settings' ); ?>
					<?php submit_button(); ?>			
					</div>
				<? else: ?>
					<div class="register_plugin is_registered">
						<h3>Before & After Pro Activated</h3>
						<p><strong>This copy of Before & After Pro is registered to <a href="mailto:<?php echo $this->options['registration_email']; ?>"><?php echo htmlentities($this->options['registration_email']); ?></a>.</strong></p>
						<?php $this->output_hidden_registration_fields(); ?>
					</div>
				<?php endif; ?>
				<?php
					// Output notification settings
					do_settings_sections( 'ba_notifications_settings' );
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
            'Upgrade to Before & After Pro to Unlock Conversion Tracking and Notifications!', // Title
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
        echo '<p>Each time a goal is completed, we\'ll send a notification email.</p>';
		echo '<p><em>Tip: if you don\'t want notifications, just leave the Email field empty.</em></p>';
    }
	
    /** 
     * Print the Section text
     */
    public function print_registration_section_info()
    {
		echo '<p><em><a href="http://goldplugins.com/our-plugins/before-and-after/?utm_source=b_a_plugin&utm_campaign=upgrade&is_pro=0" target="_blank">Click here to purchase Before & After Pro.</a> You will receive your API keys by email as soon as you purchase.</a></em></p>';
		print '<strong>Enter your registration information below to enable Before & After Pro:</strong>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function email_callback()
    {
        printf(
            '<input type="text" id="email" name="b_a_options[email]" value="%s" style="width:450px" />',
            isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : ''
        );
		echo '<p class="description">This email will receive the notification emails. Seperate multiple addresses with a comma.</p>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function subject_callback()
    {
        printf(
            '<input type="text" id="subject" name="b_a_options[subject]" value="%s" style="width:450px" />',
            isset( $this->options['subject'] ) ? esc_attr( $this->options['subject']) : 'New Conversion: [goal_name]'
        );
		echo '<p class="description">We\'ll use this subject line for all of the notification emails.</p>';
    }

    public function email_body_callback()
    {
        printf(
            '<textarea id="email_body" name="b_a_options[email_body]" style="width:450px;height:200px;">%s</textarea>',
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
	
	function output_mailing_list_form()
	{
?>
		<!-- Begin MailChimp Signup Form -->
		<style type="text/css">
			/* MailChimp Form Embed Code - Slim - 08/17/2011 */
			#mc_embed_signup form {display:block; position:relative; text-align:left; padding:10px 0 10px 3%}
			#mc_embed_signup h2 {font-weight:bold; padding:0; margin:15px 0; font-size:1.4em;}
			#mc_embed_signup input {border:1px solid #999; -webkit-appearance:none;}
			#mc_embed_signup input[type=checkbox]{-webkit-appearance:checkbox;}
			#mc_embed_signup input[type=radio]{-webkit-appearance:radio;}
			#mc_embed_signup input:focus {border-color:#333;}
			#mc_embed_signup .button {clear:both; background-color: #aaa; border: 0 none; border-radius:4px; color: #FFFFFF; cursor: pointer; display: inline-block; font-size:15px; font-weight: bold; height: 32px; line-height: 32px; margin: 0 5px 10px 0; padding:0; text-align: center; text-decoration: none; vertical-align: top; white-space: nowrap; width: auto;}
			#mc_embed_signup .button:hover {background-color:#777;}
			#mc_embed_signup .small-meta {font-size: 11px;}
			#mc_embed_signup .nowrap {white-space:nowrap;}     
			#mc_embed_signup .clear {clear:none; display:inline;}

			#mc_embed_signup h3 { color: #008000; display:block; font-size:19px; padding-bottom:10px; font-weight:bold; margin: 0 0 10px;}
			#mc_embed_signup .explain {
				color: #808080;
				//width: 600px;
			}
			#mc_embed_signup label {
				color: #000000;
				display: block;
				font-size: 15px;
				font-weight: bold;
				padding-bottom: 10px;
			}
			#mc_embed_signup input.email {display:block; padding:8px 0; margin:0 4% 10px 0; text-indent:5px; width:58%; min-width:130px;}

			#mc_embed_signup div#mce-responses {float:left; top:-1.4em; padding:0em .5em 0em .5em; overflow:hidden; width:90%;margin: 0 5%; clear: both;}
			#mc_embed_signup div.response {margin:1em 0; padding:1em .5em .5em 0; font-weight:bold; float:left; top:-1.5em; z-index:1; width:80%;}
			#mc_embed_signup #mce-error-response {display:none;}
			#mc_embed_signup #mce-success-response {color:#529214; display:none;}
			#mc_embed_signup label.error {display:block; float:none; width:auto; margin-left:1.05em; text-align:left; padding:.5em 0;}		
			#mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }
				#mc_embed_signup{    
						background-color: white;
						border: 1px solid #DCDCDC;
						clear: left;
						color: #008000;
						font: 14px Helvetica,Arial,sans-serif;
						margin-top: 10px;
						margin-bottom: 0px;
						max-width: 800px;
						padding: 5px 12px 0px;
			}
			#mc_embed_signup form{padding: 10px}

			#mc_embed_signup .special-offer {
				color: #808080;
				margin: 0;
				padding: 0 0 3px;
				text-transform: uppercase;
			}
			#mc_embed_signup .button {
			  background: #5dd934;
			  background-image: -webkit-linear-gradient(top, #5dd934, #549e18);
			  background-image: -moz-linear-gradient(top, #5dd934, #549e18);
			  background-image: -ms-linear-gradient(top, #5dd934, #549e18);
			  background-image: -o-linear-gradient(top, #5dd934, #549e18);
			  background-image: linear-gradient(to bottom, #5dd934, #549e18);
			  -webkit-border-radius: 5;
			  -moz-border-radius: 5;
			  border-radius: 5px;
			  font-family: Arial;
			  color: #ffffff;
			  font-size: 20px;
			  padding: 10px 20px 10px 20px;
			  line-height: 1.5;
			  height: auto;
			  margin-top: 7px;
			  text-decoration: none;
			}

			#mc_embed_signup .button:hover {
			  background: #65e831;
			  background-image: -webkit-linear-gradient(top, #65e831, #5dd934);
			  background-image: -moz-linear-gradient(top, #65e831, #5dd934);
			  background-image: -ms-linear-gradient(top, #65e831, #5dd934);
			  background-image: -o-linear-gradient(top, #65e831, #5dd934);
			  background-image: linear-gradient(to bottom, #65e831, #5dd934);
			  text-decoration: none;
			}
			#signup_wrapper {
				max-width: 800px;
				margin-bottom: 20px;
				margin-top: 30px;
			}
			#signup_wrapper .u_to_p
			{
				font-size: 10px;
				margin: 0;
				padding: 2px 0 0 3px;				
			}
			#signup_wrapper {
				background-color: #ccc;
				bottom: 0;
				padding-left: 20px;
				padding-right: 20px;
				padding-top: 40px;
				position: fixed;
				right: 0;
				top: 0;
				width: 400px;
			}

			#signup_wrapper .customer_testimonial {
				border-top: 1px solid #ccc;
				color: gray;
				font-size: 17px;
				font-style: italic;
				padding-top: 20px;	
			}

			#signup_wrapper .customer_testimonial .author {
				display: block;
				font-size: 14px;
				font-style: normal;
				margin-right: 18px;
				margin-top: 13px;
				text-align: right;
			}
		</style>
		<div id="signup_wrapper">
			<div id="mc_embed_signup">
				<form action="http://illuminatikarate.us2.list-manage2.com/subscribe/post?u=403e206455845b3b4bd0c08dc&amp;id=934e059cff" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<p class="special-offer">Special Offer:</p>
					<h3>Sign-up for our newsletter now, and we'll give you a discount on Before & After Pro!</h3>
					<label for="mce-EMAIL">Your Email:</label>
					<input type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email address" required>
					<!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
					<div style="position: absolute; left: -5000px;"><input type="text" name="b_403e206455845b3b4bd0c08dc_934e059cff" tabindex="-1" value=""></div>
					<div class="clear"><input type="submit" value="Subscribe Now" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
					<p class="explain"><strong>What To Expect:</strong> You'll receive you around one email from us each month, jam-packed with special offers and tips for getting the most out of WordPress. Of course, you can unsubscribe at any time.</p>
				</form>
			</div>
			<p class="u_to_p"><a href="http://goldplugins.com/our-plugins/before-and-after/?utm_source=plugin&utm_campaign=upgrade_small">Upgrade to Before & After Pro now</a> to remove banners like this one.</p>
		</div>
		<!--End mc_embed_signup-->
<?php	
	}
	
	function output_admin_styles()
	{
		?>
		<style>
			.register_plugin {
				border: 1px solid green;
				background-color: lightyellow;
				padding: 25px;
				width: 750px;
				margin-top: 10px;
			}
			.register_plugin.is_registered {
				background-color: #EEFFF7;
				padding: 10px 16px 0;
			}
			.register_plugin h3 {
				padding-top: 0;
				margin-top: 0;
			}
			.register_plugin .field {
				padding-bottom: 10px;
			}
			.register_plugin .submit {
				padding-top: 10px;
				margin: 0;
			}
			.register_plugin label {
				display: block;
			}
			.register_plugin input[type="text"] {
				width: 350px;
			}
			/* Add/Edit page */
			.b_a_options input[type="radio"] {
				float: left;
				margin: 3px 5px 0 0;
			}
			.b_a_options .secondary-option {
				padding: 10px 0 10px 20px;
			}			
		</style>
		<?php
	}
}