<?php
class BA_Conversion_Model
{
    /**
     * Holds the values to be used in the fields callbacks
     */
	const post_type = 'b_a_conversion';
	private $root;
	private $plugin_title;

    /**
     * Start up
     */
    public function __construct($root)
    {
		$this->root = $root;
		$this->plugin_title = $root->plugin_title;
		if ($this->root->is_pro()) {
			$this->setup_custom_post_type();
			add_action( 'admin_init', array( $this, 'admin_init' ) );		
			add_filter( 'pre_get_posts', array( $this, 'meta_filter_posts' ) );
		}
    }
	
	public function admin_init()
	{
		add_filter('manage_edit-conversion_columns', array( $this, 'add_new_columns' ));
		add_filter('manage_edit-conversions_columns', array( $this, 'add_new_columns' ));
		add_action('manage_conversion_posts_custom_column', array( $this, 'manage_conversion_columns' ), 10, 2);
	}
	
	function meta_filter_posts( $query )
	{
		if( is_admin() && $query->is_main_query() && $query->query_vars['post_type'] == 'b_a_conversion' && isset( $_GET['goal_id'] ) ) {
			// $query is the WP_Query object, set is simply a method of the WP_Query class that sets a query var parameter
			$query->set( 'meta_key', 'goal_id' );
			$query->set( 'meta_value', intval($_GET['goal_id']) );
		}
		
		// order by newest first
		if( is_admin() && $query->is_main_query() && $query->query_vars['post_type'] == 'b_a_conversion' && !isset( $_GET['order'] ) && !isset( $_GET['orderby'] ) ) {
			$query->set( 'order', 'DESC' );
			$query->set( 'orderby', 'date' );
		}
		return $query;
	}	
	
	public function logConversion($goalId, $goal_complete_url = '')
	{
		global $post;
		
		if ( !$this->root->is_pro() ) {
			return false;
		}
		
		// 1: create an array of the visitors details, such as:
		//   - time and date
		//   - IP
		//   - (TBD) a link to the contact from submission
		//   - (TBD) details from e.g.. contact forms like name and email. 
		//   - (TBD) referring page
		$geolocation = $this->geolocate_current_visitor();
		if ($geolocation !== false)
		{
			$conversion_title = 'Unknown Visitor - ' . $geolocation['friendly_location'] . ' (' . $_SERVER["REMOTE_ADDR"] . ')';
		}
		else {
			$conversion_title = 'Unknown Visitor (' . $_SERVER["REMOTE_ADDR"] . ')';
		}
		
		$start_time = ( isset($_SESSION['goal_'.$goalId.'_start_time']) ? $_SESSION['goal_'.$goalId.'_start_time']  : '' );
		$completion_time = microtime(true);
		$time_to_complete = '';
		if($start_time) {
			$time_to_complete = floatval($completion_time) - floatval($start_time);
		}
		
		$goal_start_url = isset($_SESSION['goal_'.$goalId.'_encounter_url']) ? $_SESSION['goal_'.$goalId.'_encounter_url']  : '';
		
		if ( empty($goal_complete_url) && isset($post->ID) ) {
			$goal_complete_url = get_permalink($post->ID);
		}
		
		$visitor_details = array(
								'goal_id' => $goalId,
								'timestamp'  => date('U'),
								'friendly_date'  => date('l, F jS Y h:i:s A'),
								'friendly_location'  => ( $geolocation !== false ? $geolocation['friendly_location'] : 'unknown' ),
								'country_name'  => ( $geolocation !== false ? $geolocation['country_name'] : '' ),
								'country_code'  => ( $geolocation !== false ? $geolocation['country_code'] : '' ),
								'geolocation'  => ( $geolocation !== false ? $geolocation : '' ),
								'goal_start_url'  => $goal_start_url ,
								'goal_complete_url'  => $goal_complete_url,
								'goal_start_time'  => $start_time,
								'goal_complete_time'  => $completion_time,
								'goal_time_to_complete'  => $time_to_complete,
								'ip_address' => $_SERVER["REMOTE_ADDR"],
								'link_to_submission' => '',
								'extra_fields' => '',								
		);		
		//	
		// 2: save a new record of the "conversion" custom post type
		$new_conversion = array(
						  'post_title'    	=> $conversion_title,
						  'post_content'  	=> '[conversion_data]',
						  'post_type'   	=> 'b_a_conversion',
						  'post_status'   	=> 'pending',
						  'post_status'   	=> 'publish',
		);
		// TBD: use global $post to get current page URL and save it!

		// store conversion to database
		$conversion_id = wp_insert_post( $new_conversion );
		
		// send a notification email
		$notification_sent = $this->root->Notifications->send($goalId, $visitor_details);
		
		// 3: store the details as custom meta fields. some will be named, some/all will go into some kind of heap/blob if not otherwise saved
		if ($conversion_id)
		{
			// save the meta fields (if they contain a value, but skip the empty ones)
			foreach($visitor_details as $meta_key => $value) 
			{
				if ($value && $value != '') {
					add_post_meta($conversion_id, $meta_key, $value, true);
				}
			}
			
			// generate and save their random download key
			$download_key = substr(md5(rand()), 0, 10) . substr(md5($conversion_id), 0, 10);
			$meta_key = 'b_a_dk_' . $download_key;
			add_post_meta($conversion_id, '_b_a_download_key', $download_key, true);
		}
		else {
			return false; // save failed!?!
		}		
		return $conversion_id;
	}
	
