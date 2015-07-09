<?php
class BA_Shortcodes
{
	var $root;
	
	public function __construct($root)
	{
		$this->root = $root;
		$this->registerShortcodes();
	}
	
	// looks up the current value of the setting specified. returns $default_value if its not set
	// Note: $setting_location should be set to either "before-values" or "after-values"
	function get_goal_setting_value($goal_id, $setting_location, $setting_key, $default_value = '')
	{
		return $this->root->Goal->get_goal_setting_value($goal_id, $setting_location, $setting_key, $default_value);
	}	

	/* Creates the [goal], [before], [after], and [complete_goal] shortcodes */
	function registerShortcodes()
	{
		add_shortcode('goal', array($this, 'goal_shortcode'));
		add_shortcode('before', array($this, 'before_shortcode'));
		add_shortcode('after', array($this, 'after_shortcode'));
		add_shortcode('complete_goal', array($this, 'complete_goal_shortcode'));
		add_shortcode('goal_stats', array($this, 'goal_stats_shortcode'));
	}
	
	
	/* Holds the content which should be shown to a visitor BEFORE they complete a specified goal.
	 *
	 * This shortcode expects to be nested inside a [goal] shortcode, and possibly accompanied by an [after] shortcode.
	 */
	function before_shortcode($atts, $content)
	{
		return do_shortcode($content);
	}
	
	/* Holds the content which should be shown to a visitor AFTER they complete a specified goal.
	 *
	 * This shortcode expects to be nested inside a [goal] shortcode, and possibly accompanied by a [before] shortcode.
	 */
	function after_shortcode($atts, $content)
	{
		return do_shortcode($content);
	}

	/* [complete_goal] shortcode
	 *
	 * This shortcode should be included on a page, e.e., a Thank You page after a newsletter signup form, 
	 * and marks the current visitor as having completed the goal (via a $_SESSION variable).
	 */
	function complete_goal_shortcode($atts, $content)
	{
		$atts = shortcode_atts( array(
				'id' => 0,
				'name' => '',
		), $atts );
		$goalId = intval($atts['id']);

		if ($atts['name'] !== '') {
			$goalName = $atts['name'];
			return $this->root->Goal->completeGoal($goalName); // ad hoc (name based) goal. just return and be done.
		}
		else if ($goalId > 0) {
			$completed = $this->root->Goal->completeGoalById($goalId);
			// ahh! A goal specified by ID. We should render the after content now, unless we were told otherwise
			$goal_code = '[goal id="' . $goalId . '" /]';
			return do_shortcode($goal_code);
		}
		else {
			return '';
		}
		
		
		
	}
	
	// output the [goal_stats] shortcode; a list of recent completions and the recorded stats
	function goal_stats_shortcode($atts, $content = '')
	{
		
		$atts = shortcode_atts( array(
				'id' => 0,
		), $atts );	
		$goalId = intval($atts['id']);
		$goalData = get_post($goalId);
		$goalCompletions = get_post_meta($goalId, '_goal_completions', false);
		$html = '';
		$html .= '<h2>Goal Name: ' . apply_filters('the_title', $goalData->post_title) . '</h2>';
		foreach($goalCompletions as $index => $visitor)
		{
			if (!isset($visitor['IP']) || $visitor['IP'] == '') {
				continue; // skip blanks
			}
			
			
			$friendlyTime = date('M d Y h:i a', floatval($visitor['timestamp']));
			$html .= "Time: " . $friendlyTime . '<br />';
			$html .= "IP: " . $visitor['IP'] . '<br />' . '<br />';
		}
		
		//$html .= print_r ($goalCompletions, true);
		return $html;
		
	}
	
