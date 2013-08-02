<?php
/*
Plugin Name: Before And After - Lead Capture Plugin for Wordpress
Plugin URI: http://illuminatikarate.com/before-and-after-plugin/
Description: Before And After is a lead capture plugin for Wordpress. It allows a webmaster to require visitors to complete a goal, such as filling out a contact form, before viewing the content inside the shortcode. This functionality is also useful when webmaster's want to ensure visitors read a Terms Of Service or Copyright Notice before viewing a given page.
Author: Illuminati Karate, Inc.
Version: 1.2.1
Author URI: http://illuminatikarate.com

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
	
include('include/backfill_functions.php');


class BeforeAndAfterPlugin
{

	var $nextGoalName = false;

	/* Plugin init. Registers shortcodes and starts the session if needed */
	function __construct()
	{
		if(session_id() == '') {
			session_start();
		}
		$this->registerShortcodes();
	}
		
	/* Creates the [goal], [before], [after], and [complete_goal] shortcodes */
	function registerShortcodes()
	{
		add_shortcode('goal', array($this, 'goal_shortcode'));
		add_shortcode('before', array($this, 'before_shortcode'));
		add_shortcode('after', array($this, 'after_shortcode'));
		add_shortcode('complete_goal', array($this, 'complete_goal_shortcode'));
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
		if (isset($atts['name'])) {
			$goalName = $atts['name'];
		} else {		
			return ''; // name is required. return empty string.
		}
		$hasBefore = has_shortcode($content, 'before');
		$hasAfter = has_shortcode($content, 'after');
				
		if (!$hasBefore && !$hasAfter)
		{
			$content = '[before]' . $content . '[/before]';
			$hasBefore = true;
		}

		if ($this->wasGoalCompleted($goalName))
		{
			$shortcodeContent = $hasAfter ? $this->extract_shortcode('after', $content) : 'x';
			$this->nextGoalName = $goalName;
			return do_shortcode($shortcodeContent);
		}
		else
		{
			$shortcodeContent = $hasBefore ? $this->extract_shortcode('before', $content) : 'x';
			$this->nextGoalName = $goalName;			
			return do_shortcode($shortcodeContent);
		}
	}
	
	/* Finds a the given $shortcode inside $content and return it, along with its contemts.
	 * Returns an empty string if $shortcode is not found within $content
	 */
	function extract_shortcode($shortcode, $content)
	{
		// first verify that $shortcode is inside $content, before we start in with the regular expressions to extract it
		if (!has_shortcode($content, $shortcode)) {
			return '';
		}
		// extraction tim!
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
	
	/* Sort of a "mega trim" function that removes <br /> tags from the start and end of a string, along with a normal trim () for whitespace.
	 * Used to extract nested shortcodes without leaving a bunch of extra whitespace
	*/
	function trim_brs($str)
	{
		$str = preg_replace('/(<br \/>)+$/', '', $str);
		$str = preg_replace('/^(<br \/>)*/', '', $str);
		$str = rtrim(trim($str), '<br />');  	
		return trim($str);
	}
	
	/* Holds the content which should be shown to a visitor BEFORE they complete a specified goal.
	 *
	 * This shortcode expects to be nested inside a [goal] shortcode, and possibly accompanied by an [after] shortcode.
	 */
	function before_shortcode($atts, $content)
	{
		if ( $this->nextGoalName != '' && $this->wasGoalCompleted($this->nextGoalName) ){
			return '';
		}
		else {
			$trimmedContent = $this->trim_brs($content);
			return do_shortcode($trimmedContent);
		}		
	}
	
	/* Holds the content which should be shown to a visitor AFTER they complete a specified goal.
	 *
	 * This shortcode expects to be nested inside a [goal] shortcode, and possibly accompanied by a [before] shortcode.
	 */
	function after_shortcode($atts, $content)
	{
		if ( $this->nextGoalName != '' && $this->wasGoalCompleted($this->nextGoalName) ){
			$trimmedContent = $this->trim_brs($content);
			return do_shortcode($trimmedContent);
		}
		else {
			return '';
		}		
	}

	/* [complete_goal] shortcode
	 *
	 * This shortcode should be included on a page, e.e., a Thank You page after a newsletter signup form, 
	 * and marks the current visitor as having completed the goal (via a $_SESSION variable).
	 */
	function complete_goal_shortcode($atts, $content)
	{
		if (isset($atts['name'])) {
			$goalName = $atts['name'];
			return $this->completeGoal($goalName);
		}
		return '';
	}
	
	/* Place a session variable that marks the current visitor as having completed the specified goal */
	function completeGoal($goalName)
	{
		$sessionKey = 'goal_' . md5($goalName);
		$sessionValue = 'goal_completed_' . md5($goalName);
		$_SESSION[$sessionKey] = $sessionValue;
		return '';
	}
	
	/* Returns true/false, indicated whether the specified goal has been completed */
	function wasGoalCompleted($goalName)
	{
		$sessionKey = 'goal_' . md5($goalName);
		$sessionValue = 'goal_completed_' . md5($goalName);
		return (isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] == $sessionValue);
	}
	
}

// Instantiate one copy of the plugin class, to kick things off
$beforeAndAfter = new BeforeAndAfterPlugin();