	function geolocate_current_visitor()
	{
		$geolocator_url = 'http://freegeoip.net/json/' . $_SERVER["REMOTE_ADDR"];
		$url_contents = wp_remote_get( $geolocator_url );
		if (! is_wp_error( $url_contents ) && is_array( $url_contents ) && isset($url_contents['body']) && strlen($url_contents['body']) > 0)
		{
			$response_body = $url_contents['body'];
			$geo_json = json_decode($response_body);
			$geo = array('ip' => $geo_json->ip,
						 'country_code' => $geo_json->country_code,
						 'country_name' => $geo_json->country_name,
						 'region_name' => $geo_json->region_name,
						 'state' => $geo_json->region_name,
						 'city' => $geo_json->city,
						 'zipcode' => isset($geo_json->zipcode) ? $geo_json->zipcode : '',
						 'latitude' => $geo_json->latitude,
						 'longitude' => $geo_json->longitude,
						 'friendly_location' => $geo_json->country_name,
						);

						// if US, replace country name with city and state
			if ($geo['country_code'] == 'US') { 
				$geo['friendly_location'] = $geo_json->city . ', ' . $geo_json->region_name . ', USA';
			}
			return $geo;
		}
		else {
			return false;
		}
	}
	
	

	private function setup_custom_post_type()
	{
		// create the Goal custom post type
		$postType = array('name' => 'Conversion', 'plural' => 'Conversions', 'slug' => 'b_a_conversions');
		$custom_args = array(
			'show_in_menu' => 'edit.php?post_type=b_a_goal'
		);
		$this->root->custom_post_types[] = new B_A_CustomPostType($postType, array(), false, $custom_args);

		add_action('init', array($this, 'remove_unneeded_metaboxes')); // remove some default meta boxes
		
		
		
		
/* 		// setup the meta boxes on the Add/Edit Goal screen
		add_action( 'admin_menu', array( $this, 'add_meta_boxes' ) ); // add our custom meta boxes

		// add a hook to save the new values of our Goal settings whenever the Goal is saved
		add_action( 'save_post', array( $this, 'save_goal_settings' ), 1, 2 );

		// add a special link to the Row Actions menu of each Goal, which displays the visitors who have completed the goal
		add_filter('page_row_actions', array( $this, 'add_page_row_actions' ), 10, 2);
		add_filter('post_row_actions', array( $this, 'add_page_row_actions' ), 10, 2);
 */

	}

	function secondsToTime($seconds) 
	{
		$dtF = new DateTime("@0");
		$dtT = new DateTime("@$seconds");
		$time_str = $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
		$zeros = array('0 days and', '0 days ', ', 0 hours', ', 0 minutes', ', 0 seconds', ' 0 minutes and ');
		return str_replace($zeros, '', $time_str);
	}
 
