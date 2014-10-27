<?php
class BA_Goal_Model
{
    /**
     * Holds the values to be used in the fields callbacks
     */
	const post_type = 'b_a_goal';
	private $root;
	private $plugin_title;

    /**
     * Start up
     */
    public function __construct($root)
    {
		$this->root = $root;
		$this->plugin_title = $root->plugin_title;
		$this->setup_custom_post_type();
		add_action( 'admin_init', array( $this, 'admin_init' ) );		
    }
	
	function admin_init()
	{
		add_filter('manage_edit-goal_columns', array( $this, 'add_new_columns' ));
		add_filter('manage_edit-goals_columns', array( $this, 'add_new_columns' ));
		add_action('manage_goal_posts_custom_column', array( $this, 'manage_goal_columns' ), 10, 2);
	}
	
	function manage_goal_columns($column_name, $id) {
		global $wpdb;
		switch ($column_name) {
		case 'id':
			echo $id;
				break;
			
		case 'goal_shortcode':
/* 			$goal_id = intval(get_post_meta($id, 'goal_id', true));
			if ($goal_id > 0) {
				$my_goal = get_post($goal_id);
				$goal_title = apply_filters('the_title', $my_goal->post_title);
				$my_admin_url = admin_url( 'post.php?post=' . $goal_id . '&action=edit');
				$my_link = '<a class="row-title" href="' . $my_admin_url .'">' . htmlentities($goal_title) . '</a>';
				echo $my_link;
			} else {
				echo "";				
			}
 */
			echo '[goal id="' . $id . '"]';
			break;

			case 'complete_goal_shortcode':
			echo '[complete_goal id="' . $id . '"]';
			break;
			
		default:
			break;
		} // end switch
	}
	
