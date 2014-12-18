<?php
class BA_CF7_Plugin
{
	var $root;
	
	public function __construct(&$root)
	{
		$this->root = $root;
		add_action("wpcf7_before_send_mail", array( $this, "capture_form_submissions") );	
	}
	
	// Looks for Goals that are hooked to this form. If any are found, marks them as complete
	function capture_form_submissions ($WPCF7_ContactForm)
	{
		$form_id = $WPCF7_ContactForm->id();
		$goal = $this->find_goal_by_form_id($form_id);
		if ($goal) {
			$completed = $this->root->Goal->completeGoalById($goal->ID);
		}
	}
	
	function find_goal_by_form_id($form_id)
	{
		$goal_selector = 'cf7_' . intval($form_id);
		$conditions = array('post_type' => 'b_a_goal', 
							'meta_key' => '_goal_selector',
							'meta_value' => $goal_selector,
							);
		$posts = get_posts($conditions);
		if ($posts) {
			return $posts[0];
		} else {
			return FALSE;
		}
	}

	
}