	/* Renders the [goal] shortcode
	 *
	 * First checks if the specified goal has been completed.
	 * - If it has, the nested [before] shortcode is rendered and returned. 
	 * - If it has not, the nested [after] shortcode is rendered and returned. 
	 * 
	 * If neither a before nor an after shortcode is contained within the [goal] shortcode,
	 * the shortcode implicitly assumes its contents should be shown *before* the 
	 * goal is completed, and nothing is shown after
	 */	
	function goal_shortcode($atts, $content)
	{
		// merge the passed in arguments with our defaults
		$atts = shortcode_atts( array(
				'id' => 0,
				'name' => '',
		), $atts );
		
		// sanitize $goalId to an int
		$goalId = intval($atts['id']);
		$goalName = $this->get_goal_name($atts);

		// make sure a valid goal name or ID was set. if not, exit and return an empty string
		if ($goalName == '') {
			// a name or id is required. since neither was found, return empty string.
			return '';
		}

		//TBD: mark this as an uncompleted coversion until / unless they convert?
		
		global $post;
		$_SESSION['goal_'.$goalId.'_encounter_url'] = isset($post->ID) ? get_permalink($post->ID) : '';
		if (true || !isset($_SESSION['goal_'.$goalId.'_start_time'])) {
			$_SESSION['goal_'.$goalId.'_start_time'] = microtime(true);
		}

		// Check if the Goal has been completed. If it has, we'll show the Before content. If not, we'll show the After content
		if (!$this->root->Goal->wasGoalCompleted($goalName))
		{
			
			// see if a [before] was specified to override the goal's default
			if (has_shortcode($content, 'before')) { 
				$value = $this->process_before_or_after_shortcode('before', $content);
			}
			else { // no override was specified, so continue with the default
				$value = $this->process_action($goalId, 'before');
			}
			return apply_filters('ba_before_content', $value);
		}
		else
		{
			// see if a [after] was specified to override the goal's default
			if (has_shortcode($content, 'after')) { 
				$value = $this->process_before_or_after_shortcode('after', $content);
			}
			else { // no override was specified, so continue with the default
				$value = $this->process_action($goalId, 'after');
			}			
			return apply_filters('ba_after_content', $value);
		}
	}
	
	private function get_goal_name($atts)
	{
		$goalName = '';
		$goalId = intval($atts['id']);
		if ($atts['name'] !== '') {
			$goalName = $atts['name'];
		}
		else if ($goalId > 0) {
			// Base the goal name on the ID
			$goalName = 'Goal_ID_' . $goalId;
		}
		return $goalName;
	}
	
	private function process_before_or_after_shortcode($beforeOrAfter, $content, $apply_filters = true)
	{
		$extractedContent = $this->extract_shortcode($beforeOrAfter, $content);
		$trimmedContent = $this->trim_brs($extractedContent);
		if ($apply_filters) {
			return apply_filters('the_content', $trimmedContent);
		} else {
			return $trimmedContent;
		}
	}
	
	private function process_action($goalId, $beforeOrAfter)
	{
		if ($beforeOrAfter == 'before') {
			$context = 'before-values';
			$action = get_post_meta($goalId, '_goal_before_action', true);
		} else if ($beforeOrAfter == 'after') {
			$context = 'after-values';
			$action = get_post_meta($goalId, '_goal_after_action', true);
		}
		else {
			return ''; // TBD: throw error/exception?
		}
		
		switch($action)
		{
			case 'redirect_page':
				$value = $this->get_goal_setting_value($goalId, $context, $action);
				$targetPageId = intval($value);
				$targetURL = get_permalink($targetPageId);				
				//todo: make sure we're not already on this page, to prevent a loop
				if (!$this->is_current_url($targetURL)) {
					return $this->get_redirect_javascript($targetURL);
				} else {
					return '';
				}
			break;
			case 'redirect_url':
				$targetURL = $this->get_goal_setting_value($goalId, $context, $action);
				//todo: make sure we're not already on this page, to prevent a loop
				if (!$this->is_current_url($targetURL)) {
					return $this->get_redirect_javascript($targetURL);
				} else {
					return '';
				}
			break;
			case 'contact_form_7':
				$value = $this->get_goal_setting_value($goalId, 'before-values', $action);
				$contactFormId = intval($value);
				$form = get_post($contactFormId);
				$shortcode = '[contact-form-7 id="' . $contactFormId . '" title="' . $form->post_title . '"]';
				return do_shortcode($shortcode);
			break;
			case 'gravity_form':
				$value = $this->get_goal_setting_value($goalId, 'before-values', $action);
				$contactFormId = intval($value);
				//$form = get_post($contactFormId); // we dont support titles for GForms yet. TBD.
				$shortcode = '[gravityform id="' . $contactFormId . '"]';
				return do_shortcode($shortcode);						
			break;
			case 'file_url':
				$targetURL = $this->get_goal_setting_value($goalId, $context, $action);
				$download_url = $this->generate_download_url($goalId, $targetURL);
				$output = '<a class="download_link" href="' . $download_url . '" target="_blank">Please click here to complete your download.</a>';
				$rendered = apply_filters('the_content', $output);
				return $rendered;
			break;
			
			case 'default':
			case 'free_text':
				$freeText = $this->get_goal_setting_value($goalId, $context, 'free_text');
				$rendered = apply_filters('the_content', $freeText);
				return $rendered;
			break;
		}	
	}
	
