<?php
/*
This file is part of Before & After.

Before & After is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Before & After is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Before & After.  If not, see <http://www.gnu.org/licenses/>.
*/

class Before_After_Goal_Widget extends WP_Widget
{
	var $widget_keys = array(
		'title' => '',
		'goal_id' => '',
	);
	
	function __construct(){
		$widget_ops = array(
			'classname' => 'Before_After_Goal_Widget',
			'description' => 'Displays a Before &amp; After Goal.'
		);
		parent::__construct('Before_After_Goal_Widget', 'Before &amp; After: Goal', $widget_ops);		
	}
		
	function Before_After_Goal_Widget()
	{
		$this->__construct();
	}

	function form($instance)
	{
		// merge instance values with the defaults
		$instance = wp_parse_args( (array) $instance, $this->widget_keys );
		
		// init local vars needed to display the form
		$title = $instance['title'];
		$goal_id = $instance['goal_id'];
		$testimonial_categories = get_terms( 'easy-testimonial-category', 'orderby=title&hide_empty=0' );				
		$all_goals = get_posts('post_type=b_a_goal&posts_per_page=-1&nopaging=true');
		
		// show the form!
		?>
		<div class="gp_widget_form_wrapper">
			<?php if($all_goals): ?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>">Widget Title:</label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
			</p>			
		
			<p>
				<label for="<?php echo $this->get_field_id('goal_id'); ?>">Goal:</label><br />
				<select id="<?php echo $this->get_field_id('goal_id'); ?>" name="<?php echo $this->get_field_name('goal_id'); ?>">
					<?php foreach ( $all_goals as $goal  ) : ?>
					<option value="<?php echo $goal->ID; ?>"  <?php if($goal_id == $goal->ID): ?> selected="SELECTED" <?php endif; ?>><?php echo $goal->post_title; ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php else: // No Goals created! Show an error message and a link to create a Goal ?>
			<h3 class="error">No Goals Created!</h3>			
			<p class="error"><em>You'll need to setup at least one Goal to use this widget. Once you've done so, you'll be able to select it here.</p>
			<p><a class="button" href="<?php echo admin_url('post-new.php?post_type=b_a_goal');?>">Create A New Goal</a></em></p>
			<br />
			<?php endif; // has at least one goal ?>
		</div>
		
		<?php
	}
	
	function update($new_instance, $old_instance)
	{		
		$instance = $old_instance;
		foreach($this->widget_keys as $key => $default) {
			$instance[$key] = $new_instance[$key];
		}
		return $instance;
	}
	
	function widget($args, $instance)
	{
		$final_vals = $this->get_final_values($instance);
		extract($args, EXTR_SKIP);
		extract($final_vals, EXTR_SKIP);

		echo $before_widget;
		
		if (!empty($title)) {
			echo $before_title . $title . $after_title;;
		}
		
		// output the actual goal
		$shortcode_tmpl = '[goal id="%d"]';
		$shortcode_final = sprintf($shortcode_tmpl, $goal_id);
		echo do_shortcode($shortcode_final);

		echo $after_widget;
	}
	
	function get_final_values($instance)
	{
		$vals = array();
		foreach($this->widget_keys as $key => $default) 
		{
			if( isset($instance[$key]) ) {
				$vals[$key] = $instance[$key];
			} else {
				$vals[$key] = $default;				
			}
		}
		return $vals;
	}
}