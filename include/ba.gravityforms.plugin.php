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
		/* 
		 * If a single goal is indicated (by its ID being passed in the request, complete it
		 * Else, complete all goals associated with this form id 
		 */
		if( isset($_REQUEST['_before_after_goal_id']) ) {
			// goal ID found, so complete only the single goal
			$goal_id = intval($_REQUEST['_before_after_goal_id']);
			if ($goal_id > 0) {
				$completed = $this->root->Goal->completeGoalById($goal_id);
			}
		}
		else {
			// no goal ID found, so complete all goals associated with this form
			$goals = $this->find_all_goals_by_form_id($form_id);
			if ( !empty($goals) ) {
				foreach ($goals as $goal) {
					$completed = $this->root->Goal->completeGoalById($goal->ID);
				}
			}
		}
	}
	
	function find_all_goals_by_form_id($form_id)
	{
		$goal_selector = 'gform_' . intval($form_id);
		$conditions = array('post_type' => 'b_a_goal', 
							'meta_key' => '_goal_selector',
							'meta_value' => $goal_selector,
							);
		$posts = get_posts($conditions);
		if ($posts) {
			return $posts;
		} else {
			return false;
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