	/* Checks whether the provided URL matches the current page's URL. Case-insensitive.
	 *
	 * @returns		bool	True if $targetURL matches the current page's URL, false if not
	 */
	function is_current_url($targetURL)
	{
		$currentURL = $_SERVER["REQUEST_URI"];
		
		// reconstruct URLs into only their Path + Query String, to allow for more reliable comparisons
		$parts_target = parse_url($targetURL);
		$parts_current = parse_url($currentURL);		
		$cmp_target  = ( !empty($parts_target['path']) ? $parts_target['path'] : '' ) . '?' . ( !empty($parts_target['query']) ? $parts_target['query'] : '' );
		$cmp_current = ( !empty($parts_current['path']) ? $parts_current['path'] : '' ) . '?' . ( !empty($parts_current['query']) ? $parts_current['query'] : '' );

		// compare the reconstructed URLs, and return whether they match
		return (strcasecmp($cmp_target, $cmp_current) === 0);
	}
	
	// returns the HTML/Javascript code needed to perform an immediate redirect to the specified URL
	function get_redirect_javascript($targetPageURL)
	{
		$script = '<script type="text/javascript">window.location.href = "' . esc_url_raw($targetPageURL) . '";</script>;';
		return $script;	
	}
		
	/* Finds a the given $shortcode inside $content and return it, along with its contemts.
	 * Returns an empty string if $shortcode is not found within $content
	 */
	private function extract_shortcode($shortcode, $content)
	{
		// first verify that $shortcode is inside $content, before we start in with the regular expressions to extract it
		if (!has_shortcode($content, $shortcode)) {
			return '';
		}
		// extraction time!
		$pattern = get_shortcode_regex(); // this is a wp function, it returns a regex to match all shortcodes which are currently registered

		if (   preg_match_all( '/'. $pattern .'/s', $content, $matches )
			&& array_key_exists( 2, $matches )
			&& in_array( $shortcode, $matches[2] ) )
		{
			// shortcode is being used somewhere in this arrey (but the array could also contain other shortcodes, because of the regex we used). 
			// so look through the array and find it!
			
			// first step is to find the key's index, from $matches[2]
			$foundIndex = -1;
			foreach ($matches[2] as $index => $match) {			
				if ($match == $shortcode) {
					$foundIndex = $index;
					break;
				}
			}
			
			// if we found the key's index, look for the corresponding entry in $matches[0]
			// that will contain the entire shortcode, which is what we want to extract!
			if ($foundIndex >= 0 && isset($matches[0][$foundIndex])) {
				return $matches[0][$foundIndex];
			} else {
				return '';
			}
			
		} else {
			return '';
		}
	
	}	
	
	function generate_download_url($goalId, $file_url)
	{
		$goalName = 'Goal_ID_' . $goalId;
		$sessionKey_cid = 'goal_' . md5($goalName) . '_cid';
		$cid = isset($_SESSION[$sessionKey_cid]) ? intval($_SESSION[$sessionKey_cid]) : 0;
		if ($cid > 0) {
			$metaKey = '_b_a_download_key';
			$download_key = get_post_meta($cid, $metaKey, true);
			if ($download_key && strlen($download_key) > 1) {
				$url = site_url('?file_download=' . urlencode($download_key));
				return $url;
			}
		} else {
			// no conversion id was set, or we are not Pro users, so just return the plain URL
			return $file_url;
		}
		return '';
	}
	
	/* Sort of a "mega trim" function that removes <br /> tags from the start and end of a string, along with a normal trim () for whitespace.
	 * Used to extract nested shortcodes without leaving a bunch of extra whitespace
	*/
	private function trim_brs($str)
	{
		$str = preg_replace('/(<br \/>)+$/', '', $str);
		$str = preg_replace('/^(<br \/>)*/', '', $str);
		$str = rtrim(trim($str), '<br />');  	
		return trim($str);
	}

	
	
	
}