	function manage_conversion_columns($column_name, $id) {
		global $wpdb;
		switch ($column_name) {
		case 'id':
			echo $id;
				break;
	 
		case 'time_to_complete':
			$time_to_complete = get_post_meta($id, 'goal_time_to_complete', true);
			if ($time_to_complete) {
				$seconds = round($time_to_complete, 0);
				echo $this->secondsToTime($seconds);
			}
			break;		
			
		case 'visitor_location':
			$img_div = '';
			$visitor_country_code = get_post_meta($id, 'country_code', true);
			$visitor_country_name = get_post_meta($id, 'friendly_location', true);
			if ($visitor_country_code && $visitor_country_name) {
				$img_div = '<div class="flag flag-' . strtolower($visitor_country_code) . '">' . $visitor_country_code . '</div> &nbsp; ' . $visitor_country_name;
			}
			echo $img_div;
			break;	 
			
		case 'goal_name':
			$goal_id = intval(get_post_meta($id, 'goal_id', true));
			if ($goal_id > 0) {
				$my_goal = get_post($goal_id);
				$goal_title = apply_filters('the_title', $my_goal->post_title);
				$my_admin_url = admin_url( 'post.php?post=' . $goal_id . '&action=edit');
				$my_link = '<a class="row-title" href="' . $my_admin_url .'">' . htmlentities($goal_title) . '</a>';
				echo $my_link;
			} else {
				echo "";				
			}
			break;
			
		default:
			break;
		} // end switch
	}
	
	function add_new_columns($gallery_columns) {
		$gc = $this->array_put_to_position($gallery_columns, __('Time To Complete Goal'), 2, 'time_to_complete');
		$gc = $this->array_put_to_position($gallery_columns, __('Visitor Location'), 2, 'visitor_location');
		$gc = $this->array_put_to_position($gallery_columns, __('Goal Name'), 2, 'goal_name');
		return $gc;
	}
	
	function array_put_to_position(&$array, $object, $position, $name = null)
	{
			$count = 0;
			$return = array();
			foreach ($array as $k => $v)
			{  
					// insert new object
					if ($count == $position)
					{  
							if (!$name) $name = $count;
							$return[$name] = $object;
							$inserted = true;
					}  
					// insert old object
					$return[$k] = $v;
					$count++;
			}  
			if (!$name) $name = $count;
			if (!$inserted) $return[$name];
			$array = $return;
			return $array;
	}	
	// adds a special link to the Row Actions menu, to display the visitors who have completed each goal
	function add_page_row_actions($actions, $page_object)
	{
		if ($page_object->post_type =="b_a_goal")
		{		
			$link = '/wp-admin/admin.php?page=before-and-after-completed_goals&goal_id=' . $page_object->ID;
			$actions['b_a_stats'] = '<a href="' . $link . '" class="completed_goals_link">' . __('Visitors Who Completed This Goal') . '</a>';	 
		}
		return $actions;
	}
	
	// saves the per-Goal settings. called whenever the Goal is saved
	function save_goal_settings()
	{
		global $post;
		
		// make sure  that the nonce matches and the user has permission to edit this goal
		if (!wp_verify_nonce( $_POST[ 'b_a_goal_settings_nonce' ], 'b_a_goal_settings' ) ||
			!current_user_can( 'edit_post', $post_id ) || 
			$post->post_type != self::post_type)
		{
			return;
		}
		
		$this->update_goal_setting_from_post($post->ID, 'before-action', '_goal_before_action');
		$this->update_goal_setting_from_post($post->ID, 'after-action', '_goal_after_action');
		$this->update_goal_setting_from_post($post->ID, 'before-values', '_goal_before_values');
		$this->update_goal_setting_from_post($post->ID, 'after-values', '_goal_after_values');
		
	}
	
	
	
	// remove unneeded meta boxes from the Goal custom post type
	function remove_unneeded_metaboxes()
	{
		remove_post_type_support( self::post_type, 'editor' ); // note: may remove this later and replace with a custom field
		remove_post_type_support( self::post_type, 'excerpt' );
		remove_post_type_support( self::post_type, 'comments' );
		remove_post_type_support( self::post_type, 'author' );		
	}

