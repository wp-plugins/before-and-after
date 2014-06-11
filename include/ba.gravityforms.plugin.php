<?php
class BA_GravityForms_Plugin
{
	var $root;
	
	public function __construct(&$root)
	{
		$this->root = $root;
		add_action("gform_after_submission", array( $this, "capture_form_submissions"), 10, 2 );	
	}
	
	// Looks for Goals that are hooked to this form. If any are found, marks them as complete
	function capture_form_submissions ($entry, $form)
	{
		$form_id = $form['id'];
		$goal = $this->find_goal_by_form_id($form_id);
		if ($goal) {
			$completed = $this->root->Goal->completeGoalById($goal->ID);
		}
	}
	
	function find_goal_by_form_id($form_id)
	{
		$goal_selector = 'gform_' . intval($form_id);
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