	function add_new_columns($gallery_columns) {
		$gc = $this->array_put_to_position($gallery_columns, __('Complete Goal Shortcode'), 2, 'complete_goal_shortcode');
		$gc = $this->array_put_to_position($gallery_columns, __('Goal Shortcode'), 2, 'goal_shortcode');
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
	
	private function setup_custom_post_type()
	{
		// create the Goal custom post type
		$postType = array('name' => 'Goal', 'plural' => 'Goals', 'slug' => 'b_a_goals');
		$fields = array();
/* 		$fields[] = array('name' => 'before_goal_content', 'title' => 'Before', 'description' => 'Show this text to the user BEFORE they have completed this goal.', 'type' => 'textarea');	
		$fields[] = array('name' => 'after_goal_content', 'title' => 'After', 'description' => 'Show this text to the user AFTER they have completed this goal.', 'type' => 'textarea');	 */
		$this->root->custom_post_types[] = new B_A_CustomPostType($postType, $fields, true);			

		// setup the meta boxes on the Add/Edit Goal screen
		add_action('init', array($this, 'remove_unneeded_metaboxes')); // remove some default meta boxes
		add_action( 'admin_menu', array( $this, 'add_meta_boxes' ) ); // add our custom meta boxes

		// add a hook to save the new values of our Goal settings whenever the Goal is saved
		add_action( 'save_post', array( $this, 'save_goal_settings' ), 1, 2 );

		// add a special link to the Row Actions menu of each Goal, which displays the visitors who have completed the goal
		add_filter('page_row_actions', array( $this, 'add_page_row_actions' ), 10, 2);
		add_filter('post_row_actions', array( $this, 'add_page_row_actions' ), 10, 2);

	}

	// adds a special link to the Row Actions menu, to display the visitors who have completed each goal
	function add_page_row_actions($actions, $page_object)
	{
		if ($page_object->post_type == self::post_type)
		{
			// if pro, add the View Conversions link
			if ($this->root->is_pro())
			{
				$conversions_url = admin_url('edit.php?post_type=b_a_conversion&goal_id=' . $page_object->ID);
				$actions['b_a_stats'] = '<a href="' . $conversions_url . '" class="completed_goals_link">' . __('View Conversions') . '</a>';
			}
			unset($actions['view']); // remove View link
			unset($actions['inline hide-if-no-js']); // remove Quick Edit link
		}
		return $actions;
	}
	
	// saves the per-Goal settings. called whenever the Goal is saved
	function save_goal_settings()
	{
		global $post;
		
		// make sure  that the nonce matches and the user has permission to edit this goal
		if (!isset($_POST[ 'b_a_goal_settings_nonce' ]) || !wp_verify_nonce( $_POST[ 'b_a_goal_settings_nonce' ], 'b_a_goal_settings' ) ||
			!current_user_can( 'edit_post', $post_id ) || 
			$post->post_type != self::post_type)
		{
			return;
		}
		
		$this->update_goal_setting_from_post($post->ID, 'before-action', '_goal_before_action');
		$this->update_goal_setting_from_post($post->ID, 'after-action', '_goal_after_action');
		$this->update_goal_setting_from_post($post->ID, 'before-values', '_goal_before_values');
		$this->update_goal_setting_from_post($post->ID, 'after-values', '_goal_after_values');
		
		// special: if the before-action is a contact form 7 or a gravity form, we need to save an extra meta value now so that we can query it later
		if ( $_POST[ 'before-action' ] == 'contact_form_7' && isset($_POST['before-values']['contact_form_7']) ) {
			$form_id = intval($_POST['before-values']['contact_form_7']);
			$goal_selector = 'cf7_' . $form_id;
		} else if ( $_POST[ 'before-action' ] == 'gravity_form' && isset($_POST['before-values']['gravity_form']) ) {
			$form_id = intval($_POST['before-values']['gravity_form']);
			$goal_selector = 'gform_' . $form_id;
		} else {
			$goal_selector = 'none';
		}
		update_post_meta($post->ID, '_goal_selector', $goal_selector);
	}
	
	
	
	// remove unneeded meta boxes from the Goal custom post type
	function remove_unneeded_metaboxes()
	{
		remove_post_type_support( self::post_type, 'editor' ); // note: may remove this later and replace with a custom field
		remove_post_type_support( self::post_type, 'excerpt' );
		remove_post_type_support( self::post_type, 'comments' );
		remove_post_type_support( self::post_type, 'author' );		
		remove_post_type_support( self::post_type, 'page-attributes' );		
		remove_post_type_support( self::post_type, 'thumbnail' );		
	}

	// add our custom meta boxes to capture per-Goal settings
	function add_meta_boxes()
	{
		add_meta_box( 'goal_before', 'Before', array( $this, 'display_before_meta_box' ), self::post_type, 'normal', 'high' );
		add_meta_box( 'goal_after', 'After', array( $this, 'display_after_meta_box' ), self::post_type, 'normal', 'high' );
		add_meta_box( 'goal_shortcodes', 'Shortcodes', array( $this, 'display_shortcodes_meta_box' ), self::post_type, 'side', 'default' );
	}
	
	// creates the "Before" meta box
	function display_before_meta_box()
	{
		global $post;
		?>
		<div class="form-wrap">
			<?php wp_nonce_field( 'b_a_goal_settings', 'b_a_goal_settings_nonce', false, true ); ?>
			<p>BEFORE the user has completed this goal, what should happen?</p>
			<ul class="b_a_options">
				<?php if (false): ?>
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
				<?php endif; ?>
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
	
	/* Displays a meta box with the shortcodes to display and complete the current goal */
	function display_shortcodes_meta_box()
	{
		global $post;
		echo "Add this shortcode to any page where you'd like to <strong>display</strong> this goal:<br />";
		echo '<pre>[goal id="' . $post->ID . '"]</pre>';
		echo "Add this shortcode to the page which should <strong>complete</strong> this goal:<br />";
		echo '<pre>[complete_goal id="' . $post->ID . '"]</pre>';
	}
	
	
	/* Returns true/false, indicated whether the specified goal has been completed (based on the users SESSION)*/
	public function wasGoalCompleted($goalName)
	{
		$sessionKey = 'goal_' . md5($goalName);
		$sessionValue = 'goal_completed_' . md5($goalName);
		return (isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] == $sessionValue);
	}
	
	/* Place a session variable that marks the current visitor as having completed the specified goal
	 * Note: this function does not support Conversion logging. Use completeGoalById for that (names were only used in v1, before conversions were added)
	*/
	public function completeGoal($goalName)
	{
		$alreadyCompleted = $this->wasGoalCompleted($goalName);
		if (!$alreadyCompleted)
		{
			$sessionKey = 'goal_' . md5($goalName);
			$sessionValue = 'goal_completed_' . md5($goalName);
			$_SESSION[$sessionKey] = $sessionValue;
		}
		return '';
	}

	/* Place a session variable that marks the current visitor as having completed the specified goal 
	 * Note: Will also log a conversion (Pro only)
	*/
	function completeGoalById($goalId)
	{
		$goalName = 'Goal_ID_' . $goalId;
		$alreadyCompleted = $this->wasGoalCompleted($goalName);
		if (!$alreadyCompleted)
		{
			if ($this->root->is_pro()) {			
				$conversionId = $this->root->Conversions->logConversion($goalId);

				// save the conversion ID (in the session)
				$sessionKey_cid = 'goal_' . md5($goalName) . '_cid';
				$_SESSION[$sessionKey_cid] = $conversionId;
			}

			// mark this goal as completed (in the session)
			$sessionKey = 'goal_' . md5($goalName);
			$sessionValue = 'goal_completed_' . md5($goalName);
			$_SESSION[$sessionKey] = $sessionValue;

		}
		return '';
	}
	
	
	// saves the value of a POST variable to the database
	private function update_goal_setting_from_post($post_id, $request_key, $meta_key)
	{
		if (isset($_POST[$request_key]))
		{
			 // sanitize POSTed value before saving it
			if (is_array($_POST[$request_key])) {
				$val = array_map('sanitize_text_field', $_POST[$request_key]);
			} else {
				$val = sanitize_text_field($_POST[$request_key]);
			}

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

	public function get_goal_setting_value($goal_id, $setting_location, $setting_key, $default_value = '')
	{
		if ($setting_location == 'before-values') {
			$vals = get_post_meta($goal_id, '_goal_before_values', true);
		}
		else if ($setting_location == 'after-values') {
			$vals = get_post_meta($goal_id, '_goal_after_values', true);		
		}
/* 		var_dump($goal_id);
		var_dump($setting_location);
		var_dump($setting_key);
		var_dump($default_value);
		var_dump($vals);
		die('test');
 */		if($vals && is_array($vals) && isset($vals[$setting_key])) {
			return $vals[$setting_key];
		} else {
			return $default_value;
		}		
	}
			
	
	

}