	// add our custom meta boxes to capture per-Goal settings
	function add_meta_boxes()
	{
		add_meta_box( 'goal_before', 'Before', array( $this, 'display_before_meta_box' ), self::post_type, 'normal', 'high' );
		add_meta_box( 'goal_after', 'After', array( $this, 'display_after_meta_box' ), self::post_type, 'normal', 'high' );
	}
	
	// outputs some CSS styles needed by our admin pages
	function output_admin_styles()
	{
	?>
		<style>		
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
	

	// creates the "Before" meta box
	function display_before_meta_box()
	{
		global $post;
		$this->output_admin_styles();
		?>
		<div class="form-wrap">
			<?php wp_nonce_field( 'b_a_goal_settings', 'b_a_goal_settings_nonce', false, true ); ?>
			<p>BEFORE the user has completed this goal, what should happen?</p>
			<ul class="b_a_options">
				<li>
					<input type="radio" name="before-action" id="before-redirect-page" value="redirect_page" <?php echo $this->is_radio_checked($post->ID, 'before-action', 'redirect_page')?> />
					<label for="before-redirect-page">Redirect to this page:</label>
					<div class="secondary-option form-field">
						<?php
							$currentPageId = $this->get_goal_setting_value($post->ID, 'before-values', 'redirect_page');
							$args = array(  'name' => 'before-values[redirect_page]',
											'selected' => $currentPageId,
									);
							wp_dropdown_pages($args);
						?>
					</div>
				</li>
				<li>
					<input type="radio" name="before-action" id="before-redirect-url" value="redirect_url" <?php echo $this->is_radio_checked($post->ID, 'before-action', 'redirect_url')?> />
					<label for="before-redirect-url">Redirect to this URL:</label>
					<div class="secondary-option form-field">
						<input type="text" name="before-values[redirect_url]" value="<?php echo $this->get_goal_setting_value($post->ID, 'before-values', 'redirect_url')?>"/>
					</div>
				</li>
				<?php
					if(defined('WPCF7_VERSION')):
						$cf7_forms = get_posts(array('post_type' => 'wpcf7_contact_form'));
						if (is_array($cf7_forms) && count($cf7_forms) > 0): 							
				?>
				<li>
					<input type="radio" name="before-action" id="before-cf7-form" value="contact_form_7" <?php echo $this->is_radio_checked($post->ID, 'before-action', 'contact_form_7')?> />
					<label for="before-cf7-form">Show a Contact Form 7 form:</label>
					<div class="secondary-option form-field">
						<select name="before-values[contact_form_7]">
							<?php foreach($cf7_forms as $cf7_form): ?>
							<?php echo $this->display_option($post->ID, 'before-values', 'contact_form_7', $cf7_form->post_title, $cf7_form->ID); ?>
							<?php endforeach; ?>
						</select>
					</div>
				</li>
					<?php endif; // end "if has any cf7 forms" ?>
				<?php endif; // end "is_defined(WPCF7_VERSION)" ?>
				<?php
					if(class_exists('RGFormsModel')):
						$gravity_forms = RGFormsModel::get_forms( null, 'title' );
						if (is_array($gravity_forms) && count($gravity_forms) > 0): 							
				?>
				<li>
					<input type="radio" name="before-action" id="before-gravity-form" value="gravity_form" <?php echo $this->is_radio_checked($post->ID, 'before-action', 'gravity_form')?> />
					<label for="before-gravity-form">Show a Gravity Form:</label>
					<div class="secondary-option form-field">
						<select name="before-values[gravity_form]">
							<?php foreach($gravity_forms as $gravity_form): ?>
							<?php echo $this->display_option($post->ID, 'before-values', 'gravity_form', $gravity_form->title, $gravity_form->id); ?>
							<?php endforeach; ?>
						</select>
					</div>
				</li>
					<?php endif; // end "if has any gravity forms" ?>
				<?php endif; // end "if RGFormsModel exists" ?>
				<li>
					<input type="radio" name="before-action" id="before-text" value="free_text" <?php echo $this->is_radio_checked($post->ID, 'before-action', 'free_text')?> />
					<label for="before-text">Show the following text:</label>
					<div class="secondary-option form-field">
						<textarea name="before-values[free_text]" rows="5"><?php echo $this->get_goal_setting_value($post->ID, 'before-values', 'free_text')?></textarea>
					</div>
				</li>
			</ul>
		</div>
		<?php
	}

	// creates the "After" meta box
	function display_after_meta_box()
	{
		global $post;
		$this->output_admin_styles();
		?>
		<div class="form-wrap">
			<p>AFTER the user has completed this goal, what should happen?</p>
			<ul class="b_a_options">
				<li>
					<input type="radio" name="after-action" id="after-redirect-page" value="redirect_page" <?php echo $this->is_radio_checked($post->ID, 'after-action', 'redirect_page')?> />
					<label for="after-redirect-page">Redirect to this page:</label>
					<div class="secondary-option form-field">
						<?php
							$currentPageId = $this->get_goal_setting_value($post->ID, 'after-values', 'redirect_page');
							$args = array(  'name' => 'after-values[redirect_page]',
											'selected' => $currentPageId,
									);
							wp_dropdown_pages($args);
						?>
					</div>
				</li>
				<li>
					<input type="radio" name="after-action" id="after-redirect-url" value="redirect_url" <?php echo $this->is_radio_checked($post->ID, 'after-action', 'redirect_url')?> />
					<label for="after-redirect-url">Redirect to this URL:</label>
					<div class="secondary-option form-field">
						<input type="text" name="after-values[redirect_url]" value="<?php echo $this->get_goal_setting_value($post->ID, 'after-values', 'redirect_url')?>"/>
					</div>
				</li>
				<li>
					<input type="radio" name="after-action" id="after-file-url"  value="file_url" <?php echo $this->is_radio_checked($post->ID, 'after-action', 'file_url')?>/>
					<label for="after-file-url">Link to a file to download:</label>
					<div class="secondary-option form-field">
						<input type="text" name="after-values[file_url]" value="<?php echo $this->get_goal_setting_value($post->ID, 'after-values', 'file_url')?>" />
					</div>
				</li>
				<li>
					<input type="radio" name="after-action" id="after-text" value="free_text" <?php echo $this->is_radio_checked($post->ID, 'after-action', 'free_text')?> />
					<label for="after-text">Show the following text:</label>
					<div class="secondary-option form-field">
						<textarea name="after-values[free_text]" rows="5"><?php echo $this->get_goal_setting_value($post->ID, 'after-values', 'free_text')?></textarea>
					</div>
				</li>
			</ul>
		</div>
		<?php
			
	}
	
	
	
	
	/* Returns true/false, indicated whether the specified goal has been completed (based on the users SESSION)*/
	public function wasGoalCompleted($goalName)
	{
		$sessionKey = 'goal_' . md5($goalName);
		$sessionValue = 'goal_completed_' . md5($goalName);
		return (isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] == $sessionValue);
	}
	
	/* Place a session variable that marks the current visitor as having completed the specified goal */
	public function completeGoal($goalName, $log = true)
	{
		$alreadyCompleted = $this->wasGoalCompleted($goalName);
		if (!$alreadyCompleted)
		{
			$sessionKey = 'goal_' . md5($goalName);
			$sessionValue = 'goal_completed_' . md5($goalName);
			$_SESSION[$sessionKey] = $sessionValue;
			
			if ($log) {
				//TBD: this requires an ID, not a name, so we'll need to start converting from names to IDs in order to make this work
				//$this->logGoalCompletion($goalName);
			}
		}
		return '';
	}

	/* Place a session variable that marks the current visitor as having completed the specified goal */
	function completeGoalById($goalId, $log = true)
	{
		$goalName = 'Goal_ID_' . $goalId;
		$alreadyCompleted = $this->wasGoalCompleted($goalName);
		if (!$alreadyCompleted)
		{
			$sessionKey = 'goal_' . md5($goalName);
			$sessionValue = 'goal_completed_' . md5($goalName);
			$_SESSION[$sessionKey] = $sessionValue;
			
			if ($log) {
				$this->logGoalCompletion($goalId);
			}
		}
		return '';
	}
	
	// records a record of the current visitor completing the specified goal
	// logs a set of default information automatically. extra key/value pairs can also be saved by passing data via the $extraFields parameter
	public function logGoalCompletion($goalId, $extraFields = array())
	{
		$this->incrementGoalCounter($goalId);
		$details = array(
					'timestamp' => date('U'),
					'IP'		=> $_SERVER["REMOTE_ADDR"],
				);
		$details = array_merge($details, $extraFields); // note: $extraFields "wins" over $details, if they contain matching keys
		add_post_meta($goalId, '_goal_completions', $details, false);
	}
	
	// increments the internal counter for the number of times the specified goal has been completed
	// note: does not perform any duplicate checking
	private function incrementGoalCounter($goalId)
	{
		// Get the current counter value.  Start it at 0 if its not been set
		$completionCounterOldValue = intval(get_post_meta($goalId, '_completion_counter', true));
		
		// add one to the current value
		$completionCounterNewValue = $completionCounterOldValue + 1;
		
		// Save the new value. 
		// Note: its important to specify the previous value when calling update_post_meta, 
		// so that we can't have a race condition when another visitor completes the goal at the same time
		update_post_meta($goalId, '_completion_counter', $completionCounterNewValue, $completionCounterOldValue);	
	}
	
	// saves the value of a POST variable to the database
	private function update_goal_setting_from_post($post_id, $request_key, $meta_key)
	{
		if (isset($_POST[$request_key]))
		{
			$val = $_POST[$request_key]; // TBD: sanitize POSTed value!
			if ($val != '') {
				return update_post_meta($post_id, $meta_key, $val);
			}			
		}
		return false;
	}
	

	// returns the "checked" attribute for a radio button, depending on whether the setting specified matches the test value specified
	// returns either the string 'checked="checked"', or an empty string ''. These are intended to be used inside an <input type="radio" /> HTML tag
	private function is_radio_checked($goal_id, $setting_name, $setting_value)
	{
		if ($setting_name == 'before-action') {
			$val = get_post_meta($goal_id, '_goal_before_action', true);
		}
		else if ($setting_name == 'after-action') {
			$val = get_post_meta($goal_id, '_goal_after_action', true);		
		}
		if ($val == $setting_value) {
			return 'checked="checked"';			
		} else {
			return '';
		}
	}
	
	// returns a formatted HTML <option> tag with the specified settings
	private function display_option($goal_id, $setting_location, $setting_key, $option_text, $option_value = '')
	{
		$val = $this->get_goal_setting_value($goal_id, $setting_location, $setting_key);
		if($option_value == '') {
			$option_value = $option_text;
		}
		$selected = false;
		if($option_value == $val) {
			$selected = true;
		}
		$html = '<option value="' . htmlspecialchars($option_value) . '"';
		if ($selected) {
			$html .= ' selected="selected"';
		}
		$html .= '>' . htmlspecialchars($option_text) . '</option>';
		return $html;
	}

	private function get_goal_setting_value($goal_id, $setting_location, $setting_key, $default_value = '')
	{
		if ($setting_location == 'before-values') {
			$vals = get_post_meta($goal_id, '_goal_before_values', true);
		}
		else if ($setting_location == 'after-values') {
			$vals = get_post_meta($goal_id, '_goal_after_values', true);		
		}
		if($vals && isset($vals[$setting_key])) {
			return $vals[$setting_key];
		} else {
			return $default_value;
		}		
	}
	
	
	function show_upgrade_message()
	{
		$upgrade_url = 'http://goldplugins.com/our-plugins/before-and-after-pro/?utm_source=b_a_plugin&utm_campaign=upgrade&utm_banner=conversion_tracking&is_pro=0';
		echo "<h3>Upgrade To Before & After Pro and Unlock Conversion Tracking</h3>";
		echo "<strong><a target=\"_blank\" href=\"{$upgrade_url}\">Before & After Pro</a></strong> adds Conversion Tracking and other advanced features. <a target=\"_blank\" href=\"{$upgrade_url}\">Upgrade now</a>, and you'll be tracking your goal conversions in 10 minutes.";
		echo '<a target="_blank" href="' . $upgrade_url . '"><img src="' . plugins_url('../assets/img/upgrade-conversions.png' , __FILE__) . '" alt="Upgrade to Before & After Pro and Unlock Comversion Tracking" style="max-width:100%;height:auto;margin-top:30px;border: 1px solid lightgray;" /></a>';
	}
			
	
	

}